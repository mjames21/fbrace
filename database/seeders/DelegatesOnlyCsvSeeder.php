<?php

namespace Database\Seeders;

use App\Models\Delegate;
use App\Models\District;
use App\Models\Group;
use App\Models\Region;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DelegatesOnlyCsvSeeder extends Seeder
{
    /**
     * Reads: storage/app/imports/delegates.csv
     *
     * Supported headers (case-insensitive; spaces/dashes ok; BOM ok):
     * - full_name | name | delegate_name
     * - category | section | group_category
     * - region | region_name
     * - district | district_name
     * - constituency
     * - groups | group | bloc | blocs   (multi values separated by ; or ,)
     * - is_high_value | high_value      (1/0/yes/no/true/false)
     *
     * Notes:
     * - Reuses existing Regions/Districts/Groups; will firstOrCreate missing ones safely.
     * - Delegate unique key used: (full_name + category + district_id)
     */
    public function run(): void
    {
        $fullPath = storage_path('app/imports/delegates.csv');

        if (!file_exists($fullPath)) {
            $this->command?->error("CSV not found at: {$fullPath}");
            return;
        }

        $stream = fopen($fullPath, 'rb');
        if ($stream === false) {
            $this->command?->error("Failed to open CSV: {$fullPath}");
            return;
        }

        $header = null;

        $counts = [
            'rows_total' => 0,
            'rows_skipped_missing_name' => 0,
            'delegates_upserted' => 0,
            'groups_attached' => 0,
            'missing_region_created' => 0,
            'missing_district_created' => 0,
        ];

        DB::transaction(function () use (&$header, $stream, &$counts) {
            while (($row = fgetcsv($stream)) !== false) {
                if ($row === [null] || $row === false) {
                    continue;
                }

                $counts['rows_total']++;

                if ($header === null) {
                    $header = $this->canonicalizeHeaderRow($row);
                    continue;
                }

                $data = $this->rowToAssoc($header, $row);

                $fullName = $this->firstNonEmpty($data, ['full_name', 'name', 'delegate_name']);
                $fullName = $this->nullIfEmpty($fullName);

                if (!$fullName) {
                    $counts['rows_skipped_missing_name']++;
                    continue;
                }

                $category = $this->nullIfEmpty($this->firstNonEmpty($data, ['category', 'section', 'group_category']));
                $regionName = $this->nullIfEmpty($this->firstNonEmpty($data, ['region', 'region_name']));
                $districtName = $this->nullIfEmpty($this->firstNonEmpty($data, ['district', 'district_name']));
                $constituency = $this->nullIfEmpty($this->firstNonEmpty($data, ['constituency']));
                $groupsRaw = $this->firstNonEmpty($data, ['groups', 'group', 'bloc', 'blocs']);

                $isHighValue = $this->parseBool($this->firstNonEmpty($data, ['is_high_value', 'high_value']));
                $isHighValue ??= $this->inferHighValue($category);

                $region = null;
                if ($regionName) {
                    $region = Region::query()
                        ->where('name', $regionName)
                        ->orWhere('slug', Str::slug($regionName))
                        ->first();

                    if (!$region) {
                        $region = Region::firstOrCreate(
                            ['slug' => Str::slug($regionName)],
                            ['name' => $regionName, 'slug' => Str::slug($regionName)]
                        );
                        $counts['missing_region_created']++;
                    }
                }

                $district = null;
                if ($districtName) {
                    $districtSlug = Str::slug($districtName);

                    $districtQuery = District::query()
                        ->where(function ($q) use ($districtName, $districtSlug) {
                            $q->where('name', $districtName)->orWhere('slug', $districtSlug);
                        });

                    if ($region) {
                        $districtQuery->where('region_id', $region->id);
                    }

                    $district = $districtQuery->first();

                    if (!$district) {
                        $region ??= Region::firstOrCreate(
                            ['slug' => 'unknown'],
                            ['name' => 'Unknown', 'slug' => 'unknown']
                        );

                        $district = District::firstOrCreate(
                            ['region_id' => $region->id, 'slug' => $districtSlug],
                            ['region_id' => $region->id, 'name' => $districtName, 'slug' => $districtSlug]
                        );

                        $counts['missing_district_created']++;
                    }
                }

                $delegate = Delegate::updateOrCreate(
                    [
                        'full_name' => $fullName,
                        'category' => $category,
                        'district_id' => $district?->id,
                    ],
                    [
                        'constituency' => $constituency,
                        'is_high_value' => (bool) $isHighValue,
                        'is_active' => true,
                    ]
                );

                $counts['delegates_upserted']++;

                $groups = $this->parseGroups($groupsRaw);
                if (!empty($groups) && method_exists($delegate, 'groups')) {
                    $groupIds = [];

                    foreach ($groups as $gName) {
                        $g = Group::query()
                            ->where('name', $gName)
                            ->orWhere('slug', Str::slug($gName))
                            ->first();

                        $g ??= Group::firstOrCreate(
                            ['slug' => Str::slug($gName)],
                            ['name' => $gName, 'slug' => Str::slug($gName)]
                        );

                        $groupIds[] = $g->id;
                    }

                    $delegate->groups()->syncWithoutDetaching($groupIds);
                    $counts['groups_attached'] += count($groupIds);
                }
            }
        });

        fclose($stream);

        $this->command?->info("Delegates CSV import done:");
        foreach ($counts as $k => $v) {
            $this->command?->info(" - {$k}: {$v}");
        }
    }

    private function canonicalizeHeaderRow(array $headerRow): array
    {
        return array_map(function ($h) {
            $h = (string) $h;
            $h = preg_replace('/^\xEF\xBB\xBF/', '', $h) ?: $h; // strip UTF-8 BOM
            $h = Str::lower(trim($h));
            $h = str_replace([' ', '-'], '_', $h);
            return $h;
        }, $headerRow);
    }

    private function rowToAssoc(array $header, array $row): array
    {
        $assoc = [];
        foreach ($header as $i => $key) {
            $assoc[$key] = $row[$i] ?? null;
        }
        return $assoc;
    }

    private function firstNonEmpty(array $data, array $keys): ?string
    {
        foreach ($keys as $k) {
            if (array_key_exists($k, $data)) {
                $v = trim((string) ($data[$k] ?? ''));
                if ($v !== '') {
                    return $v;
                }
            }
        }
        return null;
    }

    private function nullIfEmpty($v): ?string
    {
        $v = trim((string) ($v ?? ''));
        return $v === '' ? null : $v;
    }

    private function parseGroups($v): array
    {
        $v = trim((string) ($v ?? ''));
        if ($v === '') {
            return [];
        }

        $parts = preg_split('/[;,]+/', $v) ?: [];
        $parts = array_values(array_filter(array_map(fn ($x) => trim($x), $parts)));
        return array_values(array_unique($parts));
    }

    private function parseBool($v): ?bool
    {
        if ($v === null) return null;

        $s = Str::lower(trim((string) $v));
        if ($s === '') return null;

        if (in_array($s, ['1', 'true', 'yes', 'y'], true)) return true;
        if (in_array($s, ['0', 'false', 'no', 'n'], true)) return false;

        return null;
    }

    private function inferHighValue(?string $category): bool
    {
        if (!$category) return false;

        $c = Str::lower($category);

        return Str::contains($c, [
            'mp',
            'member of parliament',
            'national officer',
            'regional executive',
            'district executive',
            'chairman',
            'secretary',
        ]);
    }
}

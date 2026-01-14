<?php

namespace App\Services;

use App\Models\Delegate;
use App\Models\District;
use App\Models\Group;
use App\Models\Guarantor;
use App\Models\Region;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class RosterImportService
{
    /**
     * Imports Regions, Districts, Groups, Delegates FROM DOCX.
     *
     * Notes:
     * - Works with .docx (Word2007). For .doc, throws a friendly error.
     * - Category is inferred from current section heading.
     * - District is inferred from current district heading or inline matches.
     * - Group membership inferred from current group heading.
     * - Phones can be extracted if present on a line (best-effort).
     */
    public function import(string $path, bool $dryRun = false): array
    {
        $warnings = [];

        if (!File::exists($path)) {
            throw new \RuntimeException("File not found: {$path}");
        }

        $ext = Str::lower(pathinfo($path, PATHINFO_EXTENSION));
        if ($ext !== 'docx') {
            throw new \RuntimeException(
                "Roster import expects .docx. Your file is .{$ext}. Convert to .docx then rerun.\n" .
                "Tip: Open in Word → Save As → .docx"
            );
        }

        if (!class_exists(\PhpOffice\PhpWord\IOFactory::class)) {
            throw new \RuntimeException("phpoffice/phpword not installed. Run: composer require phpoffice/phpword");
        }

        $text = $this->readDocxText($path);
        $lines = $this->normalizeLines($text);

        $knownRegions = [
            'Eastern Region',
            'Southern Region',
            'North East Region',
            'North West Region',
            'Western Region',
        ];

        $knownGroups = [
            'PALM TREE ORGANISATION (PTO)',
            'CLUB OF LIKE MINDS',
            'HERITAGE ORGANISATION',
            'SUMOKAB',
            'TDA',
            'JMB WOMEN AND ASSOCIATES',
            'NATIONAL COUNCIL OF ELDERS',
            'STUDENTS FROM THE VARIOUS TERTIARY INSTITUTIONS',
        ];

        $knownDistricts = [
            'Kailahun', 'Kenema', 'Kono', 'Bo', 'Bonthe', 'Moyamba', 'Pujehun',
            'Tonkolili', 'Bombali', 'Koinadugu', 'Falaba', 'Port Loko', 'Kambia', 'Karene',
            'Western Rural', 'Western Area Rural', 'Western Area Urban', 'West Urban', 'East Urban',
            'East Rural', 'Central',
        ];

        $counts = [
            'lines' => count($lines),
            'regions' => 0,
            'districts' => 0,
            'groups' => 0,
            'guarantors' => 0,
            'delegates' => 0,
            'delegate_group_links' => 0,
            'warnings' => &$warnings,
        ];

        $ctx = [
            'current_category' => null,
            'current_region' => null,     // Region model
            'current_district' => null,   // District model
            'current_group' => null,      // Group model
        ];

        $write = function (callable $fn) use ($dryRun) {
            if ($dryRun) {
                return null;
            }
            return $fn();
        };

        DB::transaction(function () use (
            $lines,
            $knownRegions,
            $knownGroups,
            $knownDistricts,
            &$counts,
            &$ctx,
            $write,
            &$warnings
        ) {
            foreach ($knownRegions as $r) {
                $slug = Str::slug($r);
                $region = $write(fn () => Region::firstOrCreate(['slug' => $slug], ['name' => $r, 'slug' => $slug]));
                if ($region) $counts['regions']++;
            }

            foreach ($knownGroups as $g) {
                $group = $this->upsertGroup($g, $write, $counts);
                if ($group) $counts['groups']++;
            }

            foreach ($lines as $line) {
                $maybeHeading = $this->normalizeHeading($line);

                if ($maybeHeading) {
                    $ctx['current_category'] = $maybeHeading;

                    $groupName = $this->matchKnown($maybeHeading, $knownGroups);
                    if ($groupName) {
                        $ctx['current_group'] = $this->upsertGroup($groupName, $write, $counts);
                    }

                    $regionName = $this->matchKnown($maybeHeading, $knownRegions);
                    if ($regionName) {
                        $ctx['current_region'] = $this->upsertRegion($regionName, $write, $counts);
                        $ctx['current_district'] = null;
                    }

                    $districtName = $this->matchKnown($maybeHeading, $knownDistricts);
                    if ($districtName) {
                        $ctx['current_district'] = $this->upsertDistrict(
                            name: $districtName,
                            regionId: $ctx['current_region']?->id,
                            write: $write,
                            counts: $counts,
                            warnings: $warnings
                        );
                    }

                    continue;
                }

                $name = $this->extractName($line);
                if (!$name) {
                    continue;
                }

                $category = $ctx['current_category'];
                $districtId = $ctx['current_district']?->id;

                $inlineDistrict = $this->findInlineDistrict($line, $knownDistricts);
                if ($inlineDistrict) {
                    $ctx['current_district'] = $this->upsertDistrict(
                        name: $inlineDistrict,
                        regionId: $ctx['current_region']?->id,
                        write: $write,
                        counts: $counts,
                        warnings: $warnings
                    );
                    $districtId = $ctx['current_district']?->id;
                }

                $isHighValue = $this->inferHighValue($category);

                $phones = $this->extractPhones($line);
                $phonePrimary = $phones['primary'] ?? null;
                $phoneSecondary = $phones['secondary'] ?? null;

                // DOCX doesn’t usually contain guarantors; left here if you later add patterns.
                $guarantorId = null;

                $districtId = $districtId ?: $this->fallbackDistrictId($write, $counts);

                $delegate = $write(function () use (
                    $name,
                    $category,
                    $districtId,
                    $phonePrimary,
                    $phoneSecondary,
                    $guarantorId,
                    $isHighValue
                ) {
                    return Delegate::query()->updateOrCreate(
                        ['full_name' => $name, 'district_id' => $districtId],
                        $this->delegatePayload(
                            category: $category,
                            districtId: $districtId,
                            phonePrimary: $phonePrimary,
                            phoneSecondary: $phoneSecondary,
                            guarantorId: $guarantorId,
                            isHighValue: $isHighValue
                        )
                    );
                });

                if ($delegate) {
                    $counts['delegates']++;
                }

                if ($delegate && $ctx['current_group']) {
                    $write(function () use ($delegate, &$ctx, &$counts) {
                        $delegate->groups()->syncWithoutDetaching([$ctx['current_group']->id]);
                        $counts['delegate_group_links']++;
                    });
                }
            }
        });

        return $counts;
    }

    /**
     * Imports Delegates FROM CSV.
     *
     * Expected headers (case-insensitive):
     * - full_name OR name
     * - category (optional)
     * - region
     * - district
     * - group (optional)
     * - phone_primary (optional)
     * - phone_secondary (optional)
     * - guarantor_name (optional) -> auto-create guarantor and assign
     */
    public function importDelegatesFromCsv(string $absolutePath, bool $dryRun = false): array
    {
        if (!is_file($absolutePath)) {
            return ['ok' => false, 'message' => 'CSV not found: ' . $absolutePath];
        }

        $rows = $this->readCsv($absolutePath);
        if (count($rows) < 2) {
            return ['ok' => false, 'message' => 'CSV is empty or missing rows.'];
        }

        $headers = array_map([$this, 'normHeader'], $rows[0]);
        $idx = $this->headerIndex($headers);

        $warnings = [];
        $imported = 0;

        $counts = [
            'ok' => true,
            'imported' => 0,
            'regions' => 0,
            'districts' => 0,
            'groups' => 0,
            'guarantors' => 0,
            'delegate_group_links' => 0,
            'warnings' => &$warnings,
        ];

        $write = function (callable $fn) use ($dryRun) {
            if ($dryRun) return null;
            return $fn();
        };

        DB::transaction(function () use ($rows, $idx, &$imported, &$counts, $write, &$warnings) {
            for ($i = 1; $i < count($rows); $i++) {
                $r = $rows[$i];
                if ($this->rowIsEmpty($r)) continue;

                $fullName = $this->value($r, $idx, ['full_name', 'name']);
                $category = $this->value($r, $idx, ['category']);
                $regionName = $this->value($r, $idx, ['region']);
                $districtName = $this->value($r, $idx, ['district']);
                $groupName = $this->value($r, $idx, ['group']);

                if (!$fullName || !$regionName || !$districtName) {
                    $warnings[] = "Row {$i}: missing required fields (full_name/name, region, district).";
                    continue;
                }

                $region = $this->upsertRegion($regionName, $write, $counts);
                $district = $this->upsertDistrict(
                    name: $districtName,
                    regionId: $region?->id,
                    write: $write,
                    counts: $counts,
                    warnings: $warnings
                );

                $districtId = $district?->id ?: $this->fallbackDistrictId($write, $counts);

                $groupIds = [];
                if ($groupName) {
                    $g = $this->upsertGroup($groupName, $write, $counts);
                    if ($g) $groupIds[] = $g->id;
                }

                $phonePrimary = $this->value($r, $idx, ['phone_primary', 'phone1', 'phone']);
                $phoneSecondary = $this->value($r, $idx, ['phone_secondary', 'phone2']);

                $guarantorName = $this->value($r, $idx, ['guarantor_name', 'guarantor']);
                $guarantorId = null;

                if ($guarantorName) {
                    $guarantor = $this->upsertGuarantor(
                        name: $guarantorName,
                        districtId: $districtId,
                        write: $write,
                        counts: $counts
                    );
                    $guarantorId = $guarantor?->id ? (int) $guarantor->id : null;
                }

                $delegate = $write(function () use (
                    $fullName,
                    $districtId,
                    $category,
                    $phonePrimary,
                    $phoneSecondary,
                    $guarantorId
                ) {
                    return Delegate::query()->updateOrCreate(
                        ['full_name' => $fullName, 'district_id' => $districtId],
                        $this->delegatePayload(
                            category: $category,
                            districtId: $districtId,
                            phonePrimary: $phonePrimary,
                            phoneSecondary: $phoneSecondary,
                            guarantorId: $guarantorId,
                            isHighValue: $this->inferHighValue($category)
                        )
                    );
                });

                if ($delegate && count($groupIds)) {
                    $write(function () use ($delegate, $groupIds, &$counts) {
                        $delegate->groups()->syncWithoutDetaching($groupIds);
                        $counts['delegate_group_links'] += count($groupIds);
                    });
                }

                if ($delegate) {
                    $imported++;
                }
            }
        });

        $counts['imported'] = $imported;

        return $counts;
    }

    private function delegatePayload(
        ?string $category,
        ?int $districtId,
        ?string $phonePrimary,
        ?string $phoneSecondary,
        ?int $guarantorId,
        bool $isHighValue
    ): array {
        $payload = [];

        if (Schema::hasColumn('delegates', 'category')) $payload['category'] = $category;
        if (Schema::hasColumn('delegates', 'district_id')) $payload['district_id'] = $districtId;

        if (Schema::hasColumn('delegates', 'phone_primary')) {
            $payload['phone_primary'] = $phonePrimary;
        } elseif (Schema::hasColumn('delegates', 'phone')) {
            $payload['phone'] = $phonePrimary;
        }

        if (Schema::hasColumn('delegates', 'phone_secondary')) {
            $payload['phone_secondary'] = $phoneSecondary;
        }

        if (Schema::hasColumn('delegates', 'guarantor_id')) {
            $payload['guarantor_id'] = $guarantorId;
        }

        if (Schema::hasColumn('delegates', 'is_high_value')) $payload['is_high_value'] = $isHighValue;
        if (Schema::hasColumn('delegates', 'is_active')) $payload['is_active'] = true;

        // Keep prior fields if your schema still has them.
        if (Schema::hasColumn('delegates', 'constituency')) $payload['constituency'] = $payload['constituency'] ?? null;
        if (Schema::hasColumn('delegates', 'email')) $payload['email'] = $payload['email'] ?? null;

        return $payload;
    }

    private function readDocxText(string $path): string
    {
        $phpWord = \PhpOffice\PhpWord\IOFactory::load($path);
        $all = '';

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $el) {
                $all .= $this->elementText($el) . "\n";
            }
        }

        return $all;
    }

    private function elementText($el): string
    {
        $class = get_class($el);

        if ($class === \PhpOffice\PhpWord\Element\TextRun::class) {
            $t = '';
            foreach ($el->getElements() as $child) $t .= $this->elementText($child);
            return $t;
        }

        if ($class === \PhpOffice\PhpWord\Element\Text::class) {
            return (string) $el->getText();
        }

        if ($class === \PhpOffice\PhpWord\Element\Table::class) {
            $t = '';
            foreach ($el->getRows() as $row) {
                $cells = [];
                foreach ($row->getCells() as $cell) {
                    $cellText = '';
                    foreach ($cell->getElements() as $cellEl) {
                        $cellText .= $this->elementText($cellEl) . ' ';
                    }
                    $cells[] = trim($cellText);
                }
                $t .= implode(' | ', array_filter($cells)) . "\n";
            }
            return $t;
        }

        return '';
    }

    private function normalizeLines(string $text): array
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $raw = array_filter(array_map('trim', explode("\n", $text)));

        $lines = [];
        foreach ($raw as $line) {
            $line = preg_replace('/\s+/', ' ', $line);
            if (mb_strlen($line) < 3) continue;

            $line = preg_replace('/^\d+[\.\)]\s*/', '', $line);
            $lines[] = trim($line);
        }

        return $lines;
    }

    private function normalizeHeading(string $line): ?string
    {
        $s = trim($line);

        $isAllCaps = $s === mb_strtoupper($s) && preg_match('/[A-Z]/', $s);
        $shortEnough = mb_strlen($s) <= 80;

        if (($isAllCaps && $shortEnough) || $this->looksLikeSectionTitle($s)) {
            return $s;
        }

        return null;
    }

    private function looksLikeSectionTitle(string $s): bool
    {
        $k = Str::lower($s);
        return Str::contains($k, [
            'district executives',
            'regional executives',
            'members of parliament',
            'councillors',
            'organisation',
            'organization',
            'national council',
            'students',
            'constituenc',
        ]);
    }

    private function matchKnown(string $heading, array $known): ?string
    {
        foreach ($known as $k) {
            if (Str::contains(Str::lower($heading), Str::lower($k))) {
                return $k;
            }
        }
        return null;
    }

    private function extractName(string $line): ?string
    {
        $s = trim($line);

        if (Str::contains(Str::lower($s), ['delegate list', 'elections', 'final'])) {
            return null;
        }

        if (!preg_match('/[A-Za-z]/', $s) || mb_strlen($s) < 5) {
            return null;
        }

        foreach ([' | ', ' - ', "\t"] as $sep) {
            if (str_contains($s, $sep)) {
                $parts = array_map('trim', explode($sep, $s));
                $candidate = $parts[0] ?? '';
                return $this->cleanName($candidate);
            }
        }

        return $this->cleanName($s);
    }

    private function cleanName(string $name): ?string
    {
        $name = trim($name);
        $name = preg_replace('/\s+/', ' ', $name);
        if (mb_strlen($name) < 5) return null;
        return $name;
    }

    private function findInlineDistrict(string $line, array $knownDistricts): ?string
    {
        $l = Str::lower($line);
        foreach ($knownDistricts as $d) {
            if (Str::contains($l, Str::lower($d))) return $d;
        }
        return null;
    }

    /**
     * Best-effort: pulls phone numbers from a line.
     * Accepts digits with optional +, spaces, hyphens.
     *
     * @return array{primary?: string, secondary?: string}
     */
    private function extractPhones(string $line): array
    {
        $matches = [];
        preg_match_all('/(\+?\d[\d\-\s]{6,}\d)/', $line, $matches);

        $nums = array_map(function ($v) {
            $v = preg_replace('/[^\d\+]/', '', $v);
            return trim($v);
        }, $matches[0] ?? []);

        $nums = array_values(array_filter(array_unique($nums), fn ($v) => mb_strlen($v) >= 8));

        return [
            'primary' => $nums[0] ?? null,
            'secondary' => $nums[1] ?? null,
        ];
    }

    private function upsertRegion(string $name, callable $write, array &$counts): ?Region
    {
        $slug = Str::slug($name) ?: 'region';
        $region = $write(fn () => Region::firstOrCreate(['slug' => $slug], ['name' => $name, 'slug' => $slug]));
        if ($region) $counts['regions']++;
        return $region;
    }

    private function upsertDistrict(string $name, ?int $regionId, callable $write, array &$counts, array &$warnings): ?District
    {
        $slug = Str::slug($name) ?: 'district';

        if (!$regionId) {
            $warnings[] = "District '{$name}' found but region context not set. District saved under Unknown region.";
            $regionId = $this->fallbackRegionId($write, $counts);
        }

        $district = $write(fn () => District::firstOrCreate(
            ['region_id' => $regionId, 'slug' => $slug],
            ['name' => $name, 'region_id' => $regionId, 'slug' => $slug]
        ));

        if ($district) $counts['districts']++;

        return $district;
    }

    private function upsertGroup(string $name, callable $write, array &$counts): ?Group
    {
        $slug = Str::slug($name) ?: 'group';
        $group = $write(fn () => Group::firstOrCreate(['slug' => $slug], ['name' => $name, 'slug' => $slug]));
        if ($group) $counts['groups']++;
        return $group;
    }

    private function upsertGuarantor(string $name, int $districtId, callable $write, array &$counts): ?Guarantor
    {
        $slug = $this->uniqueGuarantorSlug($name);

        $guarantor = $write(fn () => Guarantor::firstOrCreate(
            ['name' => $name],
            [
                'slug' => $slug,
                'district_id' => $districtId,
                'is_active' => true,
                'sort_order' => 1000,
            ]
        ));

        if ($guarantor) $counts['guarantors']++;

        return $guarantor;
    }

    private function uniqueGuarantorSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'guarantor';
        $slug = $base;
        $i = 2;

        while (Guarantor::query()->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i;
            $i++;
        }

        return $slug;
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
            'secretary general',
        ]);
    }

    private function fallbackRegionId(callable $write, array &$counts): int
    {
        $unknown = $write(fn () => Region::firstOrCreate(
            ['slug' => 'unknown'],
            ['name' => 'Unknown', 'slug' => 'unknown']
        ));

        if ($unknown) $counts['regions']++;

        return (int) ($unknown?->id ?? Region::query()->where('slug', 'unknown')->value('id'));
    }

    private function fallbackDistrictId(callable $write, array &$counts): int
    {
        $regionId = $this->fallbackRegionId($write, $counts);

        $district = $write(fn () => District::firstOrCreate(
            ['region_id' => $regionId, 'slug' => 'unknown'],
            ['name' => 'Unknown', 'region_id' => $regionId, 'slug' => 'unknown']
        ));

        if ($district) $counts['districts']++;

        return (int) ($district?->id ?? District::query()->where('slug', 'unknown')->where('region_id', $regionId)->value('id'));
    }

    // =========================
    // CSV helpers
    // =========================

    private function readCsv(string $path): array
    {
        $rows = [];
        $fh = fopen($path, 'r');
        if (!$fh) return $rows;

        while (($data = fgetcsv($fh)) !== false) {
            $rows[] = array_map(fn ($v) => is_string($v) ? trim($v) : $v, $data);
        }

        fclose($fh);
        return $rows;
    }

    private function normHeader(string $h): string
    {
        $h = strtolower(trim($h));
        $h = str_replace([' ', '-', '.'], '_', $h);
        return $h;
    }

    private function headerIndex(array $headers): array
    {
        $idx = [];
        foreach ($headers as $i => $h) $idx[$h] = $i;
        return $idx;
    }

    private function value(array $row, array $idx, array $keys): ?string
    {
        foreach ($keys as $k) {
            $k = $this->normHeader($k);
            if (isset($idx[$k])) {
                $v = $row[$idx[$k]] ?? null;
                $v = is_string($v) ? trim($v) : $v;
                if ($v !== null && $v !== '') return (string) $v;
            }
        }
        return null;
    }

    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $v) {
            if (trim((string) $v) !== '') return false;
        }
        return true;
    }
}

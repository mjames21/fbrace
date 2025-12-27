<?php

namespace App\Services;

use App\Models\Delegate;
use App\Models\District;
use App\Models\Group;
use App\Models\Region;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class RosterImportService
{
    /**
     * Imports Regions, Districts, Groups, Delegates.
     *
     * Rules:
     * - Works with .docx (Word2007). For .doc, throws a friendly error.
     * - Category is inferred from current section heading.
     * - District is inferred from current district heading or inline matches.
     * - Group membership inferred from current group heading.
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

        // Seed known region names (from your doc concept). Districts are created on the fly.
        $knownRegions = [
            'Eastern Region',
            'Southern Region',
            'North East Region',
            'North West Region',
            'Western Region',
        ];

        // Group headings we saw in your doc (can expand later; also auto-detects uppercase headings).
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

        // District names (from your doc). Used to detect district headings/mentions.
        $knownDistricts = [
            'Kailahun','Kenema','Kono','Bo','Bonthe','Moyamba','Pujehun',
            'Tonkolili','Bombali','Koinadugu','Falaba','Port Loko','Kambia','Karene','Western Rural',
            'Western Area Rural','Western Area Urban','West Urban','East Urban','East Rural','Central',
        ];

        $counts = [
            'lines' => count($lines),
            'regions' => 0,
            'districts' => 0,
            'groups' => 0,
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
            $lines, $knownRegions, $knownGroups, $knownDistricts, &$counts, &$ctx, $write, &$warnings
        ) {
            // Ensure regions exist (idempotent)
            foreach ($knownRegions as $r) {
                $slug = Str::slug($r);
                $region = $write(fn () => Region::firstOrCreate(['slug' => $slug], ['name' => $r, 'slug' => $slug]));
                if ($region) {
                    $counts['regions']++;
                }
            }

            foreach ($lines as $line) {
                // 1) Detect major SECTION headings → category context
                $maybeHeading = $this->normalizeHeading($line);
                if ($maybeHeading) {
                    $ctx['current_category'] = $maybeHeading;

                    // If heading matches a known group, set group context
                    $groupName = $this->matchKnown($maybeHeading, $knownGroups);
                    if ($groupName) {
                        $ctx['current_group'] = $this->upsertGroup($groupName, $write, $counts);
                        // Group delegates often lack district context; keep district as-is or null it
                    }

                    // If heading matches a known region, set region context
                    $regionName = $this->matchKnown($maybeHeading, $knownRegions);
                    if ($regionName) {
                        $ctx['current_region'] = $this->upsertRegion($regionName, $write, $counts);
                        $ctx['current_district'] = null;
                    }

                    // If heading matches a district, set district context (region unknown unless already set)
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

                // 2) If the line looks like a delegate name, create delegate
                $name = $this->extractName($line);
                if (!$name) {
                    continue;
                }

                $category = $ctx['current_category'];
                $districtId = $ctx['current_district']?->id;

                // Try to infer district from inline text too (e.g., "John Doe - Bo")
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

                $delegate = $write(fn () => Delegate::firstOrCreate(
                    ['full_name' => $name],
                    [
                        'category' => $category,
                        'district_id' => $districtId,
                        'constituency' => null,
                        'phone' => null,
                        'email' => null,
                        'is_high_value' => $isHighValue,
                        'is_active' => true,
                    ]
                ));

                if ($delegate) {
                    $counts['delegates']++;
                }

                // 3) Attach group membership if in a group section
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
            foreach ($el->getElements() as $child) {
                $t .= $this->elementText($child);
            }
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
            if (mb_strlen($line) < 3) {
                continue;
            }
            // Strip common numbering prefixes "1." "2)" etc.
            $line = preg_replace('/^\d+[\.\)]\s*/', '', $line);
            $lines[] = trim($line);
        }
        return $lines;
    }

    private function normalizeHeading(string $line): ?string
    {
        $s = trim($line);

        // Heuristic: headings are often ALL CAPS or Title-ish and not too long
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

        // Skip obvious non-name lines
        if (Str::contains(Str::lower($s), ['delegate list', 'elections', 'final'])) {
            return null;
        }

        // Basic "looks like a person/org name" check
        if (!preg_match('/[A-Za-z]/', $s) || mb_strlen($s) < 5) {
            return null;
        }

        // If line has separators, name is before them
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
        if (mb_strlen($name) < 5) {
            return null;
        }
        return $name;
    }

    private function findInlineDistrict(string $line, array $knownDistricts): ?string
    {
        $l = Str::lower($line);
        foreach ($knownDistricts as $d) {
            if (Str::contains($l, Str::lower($d))) {
                return $d;
            }
        }
        return null;
    }

    private function upsertRegion(string $name, callable $write, array &$counts): ?Region
    {
        $slug = Str::slug($name);
        $region = $write(fn () => Region::firstOrCreate(['slug' => $slug], ['name' => $name, 'slug' => $slug]));
        if ($region) {
            $counts['regions']++;
        }
        return $region;
    }

    private function upsertDistrict(string $name, ?int $regionId, callable $write, array &$counts, array &$warnings): ?District
    {
        $slug = Str::slug($name);

        if (!$regionId) {
            $warnings[] = "District '{$name}' found but region context not set. District saved without region mapping.";
        }

        // If region is missing, park under a synthetic region to keep FK integrity (optional).
        // Better: keep region_id nullable by adjusting migration. Here we keep FK required in districts,
        // so we create/use an "Unknown" region when necessary.
        if (!$regionId) {
            $unknown = $write(fn () => Region::firstOrCreate(
                ['slug' => 'unknown'],
                ['name' => 'Unknown', 'slug' => 'unknown']
            ));
            if ($unknown) {
                $counts['regions']++;
                $regionId = $unknown->id;
            }
        }

        $district = $write(fn () => District::firstOrCreate(
            ['region_id' => $regionId, 'slug' => $slug],
            ['name' => $name, 'region_id' => $regionId, 'slug' => $slug]
        ));

        if ($district) {
            $counts['districts']++;
        }

        return $district;
    }

    private function upsertGroup(string $name, callable $write, array &$counts): ?Group
    {
        $slug = Str::slug($name);
        $group = $write(fn () => Group::firstOrCreate(['slug' => $slug], ['name' => $name, 'slug' => $slug]));
        if ($group) {
            $counts['groups']++;
        }
        return $group;
    }

    private function inferHighValue(?string $category): bool
    {
        if (!$category) {
            return false;
        }

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
}
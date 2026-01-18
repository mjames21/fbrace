<?php

namespace App\Console\Commands;

use App\Models\Candidate;
use App\Models\Delegate;
use App\Models\DelegateCandidateStatus;
use App\Models\District;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class UpsertPrincipalDelegatesFromCsv extends Command
{
    protected $signature = 'delegates:upsert-principal
        {csv : Path to CSV file (relative or absolute)}
        {--candidate= : Candidate ID (defaults to principal candidate if exists)}
        {--stance=for : Default stance for matched rows (for|indicative|against)}
        {--confidence=100 : Default confidence (0-100)}
        {--strict : Strict match requires full_name + district match (recommended)}
        {--dry-run : Parse and report only, do not write to DB}';

    protected $description = 'Match delegates by (full name + district) from CSV, update phones/district, and upsert principal candidate status (no creation).';

    public function handle(): int
    {
        $csvArg = (string) $this->argument('csv');
        $csvPath = $this->resolvePath($csvArg);

        if (!File::exists($csvPath)) {
            $this->error("CSV not found at: {$csvPath}");
            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $strict = (bool) $this->option('strict');

        $stance = strtolower(trim((string) $this->option('stance')));
        if (!in_array($stance, ['for', 'indicative', 'against'], true)) {
            $this->error("Invalid --stance={$stance}. Use for|indicative|against");
            return self::FAILURE;
        }

        $confidence = (int) $this->option('confidence');
        $confidence = max(0, min(100, $confidence));

        $candidateId = $this->resolveCandidateId();
        if (!$candidateId) {
            $this->error("No candidate found. Provide --candidate=<id> or mark one candidate as principal.");
            return self::FAILURE;
        }

        $this->info("CSV: {$csvPath}");
        $this->info("Candidate ID: {$candidateId}");
        $this->info("Default stance/confidence: {$stance} / {$confidence}");
        $this->info("Mode: " . ($dryRun ? 'DRY-RUN' : 'WRITE'));
        $this->info("Match: " . ($strict ? 'STRICT (name+district)' : 'SMART (name+district, fallback unique name)'));
        $this->newLine();

        // Build district map: normalized name -> id
        $districtMap = District::query()
            ->get(['id', 'name'])
            ->mapWithKeys(fn ($d) => [$this->normDistrict($d->name) => (int) $d->id])
            ->all();

        // Build delegate index:
        // key = norm(name) | norm(districtNameFromDB)
        $delegates = Delegate::query()
            ->with(['district:id,name'])
            ->get(['id', 'full_name', 'district_id', 'phone_primary', 'phone_secondary']);

        $byKey = [];
        $byName = []; // name -> [delegateIds]
        foreach ($delegates as $del) {
            $nName = $this->normName($del->full_name);

            $distName = $del->district?->name ?? '';
            $nDist = $this->normDistrict($distName);

            $key = $nName . '|' . $nDist;
            $byKey[$key] = (int) $del->id;

            $byName[$nName] ??= [];
            $byName[$nName][] = (int) $del->id;
        }

        $ts = Carbon::now()->format('Ymd_His');
        $outDir = storage_path('app/imports');
        File::ensureDirectoryExists($outDir);

        $matchedPath = "{$outDir}/matched_{$ts}.csv";
        $unmatchedPath = "{$outDir}/unmatched_{$ts}.csv";

        $matchedRows = [];
        $unmatchedRows = [];

        $processed = 0;
        $matched = 0;
        $delegatesUpdated = 0;
        $statusesUpserted = 0;

        $rows = $this->readCsv($csvPath);

        DB::transaction(function () use (
            $rows,
            $districtMap,
            $byKey,
            $byName,
            $candidateId,
            $stance,
            $confidence,
            $dryRun,
            $strict,
            &$processed,
            &$matched,
            &$delegatesUpdated,
            &$statusesUpserted,
            &$matchedRows,
            &$unmatchedRows
        ) {
            foreach ($rows as $row) {
                $processed++;

                $fullName = trim((string)($row['full_name'] ?? ''));
                $district = trim((string)($row['district'] ?? ''));

                // Skip empty lines / heading rows
                if ($fullName === '') {
                    $unmatchedRows[] = $row + ['reason' => 'blank full_name'];
                    continue;
                }

                $nName = $this->normName($fullName);
                $nDist = $this->normDistrict($district);
                $key = $nName . '|' . $nDist;

                $delegateId = $byKey[$key] ?? null;

                // If not strict, allow fallback by unique name
                if (!$delegateId && !$strict) {
                    $ids = $byName[$nName] ?? [];
                    if (count($ids) === 1) {
                        $delegateId = $ids[0];
                    }
                }

                if (!$delegateId) {
                    $unmatchedRows[] = $row + [
                        'reason' => $strict ? 'no match on (name+district)' : 'no match (name+district or unique-name fallback)',
                    ];
                    continue;
                }

                $matched++;

                // Optional phones
                $phone1 = trim((string)($row['phone_primary'] ?? ''));
                $phone2 = trim((string)($row['phone_secondary'] ?? ''));

                $districtIdFromCsv = $districtMap[$nDist] ?? null;

                $delegatePayload = [];
                if ($phone1 !== '') $delegatePayload['phone_primary'] = $phone1;
                if ($phone2 !== '') $delegatePayload['phone_secondary'] = $phone2;
                if ($districtIdFromCsv) $delegatePayload['district_id'] = $districtIdFromCsv;

                if (!$dryRun && !empty($delegatePayload)) {
                    $updated = Delegate::query()->whereKey($delegateId)->update($delegatePayload);
                    if ($updated) $delegatesUpdated += 1;
                }

                // Upsert status for principal candidate
                if (!$dryRun) {
                    $status = DelegateCandidateStatus::query()->firstOrNew([
                        'delegate_id' => $delegateId,
                        'candidate_id' => $candidateId,
                    ]);

                    // If new, set defaults; if existing, overwrite to your requested defaults
                    $status->stance = $stance;
                    $status->confidence = $confidence;
                    $status->last_confirmed_at = now();
                    $status->save();

                    $statusesUpserted += 1;
                }

                $matchedRows[] = $row + [
                    'matched_delegate_id' => $delegateId,
                    'matched_key' => $key,
                ];
            }
        });

        $this->writeCsv($matchedPath, $matchedRows);
        $this->writeCsv($unmatchedPath, $unmatchedRows);

        $this->info("DONE");
        $this->info("Processed rows: {$processed}");
        $this->info("Matched rows: {$matched}");
        $this->info("Delegates updated (phones/district): {$delegatesUpdated}");
        $this->info("Statuses upserted: {$statusesUpserted}");
        $this->info("Matched CSV: {$matchedPath}");
        $this->info("Unmatched CSV: {$unmatchedPath}");

        return self::SUCCESS;
    }

    private function resolveCandidateId(): ?int
    {
        $opt = $this->option('candidate');
        if ($opt !== null && $opt !== '') return (int) $opt;

        // Prefer principal if you have that column
        $principal = Candidate::query()->where('is_principal', true)->first(['id']);
        if ($principal) return (int) $principal->id;

        // fallback: first active candidate
        $active = Candidate::query()->where('is_active', true)->orderBy('sort_order')->first(['id']);
        return $active ? (int) $active->id : null;
    }

    private function resolvePath(string $p): string
    {
        // allow relative to project root
        if (Str::startsWith($p, ['/','\\'])) return $p;
        return base_path($p);
    }

    /**
     * Read CSV as array of associative arrays.
     * Expected headers: full_name, district, phone_primary, phone_secondary (phones optional)
     */
    private function readCsv(string $path): array
    {
        $fh = fopen($path, 'rb');
        if (!$fh) {
            throw new \RuntimeException("Failed to open CSV: {$path}");
        }

        $headers = null;
        $rows = [];

        while (($data = fgetcsv($fh)) !== false) {
            if ($headers === null) {
                $headers = array_map(fn ($h) => trim((string)$h), $data);
                continue;
            }

            $row = [];
            foreach ($headers as $i => $h) {
                $row[$h] = $data[$i] ?? null;
            }

            $rows[] = $row;
        }

        fclose($fh);
        return $rows;
    }

    private function writeCsv(string $path, array $rows): void
    {
        $fh = fopen($path, 'wb');
        if (!$fh) return;

        if (empty($rows)) {
            fputcsv($fh, ['empty']);
            fclose($fh);
            return;
        }

        // union headers
        $headers = [];
        foreach ($rows as $r) {
            foreach (array_keys($r) as $k) $headers[$k] = true;
        }
        $headers = array_keys($headers);

        fputcsv($fh, $headers);

        foreach ($rows as $r) {
            $line = [];
            foreach ($headers as $h) {
                $line[] = $r[$h] ?? '';
            }
            fputcsv($fh, $line);
        }

        fclose($fh);
    }

    private function normName(string $s): string
    {
        $s = Str::of($s)->lower()->trim()->toString();

        // remove common junk (#, numbering, punctuation)
        $s = preg_replace('/[#\d\.\)\(]+/', ' ', $s);
        $s = preg_replace('/[^a-z\s]/', ' ', $s);

        // remove titles
        $s = preg_replace('/\b(hon|mr|mrs|ms|dr|sir|madam|mp)\b/', ' ', $s);

        $s = preg_replace('/\s+/', ' ', $s);
        return trim($s);
    }

    private function normDistrict(string $s): string
    {
        $s = Str::of($s)->lower()->trim()->toString();

        // remove the word "district" so "Kailahun" == "Kailahun District"
        $s = preg_replace('/\bdistrict\b/', ' ', $s);

        // normalize punctuation
        $s = preg_replace('/[^a-z\s]/', ' ', $s);
        $s = preg_replace('/\s+/', ' ', $s);

        return trim($s);
    }
}


/*
# dry run
php artisan delegates:upsert-principal imports/delegates_principal_upsert_all.csv --dry-run

# write
php artisan delegates:upsert-principal imports/delegates_principal_upsert_all.csv

# only Tonkolili
php artisan delegates:upsert-principal imports/delegates_principal_upsert_all.csv --district="Tonkolili"

# custom outputs
php artisan delegates:upsert-principal imports/delegates_principal_upsert_all.csv \
  --matched="imports/matched_principal.csv" \
  --unmatched="imports/unmatched_principal.csv"
*/
<?php
// File: app/Console/Commands/UpsertPrincipalDelegatesFromCsv.php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Candidate;
use App\Models\Delegate;
use App\Models\District;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

final class UpsertPrincipalDelegatesFromCsv extends Command
{
    protected $signature = 'delegates:upsert-principal
        {csv : Path relative to storage/app (e.g. imports/delegates.csv) OR absolute path}
        {--candidate= : Candidate ID (default: principal candidate)}
        {--stance=for : Default stance to set for matched rows (for|indicative|against)}
        {--confidence=100 : Default confidence to set for matched rows (0-100)}
        {--district= : Only process rows whose district matches this (e.g. "Tonkolili")}
        {--unmatched= : Output path relative to storage/app (default: imports/unmatched_TIMESTAMP.csv)}
        {--matched= : Output path relative to storage/app (default: imports/matched_TIMESTAMP.csv)}
        {--dry-run : Do not write changes; only report + export unmatched/matched}
    ';

    protected $description = 'STRICT UPSERT-only: match delegates by (full_name + district) with punctuation-insensitive name normalization; update phones; upsert principal candidate status; export unmatched + matched CSVs.';

    public function handle(): int
    {
        $csvPath = $this->resolveCsvPath((string) $this->argument('csv'));
        if ($csvPath === '' || !File::exists($csvPath)) {
            $this->error("CSV not found: {$csvPath}");
            return self::FAILURE;
        }

        $candidateId = $this->resolveCandidateId();
        if (!$candidateId) {
            $this->error("No principal candidate found. Set candidates.is_principal=1 OR pass --candidate=ID");
            return self::FAILURE;
        }

        $stance = strtolower(trim((string) $this->option('stance')));
        if (!in_array($stance, ['for', 'indicative', 'against'], true)) {
            $this->error("Invalid --stance. Use: for|indicative|against");
            return self::FAILURE;
        }

        $confidence = max(0, min(100, (int) $this->option('confidence')));
        $dryRun = (bool) $this->option('dry-run');

        $districtFilter = (string) ($this->option('district') ?? '');
        $districtFilterNorm = $districtFilter !== '' ? $this->districtKey($this->normDistrict($districtFilter)) : null;

        $unmatchedPath = $this->resolveUnmatchedPath((string) ($this->option('unmatched') ?? ''));
        $matchedPath = $this->resolveMatchedPath((string) ($this->option('matched') ?? ''));

        $districtMap = $this->districtNameToIdMap();
        $now = CarbonImmutable::now();

        $in = new \SplFileObject($csvPath, 'r');
        $in->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY | \SplFileObject::DROP_NEW_LINE);

        $header = null;
        $rowNum = 0;

        $processed = 0;
        $matched = 0;
        $updatedDelegates = 0;
        $upsertedStatuses = 0;
        $unmatched = 0;
        $skippedByDistrict = 0;

        $unmatchedOut = $this->openWriter($unmatchedPath);
        fputcsv($unmatchedOut, [
            'row_number',
            'full_name',
            'district',
            'phone_primary',
            'phone_secondary',
            'reason',
        ]);

        $matchedOut = $this->openWriter($matchedPath);
        fputcsv($matchedOut, [
            'row_number',
            'full_name',
            'district',
            'delegate_id',
            'delegate_updated',
            'status_upserted',
            'phone_primary',
            'phone_secondary',
        ]);

        $this->info("CSV: {$csvPath}");
        $this->info("Candidate ID: {$candidateId}");
        $this->info("Default stance/confidence: {$stance} / {$confidence}");
        if ($districtFilterNorm !== null) {
            $this->info("District filter: {$districtFilter}");
        }
        $this->info("Matched export: {$matchedPath}");
        $this->info("Unmatched export: {$unmatchedPath}");
        $this->info($dryRun ? "Mode: DRY RUN (no writes)" : "Mode: WRITE");

        DB::beginTransaction();

        try {
            while (!$in->eof()) {
                $row = $in->fgetcsv();
                if ($row === false || $row === [null]) {
                    continue;
                }

                $rowNum++;

                if ($header === null) {
                    $header = $this->normalizeHeaderRow($row);
                    continue;
                }

                $data = $this->rowToAssoc($header, $row);

                $rawDistrict = (string) ($data['district'] ?? '');
                $districtName = $this->normDistrict($rawDistrict);
                $districtKey = $districtName !== '' ? $this->districtKey($districtName) : '';

                if ($districtFilterNorm !== null) {
                    if ($districtKey === '' || $districtKey !== $districtFilterNorm) {
                        $skippedByDistrict++;
                        continue;
                    }
                }

                $processed++;

                $rawName = (string) ($data['full_name'] ?? $data['name'] ?? '');
                $nameNormReadable = $this->normName($rawName);
                $nameKey = $this->nameKey($nameNormReadable);

                if ($nameNormReadable === '' || $districtName === '') {
                    $unmatched++;
                    fputcsv($unmatchedOut, [
                        $rowNum,
                        $nameNormReadable,
                        $districtName,
                        (string) ($data['phone_primary'] ?? ''),
                        (string) ($data['phone_secondary'] ?? ''),
                        'missing_full_name_or_district',
                    ]);
                    continue;
                }

                $districtId = $districtMap[$districtKey] ?? null;
                if (!$districtId) {
                    $unmatched++;
                    fputcsv($unmatchedOut, [
                        $rowNum,
                        $nameNormReadable,
                        $districtName,
                        (string) ($data['phone_primary'] ?? ''),
                        (string) ($data['phone_secondary'] ?? ''),
                        'district_not_found',
                    ]);
                    continue;
                }

                // STRICT MATCH: (normalized nameKey) + district_id
                $delegate = Delegate::query()
                    ->whereRaw(
                        "regexp_replace(lower(full_name), '[^a-z0-9]+', '', 'g') = ?",
                        [$nameKey]
                    )
                    ->where('district_id', $districtId)
                    ->first(['id', 'district_id', 'phone_primary', 'phone_secondary']);

                if (!$delegate) {
                    $unmatched++;
                    fputcsv($unmatchedOut, [
                        $rowNum,
                        $nameNormReadable,
                        $districtName,
                        (string) ($data['phone_primary'] ?? ''),
                        (string) ($data['phone_secondary'] ?? ''),
                        'delegate_not_found',
                    ]);
                    continue;
                }

                $matched++;

                $phone1 = $this->normPhone((string) ($data['phone_primary'] ?? $data['phone1'] ?? $data['phone'] ?? ''));
                $phone2 = $this->normPhone((string) ($data['phone_secondary'] ?? $data['phone2'] ?? ''));

                $delegatePayload = [];

                if ((int) $delegate->district_id !== (int) $districtId) {
                    $delegatePayload['district_id'] = $districtId;
                }
                if ($phone1 !== '') {
                    $delegatePayload['phone_primary'] = $phone1;
                }
                if ($phone2 !== '') {
                    $delegatePayload['phone_secondary'] = $phone2;
                }

                $didUpdateDelegate = false;
                if (!empty($delegatePayload)) {
                    $updatedDelegates++;
                    $didUpdateDelegate = true;
                    if (!$dryRun) {
                        Delegate::query()->whereKey((int) $delegate->id)->update($delegatePayload);
                    }
                }

                $finalStance = strtolower(trim((string) ($data['stance'] ?? $stance)));
                if (!in_array($finalStance, ['for', 'indicative', 'against'], true)) {
                    $finalStance = $stance;
                }

                $finalConfidence = $confidence;
                if (isset($data['confidence']) && $data['confidence'] !== '' && $data['confidence'] !== null) {
                    $finalConfidence = max(0, min(100, (int) $data['confidence']));
                }

                $upsertedStatuses++;
                if (!$dryRun) {
                    $this->upsertDelegateCandidateStatus(
                        delegateId: (int) $delegate->id,
                        candidateId: (int) $candidateId,
                        stance: $finalStance,
                        confidence: $finalConfidence,
                        now: $now
                    );
                }

                fputcsv($matchedOut, [
                    $rowNum,
                    $nameNormReadable,
                    $districtName,
                    (int) $delegate->id,
                    $didUpdateDelegate ? 1 : 0,
                    1,
                    $phone1,
                    $phone2,
                ]);
            }

            if ($dryRun) {
                DB::rollBack();
            } else {
                DB::commit();
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error($e->getMessage());
            return self::FAILURE;
        } finally {
            fclose($unmatchedOut);
            fclose($matchedOut);
        }

        $this->newLine();
        $this->info("DONE");
        $this->line("Processed rows: {$processed}" . ($districtFilterNorm ? " (district-filtered; skipped {$skippedByDistrict})" : ""));
        $this->line("Matched rows: {$matched}");
        $this->line("Delegates updated (phones/district): {$updatedDelegates}");
        $this->line("Statuses upserted: {$upsertedStatuses}");
        $this->line("Unmatched rows exported: {$unmatched}");
        $this->line("Matched CSV: {$matchedPath}");
        $this->line("Unmatched CSV: {$unmatchedPath}");

        return self::SUCCESS;
    }

    private function resolveCsvPath(string $arg): string
    {
        $arg = trim($arg);
        if ($arg === '') {
            return '';
        }

        if (File::exists($arg)) {
            return $arg;
        }

        return storage_path('app/' . ltrim($arg, '/'));
    }

    private function resolveCandidateId(): ?int
    {
        $opt = $this->option('candidate');
        if ($opt !== null && $opt !== '') {
            return (int) $opt;
        }

        return Candidate::query()
            ->where('is_principal', true)
            ->orderByDesc('is_active')
            ->orderBy('sort_order')
            ->value('id');
    }

    private function resolveUnmatchedPath(string $relativeOrEmpty): string
    {
        if ($relativeOrEmpty !== '') {
            return storage_path('app/' . ltrim($relativeOrEmpty, '/'));
        }

        $ts = CarbonImmutable::now()->format('Ymd_His');
        return storage_path("app/imports/unmatched_{$ts}.csv");
    }

    private function resolveMatchedPath(string $relativeOrEmpty): string
    {
        if ($relativeOrEmpty !== '') {
            return storage_path('app/' . ltrim($relativeOrEmpty, '/'));
        }

        $ts = CarbonImmutable::now()->format('Ymd_His');
        return storage_path("app/imports/matched_{$ts}.csv");
    }

    /** @return array<string,int> */
    private function districtNameToIdMap(): array
    {
        $map = [];
        District::query()->get(['id', 'name'])->each(function (District $d) use (&$map) {
            $map[$this->districtKey((string) $d->name)] = (int) $d->id;
        });
        return $map;
    }

    private function districtKey(string $name): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', $name) ?? ''));
    }

    /**
     * Name normalization:
     * - lowercase
     * - replace any non [a-z0-9] with spaces
     * - collapse spaces
     */
    private function normName(string $s): string
    {
        $s = mb_strtolower(trim($s));
        $s = preg_replace('/[^a-z0-9]+/u', ' ', $s) ?? '';
        $s = trim(preg_replace('/\s+/u', ' ', $s) ?? '');
        return $s;
    }

    /** DB match key: remove spaces for punctuation-insensitive matching. */
    private function nameKey(string $normalizedName): string
    {
        return str_replace(' ', '', $normalizedName);
    }

    private function normDistrict(string $s): string
    {
        $s = trim(preg_replace('/\s+/', ' ', $s) ?? '');
        return $s;
    }

    private function normPhone(string $s): string
    {
        $s = trim($s);
        if ($s === '') return '';
        return preg_replace('/\s+/', '', $s) ?? '';
    }

    /** @return array<int,string> */
    private function normalizeHeaderRow(array $row): array
    {
        $out = [];
        foreach ($row as $cell) {
            $k = mb_strtolower(trim((string) $cell));
            $k = preg_replace('/[^a-z0-9_]+/', '_', $k) ?? $k;
            $k = trim($k, '_');

            $k = match ($k) {
                'fullname' => 'full_name',
                'full_name' => 'full_name',
                'name' => 'full_name',
                'district_name' => 'district',
                'district' => 'district',
                'phone' => 'phone_primary',
                'phone1' => 'phone_primary',
                'phone_primary' => 'phone_primary',
                'phone2' => 'phone_secondary',
                'phone_secondary' => 'phone_secondary',
                default => $k,
            };

            $out[] = $k;
        }
        return $out;
    }

    /** @return array<string, mixed> */
    private function rowToAssoc(array $header, array $row): array
    {
        $assoc = [];
        foreach ($header as $i => $key) {
            $assoc[$key] = $row[$i] ?? null;
        }
        return $assoc;
    }

    private function openWriter(string $path)
    {
        File::ensureDirectoryExists(dirname($path));
        $fp = fopen($path, 'w');
        if ($fp === false) {
            throw new \RuntimeException("Cannot write file: {$path}");
        }
        return $fp;
    }

    private function upsertDelegateCandidateStatus(int $delegateId, int $candidateId, string $stance, int $confidence, CarbonImmutable $now): void
    {
        $stance = strtolower(trim($stance));
        if (!in_array($stance, ['for', 'indicative', 'against'], true)) {
            $stance = 'for';
        }
        $confidence = max(0, min(100, $confidence));

        $table = 'delegate_candidate_statuses';

        $exists = DB::table($table)
            ->where('delegate_id', $delegateId)
            ->where('candidate_id', $candidateId)
            ->exists();

        if ($exists) {
            DB::table($table)
                ->where('delegate_id', $delegateId)
                ->where('candidate_id', $candidateId)
                ->update([
                    'stance' => $stance,
                    'confidence' => $confidence,
                    'updated_at' => $now,
                ]);
            return;
        }

        DB::table($table)->insert([
            'delegate_id' => $delegateId,
            'candidate_id' => $candidateId,
            'stance' => $stance,
            'confidence' => $confidence,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
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
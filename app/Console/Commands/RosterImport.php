<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RosterImportService;

class RosterImport extends Command
{
    protected $signature = 'roster:import {path : Path to .docx roster file} {--dry-run : Parse only, do not write to DB}';
    protected $description = 'Import roster (regions, districts, groups, delegates) from a Word .docx document';

    public function handle(RosterImportService $importer): int
    {
        $path = (string) $this->argument('path');
        $dryRun = (bool) $this->option('dry-run');

        $result = $importer->import(path: $path, dryRun: $dryRun);

        $this->info("Parsed lines: {$result['lines']}");
        $this->info("Regions upserted: {$result['regions']}");
        $this->info("Districts upserted: {$result['districts']}");
        $this->info("Groups upserted: {$result['groups']}");
        $this->info("Delegates upserted: {$result['delegates']}");
        $this->info("Delegate↔Group links created: {$result['delegate_group_links']}");

        if (!empty($result['warnings'])) {
            $this->warn('Warnings:');
            foreach ($result['warnings'] as $w) {
                $this->warn("- {$w}");
            }
        }

        return self::SUCCESS;
    }
}
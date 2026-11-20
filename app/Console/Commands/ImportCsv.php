<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ImportCsv extends Command
{
    protected $signature = 'rishtahub:import
        {table? : Table name (leave empty when using --all)}
        {--all : Import every table that has a CSV file, in foreign key order}
        {--force : Delete existing rows of the table before importing}
        {--no-fk-checks : Turn off foreign key checks while importing (use when a parent row is missing)}
        {--path=storage/app/import : Folder that holds the CSV files}';

    protected $description = 'Import Supabase CSV exports (<table>_rows.csv) into MySQL';

    // Parents first, children after, so foreign keys always resolve.
    private const ORDER = [
        'plans', 'plan_features', 'profiles', 'profile_images', 'profile_views',
        'shortlists', 'ignored_profiles', 'interests', 'user_quota',
        'conversations', 'conversation_participants', 'messages',
        'message_read_states', 'user_blocks', 'chat_reports',
        'blog_authors', 'blogs', 'audit_logs', 'contact_submissions',
        'faqs', 'notifications', 'success_stories', 'support_tickets',
    ];

    public function handle(): int
    {
        $dir = base_path($this->option('path'));

        if ($this->option('all')) {
            $tables = self::ORDER;
        } elseif ($this->argument('table')) {
            $tables = [$this->argument('table')];
        } else {
            $this->error('Give a table name or use --all');
            return self::FAILURE;
        }

        if ($this->option('no-fk-checks')) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            $this->warn('Foreign key checks are OFF during this import');
        }

        try {
            return $this->importAll($tables, $dir);
        } finally {
            if ($this->option('no-fk-checks')) {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }
        }
    }

    private function importAll(array $tables, string $dir): int
    {
        foreach ($tables as $table) {
            $file = $this->findFile($dir, $table);

            if (! $file) {
                if ($this->option('all')) {
                    $this->line("skip {$table}: no CSV file found");
                    continue;
                }
                $this->error("No CSV for {$table} in {$dir} (expected {$table}_rows.csv)");
                return self::FAILURE;
            }

            if (! $this->importTable($table, $file)) {
                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }

    private function findFile(string $dir, string $table): ?string
    {
        foreach (["{$table}_rows.csv", "{$table}.csv"] as $name) {
            if (is_file($dir . DIRECTORY_SEPARATOR . $name)) {
                return $dir . DIRECTORY_SEPARATOR . $name;
            }
        }

        return null;
    }

    private function importTable(string $table, string $file): bool
    {
        if (! Schema::hasTable($table)) {
            $this->error("Table {$table} does not exist. Run php artisan migrate first.");
            return false;
        }

        $columns = collect(Schema::getColumns($table))->keyBy('name');

        $fh = fopen($file, 'r');
        $header = fgetcsv($fh, 0, ',', '"', '');

        if ($header === false) {
            $this->error("{$table}: CSV is empty");
            fclose($fh);
            return false;
        }

        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);

        $unknown = array_diff($header, $columns->keys()->all());
        if ($unknown) {
            $this->error("{$table}: CSV has columns that are not in MySQL: " . implode(', ', $unknown));
            fclose($fh);
            return false;
        }

        $existing = DB::table($table)->count();
        if ($existing > 0 && ! $this->option('force')) {
            $this->warn("skip {$table}: already has {$existing} rows (use --force to replace them)");
            fclose($fh);
            return true;
        }

        $count = 0;
        $batch = [];

        DB::beginTransaction();

        try {
            if ($existing > 0) {
                DB::table($table)->delete();
            }

            while (($row = fgetcsv($fh, 0, ',', '"', '')) !== false) {
                if ($row === [null]) {
                    continue; // blank line
                }

                $record = [];
                foreach ($header as $i => $name) {
                    $col = $columns[$name];
                    $record[$name] = $this->convert($row[$i] ?? null, $col['type_name'], (bool) $col['nullable']);
                }

                $batch[] = $record;
                $count++;

                if (count($batch) >= 200) {
                    DB::table($table)->insert($batch);
                    $batch = [];
                }
            }

            if ($batch) {
                DB::table($table)->insert($batch);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            fclose($fh);
            $this->error("{$table} failed, nothing was imported: " . substr($e->getMessage(), 0, 400));
            return false;
        }

        fclose($fh);
        $this->info("{$table}: imported {$count} rows");

        return true;
    }

    private function convert(?string $value, string $type, bool $nullable): mixed
    {
        if ($value === null || $value === '') {
            return $nullable ? null : '';
        }

        $type = strtolower($type);

        if ($type === 'tinyint') {
            $lower = strtolower($value);
            if ($lower === 'true' || $lower === 't') {
                return 1;
            }
            if ($lower === 'false' || $lower === 'f') {
                return 0;
            }
            return $value;
        }

        if ($type === 'timestamp' || $type === 'datetime') {
            // Supabase gives "2026-11-02 13:42:35.488925+00"; make the offset "+00:00"
            $value = preg_replace('/([+-]\d{2})$/', '$1:00', $value);
            return Carbon::parse($value)->utc()->format('Y-m-d H:i:s');
        }

        if ($type === 'date') {
            return Carbon::parse($value)->format('Y-m-d');
        }

        return $value;
    }
}

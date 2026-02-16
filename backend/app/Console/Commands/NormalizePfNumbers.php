<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NormalizePfNumbers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:normalize-pf-numbers 
                            {--dry-run : Show what would be changed without making changes}
                            {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Normalize all PF numbers by removing "PF" prefix and cleaning up formatting';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info('');
        $this->info('===========================================');
        $this->info('  PF Number Normalization Tool');
        $this->info('===========================================');
        $this->info('');

        if ($isDryRun) {
            $this->warn('Running in DRY-RUN mode - no changes will be made');
            $this->info('');
        }

        // Get all users with non-null pf_number
        $users = User::whereNotNull('pf_number')
            ->where('pf_number', '!=', '')
            ->get(['id', 'name', 'email', 'pf_number']);

        $this->info("Found {$users->count()} users with PF numbers to check.");
        $this->info('');

        $toUpdate = [];
        $alreadyNormalized = 0;

        foreach ($users as $user) {
            $original = $user->pf_number;
            $normalized = $this->normalizePfNumber($original);

            if ($original !== $normalized) {
                $toUpdate[] = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'original' => $original,
                    'normalized' => $normalized,
                ];
            } else {
                $alreadyNormalized++;
            }
        }

        $this->info("Already normalized: {$alreadyNormalized}");
        $this->info("Need normalization: " . count($toUpdate));
        $this->info('');

        if (empty($toUpdate)) {
            $this->info('✅ All PF numbers are already normalized. Nothing to do.');
            return Command::SUCCESS;
        }

        // Show preview table
        $this->info('Changes to be made:');
        $this->table(
            ['ID', 'Name', 'Email', 'Current PF', 'New PF'],
            array_map(function ($item) {
                return [
                    $item['id'],
                    $item['name'],
                    $item['email'],
                    $item['original'],
                    $item['normalized'],
                ];
            }, $toUpdate)
        );
        $this->info('');

        if ($isDryRun) {
            $this->warn('DRY-RUN complete. Run without --dry-run to apply changes.');
            return Command::SUCCESS;
        }

        // Confirm before making changes
        if (!$force && !$this->confirm('Do you want to proceed with these changes?')) {
            $this->info('Operation cancelled.');
            return Command::SUCCESS;
        }

        // Apply changes
        $this->info('Applying changes...');
        $this->output->progressStart(count($toUpdate));

        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        DB::beginTransaction();

        try {
            foreach ($toUpdate as $item) {
                try {
                    User::where('id', $item['id'])->update([
                        'pf_number' => $item['normalized']
                    ]);
                    $successCount++;
                } catch (\Exception $e) {
                    $errorCount++;
                    $errors[] = [
                        'id' => $item['id'],
                        'name' => $item['name'],
                        'error' => $e->getMessage(),
                    ];
                }
                $this->output->progressAdvance();
            }

            DB::commit();
            $this->output->progressFinish();

            $this->info('');
            $this->info("✅ Successfully normalized: {$successCount}");

            if ($errorCount > 0) {
                $this->error("❌ Errors: {$errorCount}");
                $this->table(
                    ['ID', 'Name', 'Error'],
                    array_map(function ($err) {
                        return [$err['id'], $err['name'], $err['error']];
                    }, $errors)
                );
            }

            Log::info('PF numbers normalized', [
                'total_checked' => $users->count(),
                'already_normalized' => $alreadyNormalized,
                'updated' => $successCount,
                'errors' => $errorCount,
            ]);

            $this->info('');
            $this->info('PF number normalization completed successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('An error occurred. All changes have been rolled back.');
            $this->error($e->getMessage());

            Log::error('PF number normalization failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * Normalize a PF number by removing "PF" prefix and cleaning up formatting.
     *
     * @param string|null $input
     * @return string
     */
    private function normalizePfNumber(?string $input): string
    {
        if (empty($input)) {
            return '';
        }

        $value = trim($input);

        // Remove spaces, dashes, colons
        $value = preg_replace('/[\s\-:]+/', '', $value);

        // Remove "PF" prefix (case-insensitive) if present
        $value = preg_replace('/^pf/i', '', $value);

        // Trim any remaining whitespace
        $value = trim($value);

        return $value;
    }
}

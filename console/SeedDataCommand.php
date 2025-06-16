<?php

declare(strict_types=1);

namespace WebBook\Mall\Console;

use DB;
use Exception;
use Illuminate\Console\Command;
use WebBook\Mall\Classes\Index\Index;
use WebBook\Mall\Classes\Index\Noop;
use WebBook\Mall\Classes\Index\ProductEntry;
use WebBook\Mall\Classes\Index\VariantEntry;
use WebBook\Mall\Updates\Seeders\DemoSeeder;
use WebBook\Mall\Updates\Seeders\MallSeeder;
use System;

class SeedDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     * @var string
     */
    protected $signature = '
        mall:seed 
        {--force : Don\'t ask before erasing all records} 
        {--d|with-demo : Insert demonstration records, such as products}
        {--l|locale= : Force a specific locale for the seeded records} 
    ';

    /**
     * The console command name.
     * @var string
     */
    protected $name = 'mall:seed';

    /**
     * The console command description.
     * @var string|null
     */
    protected $description = 'Seed the Mall related database records.';

    /**
     * Execute the console command.
     * @return void
     */
    public function handle()
    {
        $question = 'All existing WebBook.Mall data will be erased. Do you want to continue?';

        if (!$this->option('force') && !$this->confirm($question, false)) {
            return 0;
        }

        $demo = $this->option('with-demo');
        $question = 'Would you also like to import the demo content?';

        if (!$this->option('force') && !$demo && $this->confirm($question, false)) {
            $demo = true;
        }

        // Force locale
        $locale = $this->option('locale');

        if (!empty($locale)) {
            app()->setLocale($locale);
        }

        // Use a Noop-Indexer so no unnecessary queries are run during seeding.
        // The index will be re-built once everything is done.
        $originalIndex = app(Index::class);
        app()->bind(Index::class, fn () => new Noop());

        // Reset Mall Plugin
        $this->output->newLine();
        $this->warn(' Resetting Mall Plugin...');

        try {
            $this->cleanup();
            $this->info('Reset successful.');
        } catch (Exception $exc) {
            $this->output->block('The following error occurred.', 'ERROR', 'fg=red');
            $this->error($exc->getMessage());

            return 0;
        }
        $this->output->newLine();

        // Seed core records
        $this->warn(' Seed core database records...');

        try {
            if (version_compare(System::VERSION, '3.0', '<')) {
                app()->call(MallSeeder::class);
            } else {
                $this->callSilent('plugin:seed', [
                    'namespace' => 'WebBook.Mall',
                    'class'     => 'WebBook\Mall\Updates\Seeders\MallSeeder',
                ]);
            }
            $this->info('Seed core records successful.');
        } catch (Exception $exc) {
            $this->output->block('The following error occurred.', 'ERROR', 'fg=red');
            $this->error($exc->getMessage());

            return 0;
        }
        $this->output->newLine();

        // Seed demo records
        if ($demo) {
            $this->warn(' Seed demo database records...');

            try {
                if (version_compare(System::VERSION, '3.0', '<')) {
                    app()->call(DemoSeeder::class);
                } else {
                    $this->callSilent('plugin:seed', [
                        'namespace' => 'WebBook.Mall',
                        'class'     => 'WebBook\Mall\Updates\Seeders\DemoSeeder',
                    ]);
                }
                $this->info('Seed demo records successful.');
            } catch (Exception $exc) {
                $this->output->block('The following error occurred.', 'ERROR', 'fg=red');
                $this->error($exc->getMessage() . "\n" . $exc->getFile());

                return 0;
            }
            $this->output->newLine();
        }

        // Re-Index all products
        $this->warn(' Re-Create products index...');

        try {
            app()->bind(Index::class, fn () => $originalIndex);
            $this->callSilent('mall:index', ['--force' => true]);
            $this->info('Re-Index products successful.');
        } catch (Exception $exc) {
            $this->output->block('The following error occurred.', 'ERROR', 'fg=red');
            $this->error($exc->getMessage() . "\n" . $exc->getFile());

            return 0;
        }
        $this->output->newLine();

        // Finish
        $this->alert('Ready to go, happy selling!');
    }

    /**
     * Cleanup and Reset Mall Plugin
     * @return void
     */
    protected function cleanup()
    {
        try {
            if (version_compare(System::VERSION, '3.0', '<')) {
                $this->callSilent('plugin:refresh', [
                    'name'          => 'WebBook.Mall',
                    '--force'       => true,
                    '--quiet'       => true,
                ]);
            } else {
                $this->callSilent('plugin:refresh', [
                    'namespace'     => 'WebBook.Mall',
                    '--force'       => true,
                    '--quiet'       => true,
                ]);
            }
            $this->callSilent('cache:clear', []);

            // Clean Database
            DB::table('system_files')
                ->where('attachment_type', 'LIKE', 'WebBook%Mall%')
                ->orWhere('attachment_type', 'LIKE', 'mall.%')
                ->delete();

            // Clean Indexes
            $index = app(Index::class);
            $index->drop(ProductEntry::INDEX);
            $index->drop(VariantEntry::INDEX);
        } catch (Exception $exc) {
            $this->error($exc->getMessage());
        }
    }
}

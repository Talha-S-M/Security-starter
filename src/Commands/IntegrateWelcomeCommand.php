<?php

namespace Pitbphp\Security\Commands;

use Illuminate\Console\Command;

class IntegrateWelcomeCommand extends Command
{
    protected $signature = 'security:integrate-welcome';

    protected $description = 'Overwrite Laravel welcome page with PITB Security dashboard links';

    public function handle(): int
    {
        $path = base_path('resources/views/welcome.blade.php');

        if (! is_file($path)) {
            $this->error("Welcome view not found at: {$path}");

            return self::FAILURE;
        }

        $template = "@include('security::home')";

        if (file_put_contents($path, $template) === false) {
            $this->error('Unable to write changes to welcome view.');

            return self::FAILURE;
        }

        $this->info('Overwrote welcome page with PITB Security dashboard.');
        $this->line('Visit / to access security links and /security for package home route.');

        return self::SUCCESS;
    }
}

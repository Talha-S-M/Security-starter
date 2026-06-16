<?php

namespace Pitbphp\Security\Commands;

use Illuminate\Console\Command;

class IntegrateWelcomeCommand extends Command
{
    protected $signature = 'security:integrate-welcome {--force : Insert include even if fallback placement is needed}';

    protected $description = 'Inject the PITB Security header include into resources/views/welcome.blade.php';

    public function handle(): int
    {
        $path = base_path('resources/views/welcome.blade.php');

        if (! is_file($path)) {
            $this->error("Welcome view not found at: {$path}");

            return self::FAILURE;
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            $this->error('Unable to read welcome view.');

            return self::FAILURE;
        }

        $include = "@include('security::partials.header')";

        if (str_contains($contents, $include)) {
            $this->info('Welcome page is already integrated.');

            return self::SUCCESS;
        }

        $injected = $this->injectAfterBodyTag($contents, $include);

        if ($injected === null) {
            if (! $this->option('force')) {
                $this->warn('Could not find a <body> tag. Re-run with --force to prepend the include.');

                return self::FAILURE;
            }

            $injected = $include.PHP_EOL.PHP_EOL.$contents;
        }

        if (file_put_contents($path, $injected) === false) {
            $this->error('Unable to write changes to welcome view.');

            return self::FAILURE;
        }

        $this->info('Integrated PITB Security header into welcome page.');
        $this->line('Visit /security for the package home route and permission-aware navigation.');

        return self::SUCCESS;
    }

    protected function injectAfterBodyTag(string $contents, string $include): ?string
    {
        if (! preg_match('/<body[^>]*>/i', $contents, $matches, PREG_OFFSET_CAPTURE)) {
            return null;
        }

        $bodyTag = $matches[0][0];
        $start = $matches[0][1];
        $offset = $start + strlen($bodyTag);

        return substr($contents, 0, $offset)
            .PHP_EOL.'    '.$include.PHP_EOL
            .substr($contents, $offset);
    }
}

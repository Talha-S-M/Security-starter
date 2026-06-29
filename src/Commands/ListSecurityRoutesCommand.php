<?php

namespace Pitbphp\Security\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Pitbphp\Security\Support\SecurityRequest;
use Pitbphp\Security\Support\SecurityRouteCatalog;

class ListSecurityRoutesCommand extends Command
{
    protected $signature = 'security:routes
                            {--json : Output machine-readable JSON}
                            {--group= : Filter by group (api auth, api security, web auth, web security, sanctum)}';

    protected $description = 'List PITB Security routes for the current mode (API / web / hybrid)';

    public function handle(): int
    {
        $catalog = SecurityRouteCatalog::entries();
        $groupFilter = $this->option('group')
            ? strtolower((string) $this->option('group'))
            : null;

        $rows = [];

        foreach ($catalog as $entry) {
            if ($groupFilter !== null && ! str_contains(strtolower($entry['group']), $groupFilter)) {
                continue;
            }

            $registered = $entry['name'] !== '' && Route::has($entry['name']);
            $uri = $registered ? $this->resolveUri($entry['name']) : $entry['path'];

            $rows[] = [
                'group' => $entry['group'],
                'method' => $entry['method'],
                'uri' => $uri,
                'name' => $entry['name'],
                'auth' => ($entry['auth'] ?? false) ? 'yes' : 'no',
                'status' => $registered ? 'registered' : 'catalog',
                'description' => $entry['description'],
            ];
        }

        if ($rows === []) {
            $this->warn('No routes matched. Current mode: '.SecurityRequest::mode());

            return self::SUCCESS;
        }

        if ($this->option('json')) {
            $this->line(json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->line('PITB Security routes (mode: '.SecurityRequest::mode().')');
        $this->newLine();
        $this->table(
            ['Group', 'Method', 'URI', 'Route name', 'Auth', 'Status', 'Description'],
            array_map(static fn (array $row) => [
                $row['group'],
                $row['method'],
                $row['uri'],
                $row['name'],
                $row['auth'],
                $row['status'],
                $row['description'],
            ], $rows)
        );

        $this->newLine();
        $this->line('Published reference: routes/pitb-security/README.md');
        $this->line('Customize route files in routes/pitb-security/ (loaded instead of package defaults when present).');

        return self::SUCCESS;
    }

    protected function resolveUri(string $name): string
    {
        $route = Route::getRoutes()->getByName($name);

        if (! $route) {
            return '';
        }

        $uri = '/'.ltrim($route->uri(), '/');

        return $uri === '/' ? '/' : $uri;
    }
}

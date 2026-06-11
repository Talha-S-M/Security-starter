<?php



namespace Pitbphp\Security;



use Illuminate\Auth\Events\Failed;

use Illuminate\Auth\Events\Login;

use Illuminate\Auth\Events\Logout;

use Illuminate\Auth\Events\PasswordReset;

use Illuminate\Console\Scheduling\Schedule;

use Illuminate\Routing\Router;

use Illuminate\Support\ServiceProvider;

use Pitbphp\Security\Auditing\ActivityLogAuditLogger;

use Pitbphp\Security\Auditing\NullAuditLogger;

use Pitbphp\Security\Auditing\OwenItAuditLogger;

use Pitbphp\Security\Commands\DisableInactiveUsersCommand;

use Pitbphp\Security\Commands\InstallSecurityCommand;

use Pitbphp\Security\Commands\NotifyAccessReviewCommand;

use Pitbphp\Security\Commands\NotifyLogReviewCommand;

use Pitbphp\Security\Commands\PruneSecurityLogsCommand;

use Pitbphp\Security\Commands\RecordAccessReviewCommand;

use Pitbphp\Security\Commands\RecordInactiveReviewCommand;

use Pitbphp\Security\Commands\RecordLogReviewCommand;

use Pitbphp\Security\Contracts\AuditLoggerInterface;

use Pitbphp\Security\Listeners\LogAuthenticationEvents;

use Pitbphp\Security\Middleware\CheckAccountStatus;

use Pitbphp\Security\Middleware\EnforcePasswordExpiry;

use Pitbphp\Security\Middleware\EnforceSessionTimeout;

use Pitbphp\Security\Middleware\EnforceTokenTimeout;

use Pitbphp\Security\Middleware\ForceHttps;

use Pitbphp\Security\Middleware\RequireMfa;

use Pitbphp\Security\Support\SecurityRequest;



class SecurityServiceProvider extends ServiceProvider

{

    public function register(): void

    {

        $this->mergeConfigFrom(__DIR__.'/../config/security.php', 'security');



        $this->app->singleton(AuditLoggerInterface::class, function () {

            return match (config('security.auditing.driver', 'activitylog')) {

                'auditing' => new OwenItAuditLogger(),

                'activitylog' => new ActivityLogAuditLogger(),

                default => new NullAuditLogger(),

            };

        });

    }



    public function boot(): void

    {

        $this->publishes([

            __DIR__.'/../config/security.php' => config_path('security.php'),

        ], 'security-config');



        $this->publishes([

            __DIR__.'/../resources/views/password' => resource_path('views/vendor/security/password'),

            __DIR__.'/../resources/views/mfa' => resource_path('views/vendor/security/mfa'),

        ], 'security-views');



        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if (in_array(SecurityRequest::mode(), ['web', 'hybrid'], true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/security.php');
        }

        if (SecurityRequest::isApiEnabled()) {
            $this->loadRoutesFrom(__DIR__.'/../routes/security-api.php');
        }



        $this->loadViewsFrom(__DIR__.'/../resources/views', 'security');



        $publishedViews = resource_path('views/vendor/security');



        if (is_dir($publishedViews)) {

            $this->loadViewsFrom($publishedViews, 'security');

        }



        $this->registerMiddleware();

        $this->registerCommands();

        $this->registerEventListeners();

        $this->registerSchedule();

    }



    protected function registerMiddleware(): void

    {

        /** @var Router $router */

        $router = $this->app['router'];



        foreach (config('security.middleware.aliases', []) as $alias => $class) {

            $router->aliasMiddleware($alias, $class);

        }



        $mode = SecurityRequest::mode();



        if (in_array($mode, ['web', 'hybrid'], true) && config('security.middleware.auto_apply_web', true)) {

            $this->pushMiddlewareGroup($router, 'web', $this->webMiddlewareStack());

        }



        if (in_array($mode, ['api', 'hybrid'], true) && config('security.api.auto_apply_middleware', true)) {

            $this->pushMiddlewareGroup($router, config('security.api.middleware_group', 'api'), $this->apiMiddlewareStack());

        }

    }



    /**

     * @return array<int, class-string>

     */

    protected function webMiddlewareStack(): array

    {

        $stack = [

            ForceHttps::class,

            CheckAccountStatus::class,

            EnforceSessionTimeout::class,

            EnforcePasswordExpiry::class,

        ];



        if (config('security.mfa.enabled')) {

            $stack[] = RequireMfa::class;

        }



        return $stack;

    }



    /**

     * @return array<int, class-string>

     */

    protected function apiMiddlewareStack(): array

    {

        $stack = [

            ForceHttps::class,

            CheckAccountStatus::class,

            EnforceTokenTimeout::class,

            EnforcePasswordExpiry::class,

        ];



        if (config('security.mfa.enabled')) {

            $stack[] = RequireMfa::class;

        }



        return $stack;

    }



    protected function pushMiddlewareGroup(Router $router, string $group, array $stack): void

    {

        foreach ($stack as $middleware) {

            $router->pushMiddlewareToGroup($group, $middleware);

        }

    }



    protected function registerCommands(): void

    {

        if (! $this->app->runningInConsole()) {

            return;

        }



        $this->commands([

            InstallSecurityCommand::class,

            DisableInactiveUsersCommand::class,

            NotifyAccessReviewCommand::class,

            NotifyLogReviewCommand::class,

            PruneSecurityLogsCommand::class,

            RecordAccessReviewCommand::class,

            RecordInactiveReviewCommand::class,

            RecordLogReviewCommand::class,

        ]);

    }



    protected function registerEventListeners(): void

    {

        $listener = LogAuthenticationEvents::class;



        $this->app['events']->listen(Login::class, [$listener, 'handleLogin']);

        $this->app['events']->listen(Failed::class, [$listener, 'handleFailed']);

        $this->app['events']->listen(Logout::class, [$listener, 'handleLogout']);

        $this->app['events']->listen(PasswordReset::class, [$listener, 'handlePasswordReset']);

    }



    protected function registerSchedule(): void

    {

        $this->app->booted(function () {

            $schedule = $this->app->make(Schedule::class);



            if (config('security.notifications.access_review.enabled')) {

                $schedule->command('security:notify-access-review')

                    ->cron(config('security.notifications.access_review.reminder_cron'));

            }



            if (config('security.notifications.log_review.enabled')) {

                $schedule->command('security:notify-log-review')

                    ->cron(config('security.notifications.log_review.reminder_cron'));

            }



            if (config('security.notifications.inactive_accounts.enabled')) {

                $schedule->command('security:disable-inactive-users --notify')

                    ->cron(config('security.notifications.inactive_accounts.reminder_cron'));

            }



            $schedule->command('security:prune-logs')->monthly();

        });

    }

}



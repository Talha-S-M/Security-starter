<?php



namespace Pitbphp\Security;



use Illuminate\Auth\Events\Failed;

use Illuminate\Auth\Events\Login;

use Illuminate\Auth\Events\Logout;

use Illuminate\Auth\Events\PasswordReset;

use Illuminate\Auth\Events\Registered;

use Illuminate\Console\Scheduling\Schedule;

use Illuminate\Routing\Router;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

use Pitbphp\Security\Auditing\ActivityLogAuditLogger;

use Pitbphp\Security\Auditing\NullAuditLogger;

use Pitbphp\Security\Auditing\OwenItAuditLogger;

use Pitbphp\Security\Commands\DisableInactiveUsersCommand;

use Pitbphp\Security\Commands\IntegrateWelcomeCommand;

use Pitbphp\Security\Commands\ListSecurityRoutesCommand;
use Pitbphp\Security\Commands\InstallSecurityCommand;

use Pitbphp\Security\Commands\NotifyAccessReviewCommand;

use Pitbphp\Security\Commands\NotifyLogReviewCommand;

use Pitbphp\Security\Commands\PublishVendorConfigCommand;
use Pitbphp\Security\Support\VendorConfigAligner;
use Pitbphp\Security\Commands\PruneSecurityLogsCommand;

use Pitbphp\Security\Commands\RecordAccessReviewCommand;

use Pitbphp\Security\Commands\RecordInactiveReviewCommand;

use Pitbphp\Security\Commands\SeedRbacCommand;
use Pitbphp\Security\Commands\SecurityDoctorCommand;

use Pitbphp\Security\Commands\RecordLogReviewCommand;

use Pitbphp\Security\Contracts\AuditLoggerInterface;
use Pitbphp\Security\Contracts\SmsGatewayInterface;

use Pitbphp\Security\Listeners\AssignDefaultRole;
use Pitbphp\Security\Listeners\LogAuthorizationEvents;
use Pitbphp\Security\Listeners\LogAuthenticationEvents;

use Pitbphp\Security\Middleware\CheckAccountStatus;

use Pitbphp\Security\Middleware\EnforcePasswordExpiry;

use Pitbphp\Security\Middleware\EnforceSessionTimeout;

use Pitbphp\Security\Middleware\EnforceTokenTimeout;

use Pitbphp\Security\Middleware\ForceHttps;

use Pitbphp\Security\Middleware\RequireMfa;
use Pitbphp\Security\Middleware\RequireMfaSetup;

use Pitbphp\Security\Services\LogSmsGateway;
use Pitbphp\Security\Services\PitbSmsGateway;
use Pitbphp\Security\Services\SecurityEventLogger;

use Pitbphp\Security\Support\SecurityLog;
use Pitbphp\Security\Support\RouteLoader;
use Pitbphp\Security\Support\SecurityRoutePublisher;
use Pitbphp\Security\Support\SecurityRequest;



class SecurityServiceProvider extends ServiceProvider

{

    public function register(): void

    {

        $this->mergeConfigFrom(__DIR__.'/../config/security.php', 'security');
        $this->mergeConfigFrom(__DIR__.'/../config/captcha.php', 'captcha');

        $this->app->booting(function () {
            VendorConfigAligner::apply();
        });



        $this->app->singleton(AuditLoggerInterface::class, function () {

            return match (config('security.auditing.driver', 'activitylog')) {

                'auditing' => new OwenItAuditLogger(),

                'activitylog' => new ActivityLogAuditLogger(),

                default => new NullAuditLogger(),

            };

        });

        $this->app->singleton(SmsGatewayInterface::class, function () {
            return match (config('security.sms.driver', 'pitb')) {
                'log' => new LogSmsGateway(),
                default => new PitbSmsGateway(),
            };
        });

        $this->app->singleton(SecurityEventLogger::class);

    }



    public function boot(): void

    {

        $this->publishes([

            __DIR__.'/../config/security.php' => config_path('security.php'),

        ], 'security-config');

        $this->publishes([

            __DIR__.'/../config/captcha.php' => config_path('captcha.php'),

        ], 'security-captcha-config');



        $this->publishes([

            __DIR__.'/../resources/views/password' => resource_path('views/vendor/security/password'),

            __DIR__.'/../resources/views/mfa' => resource_path('views/vendor/security/mfa'),

            __DIR__.'/../resources/views/auth' => resource_path('views/vendor/security/auth'),

            __DIR__.'/../resources/views/partials' => resource_path('views/vendor/security/partials'),

            __DIR__.'/../resources/views/home.blade.php' => resource_path('views/vendor/security/home.blade.php'),

            __DIR__.'/../resources/views/admin' => resource_path('views/vendor/security/admin'),

        ], 'security-views');

        $this->publishes([

            __DIR__.'/../resources/views/admin' => resource_path('views/vendor/security/admin'),

        ], 'security-admin-views');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations/pitb_security'),
        ], 'security-migrations');

        $this->publishes([
            __DIR__.'/../resources/assets/js/pitb-password-strength.js' => public_path('vendor/pitb-security/js/pitb-password-strength.js'),
            __DIR__.'/../resources/assets/js/pitb-temporary-password.js' => public_path('vendor/pitb-security/js/pitb-temporary-password.js'),
        ], 'security-assets');

        $this->publishes(SecurityRoutePublisher::publishMapForMode('api'), 'security-routes-api');
        $this->publishes(SecurityRoutePublisher::publishMapForMode('web'), 'security-routes-web');
        $this->publishes(SecurityRoutePublisher::publishMapForMode('hybrid'), 'security-routes-hybrid');

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if (in_array(SecurityRequest::mode(), ['web', 'hybrid'], true)) {
            if (config('security.auth.enabled', true)) {
                $this->loadRoutesFrom(RouteLoader::path('auth.php'));
            }

            $this->loadRoutesFrom(RouteLoader::path('security.php'));

            if (config('security.admin.enabled', true)) {
                $this->loadRoutesFrom(RouteLoader::path('security-admin.php'));
            }
        }

        if (SecurityRequest::isApiEnabled()) {
            $this->loadRoutesFrom(RouteLoader::path('security-api.php'));

            if (config('security.api.auth.enabled', true) && config('security.auth.enabled', true)) {
                $this->loadRoutesFrom(RouteLoader::path('security-api-auth.php'));
            }
        }

        if (SecurityRequest::isWebEnabled()) {
            $this->loadViewsFrom(__DIR__.'/../resources/views', 'security');

            $publishedViews = resource_path('views/vendor/security');

            if (is_dir($publishedViews)) {
                $this->loadViewsFrom($publishedViews, 'security');
            }
        }



        $this->registerMiddleware();

        $this->registerCommands();

        $this->registerEventListeners();

        $this->registerAuthorizationLogging();

        $this->registerSchedule();

    }



    protected function registerMiddleware(): void

    {

        /** @var Router $router */

        $router = $this->app['router'];



        foreach (config('security.middleware.aliases', []) as $alias => $class) {

            $router->aliasMiddleware($alias, $class);

        }

        if (config('security.permissions.enabled', true) && class_exists(\Spatie\Permission\Middleware\RoleMiddleware::class)) {
            $router->aliasMiddleware('role', \Spatie\Permission\Middleware\RoleMiddleware::class);
            $router->aliasMiddleware('permission', \Spatie\Permission\Middleware\PermissionMiddleware::class);
            $router->aliasMiddleware('role_or_permission', \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class);
        } elseif (config('security.permissions.enabled', true) && class_exists(\Spatie\Permission\Middlewares\RoleMiddleware::class)) {
            $router->aliasMiddleware('role', \Spatie\Permission\Middlewares\RoleMiddleware::class);
            $router->aliasMiddleware('permission', \Spatie\Permission\Middlewares\PermissionMiddleware::class);
            $router->aliasMiddleware('role_or_permission', \Spatie\Permission\Middlewares\RoleOrPermissionMiddleware::class);
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
            $stack[] = RequireMfaSetup::class;
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
            $stack[] = RequireMfaSetup::class;
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

            ListSecurityRoutesCommand::class,

            IntegrateWelcomeCommand::class,

            SecurityDoctorCommand::class,

            SeedRbacCommand::class,

            DisableInactiveUsersCommand::class,

            NotifyAccessReviewCommand::class,

            NotifyLogReviewCommand::class,

            PruneSecurityLogsCommand::class,

            PublishVendorConfigCommand::class,

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

        if (config('security.permissions.enabled', true)) {
            $rbacListener = LogAuthorizationEvents::class;

            $this->app['events']->listen(\Spatie\Permission\Events\RoleAttached::class, [$rbacListener, 'handleRoleAttached']);
            $this->app['events']->listen(\Spatie\Permission\Events\RoleDetached::class, [$rbacListener, 'handleRoleDetached']);
            $this->app['events']->listen(\Spatie\Permission\Events\PermissionAttached::class, [$rbacListener, 'handlePermissionAttached']);
            $this->app['events']->listen(\Spatie\Permission\Events\PermissionDetached::class, [$rbacListener, 'handlePermissionDetached']);

            $this->app['events']->listen(Registered::class, AssignDefaultRole::class);
        }

    }

    protected function registerAuthorizationLogging(): void

    {

        if (! config('security.permissions.enabled', true) || ! config('security.auditing.log_authorization_denials', true)) {

            return;

        }

        Gate::after(function ($user, string $ability, ?bool $result, array $arguments = []) {

            if ($result !== false || ! $user) {

                return;

            }

            SecurityLog::authorization('authorization.denied', false, $user, [

                'ability' => $ability,

                'arguments' => $arguments,

            ]);

        });

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



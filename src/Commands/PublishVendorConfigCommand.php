<?php

namespace Pitbphp\Security\Commands;

use Illuminate\Console\Command;
use Pitbphp\Security\Support\VendorConfigPublisher;

class PublishVendorConfigCommand extends Command
{
    protected $signature = 'security:publish-vendor-config
                            {--driver= : Auditing driver context: activitylog, auditing, or none}
                            {--force : Overwrite existing vendor config files}';

    protected $description = 'Publish captcha, permission, and auditing dependency config files';

    public function handle(): int
    {
        $driver = $this->option('driver')
            ?: config('security.auditing.driver', 'activitylog');

        if (! in_array($driver, ['activitylog', 'auditing', 'none'], true)) {
            $this->error('Invalid --driver. Use activitylog, auditing, or none.');

            return self::FAILURE;
        }

        VendorConfigPublisher::publish($this, $driver, (bool) $this->option('force'));

        $this->info('Vendor config publish complete. Feature toggles are driven by config/security.php.');

        return self::SUCCESS;
    }
}

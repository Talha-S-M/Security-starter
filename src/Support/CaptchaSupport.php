<?php

namespace Pitbphp\Security\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class CaptchaSupport
{
    public static function apply(): void
    {
        if (! config()->has('captcha.disable')) {
            return;
        }

        $disk = Storage::disk('public');
        $disk->makeDirectory('captcha/backgrounds');

        self::copyVendorBackgroundsIfMissing($disk);

        $fontsPath = base_path('vendor/mews/captcha/assets/fonts');

        config([
            'captcha.bgsDirectory' => $disk->path('captcha/backgrounds'),
            'captcha.flat.bgImage' => false,
            'captcha.default.bgImage' => false,
        ]);

        if (is_dir($fontsPath)) {
            config(['captcha.fontsDirectory' => $fontsPath]);
        }
    }

    protected static function copyVendorBackgroundsIfMissing($disk): void
    {
        if ($disk->files('captcha/backgrounds') !== []) {
            return;
        }

        $vendorPath = base_path('vendor/mews/captcha/assets/backgrounds');

        if (! is_dir($vendorPath)) {
            return;
        }

        foreach (File::files($vendorPath) as $file) {
            $disk->put(
                'captcha/backgrounds/'.$file->getFilename(),
                file_get_contents($file->getPathname())
            );
        }
    }
}

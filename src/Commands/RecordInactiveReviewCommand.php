<?php

namespace Pitbphp\Security\Commands;

use Illuminate\Console\Command;
use Pitbphp\Security\Commands\Concerns\ResolvesSecurityReviewer;
use Pitbphp\Security\Models\SecurityReview;
use Pitbphp\Security\Support\SecurityLog;

class RecordInactiveReviewCommand extends Command
{
    use ResolvesSecurityReviewer;

    protected $signature = 'security:record-inactive-review
                            {--notes= : Summary of the inactive accounts review}
                            {--user= : User ID of reviewer}';

    protected $description = 'Record evidence that inactive accounts were reviewed';

    public function handle(): int
    {
        $performer = $this->resolvePerformer();

        if (! $performer) {
            $this->error('No reviewer specified. Use --user=ID.');

            return self::FAILURE;
        }

        $review = SecurityLog::recordReview(
            SecurityReview::TYPE_INACTIVE,
            $performer,
            $this->option('notes'),
            ['recorded_via' => 'artisan']
        );

        $this->info("Inactive accounts review recorded (ID: {$review->id}).");

        return self::SUCCESS;
    }
}

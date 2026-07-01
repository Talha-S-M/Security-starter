<?php

namespace Pitbphp\Security\Commands;

use Illuminate\Console\Command;
use Pitbphp\Security\Commands\Concerns\ResolvesSecurityReviewer;
use Pitbphp\Security\Models\SecurityReview;
use Pitbphp\Security\Support\SecurityLog;

class RecordLogReviewCommand extends Command
{
    use ResolvesSecurityReviewer;

    protected $signature = 'security:record-log-review
                            {--notes= : Summary of the log review performed}
                            {--user= : User ID of reviewer}';

    protected $description = 'Record evidence that a manual daily log review was performed';

    public function handle(): int
    {
        $performer = $this->resolvePerformer();

        if (! $performer) {
            $this->error('No reviewer specified. Use --user=ID.');

            return self::FAILURE;
        }

        $review = SecurityLog::recordReview(
            SecurityReview::TYPE_LOG,
            $performer,
            $this->option('notes'),
            ['recorded_via' => 'artisan']
        );

        $this->info("Log review recorded (ID: {$review->id}).");

        return self::SUCCESS;
    }
}

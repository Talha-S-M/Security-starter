<?php

namespace Pitbphp\Security\Commands;

use Illuminate\Console\Command;
use Pitbphp\Security\Commands\Concerns\ResolvesSecurityReviewer;
use Pitbphp\Security\Models\SecurityReview;
use Pitbphp\Security\Support\SecurityLog;

class RecordAccessReviewCommand extends Command
{
    use ResolvesSecurityReviewer;

    protected $signature = 'security:record-access-review
                            {--notes= : Summary of the access review performed}
                            {--user= : User ID of reviewer (defaults to authenticated user)}';

    protected $description = 'Record evidence that a manual access review was performed';

    public function handle(): int
    {
        $performer = $this->resolvePerformer();

        if (! $performer) {
            $this->error('No reviewer specified. Use --user=ID or run as an authenticated user.');

            return self::FAILURE;
        }

        $review = SecurityLog::recordReview(
            SecurityReview::TYPE_ACCESS,
            $performer,
            $this->option('notes'),
            ['recorded_via' => 'artisan']
        );

        $this->info("Access review recorded (ID: {$review->id}).");

        return self::SUCCESS;
    }
}

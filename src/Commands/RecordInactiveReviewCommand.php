<?php

namespace Pitbphp\Security\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Pitbphp\Security\Models\SecurityReview;
use Pitbphp\Security\Services\SecurityEventLogger;

class RecordInactiveReviewCommand extends Command
{
    protected $signature = 'security:record-inactive-review
                            {--notes= : Summary of the inactive accounts review}
                            {--user= : User ID of reviewer}';

    protected $description = 'Record evidence that inactive accounts were reviewed';

    public function handle(SecurityEventLogger $logger): int
    {
        $performer = $this->resolvePerformer();

        if (! $performer) {
            $this->error('No reviewer specified. Use --user=ID.');

            return self::FAILURE;
        }

        $review = $logger->recordReview(
            SecurityReview::TYPE_INACTIVE,
            $performer,
            $this->option('notes'),
            ['recorded_via' => 'artisan']
        );

        $this->info("Inactive accounts review recorded (ID: {$review->id}).");

        return self::SUCCESS;
    }

    protected function resolvePerformer()
    {
        if ($userId = $this->option('user')) {
            return $this->findUser($userId);
        }

        return Auth::user();
    }

    protected function findUser(int|string $userId)
    {
        $model = config('security.user.model');

        return (new $model)->newQuery()->find($userId);
    }
}

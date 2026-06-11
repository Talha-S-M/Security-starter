<?php

namespace Pitbphp\Security\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Pitbphp\Security\Models\SecurityReview;
use Pitbphp\Security\Services\SecurityEventLogger;

class RecordLogReviewCommand extends Command
{
    protected $signature = 'security:record-log-review
                            {--notes= : Summary of the log review performed}
                            {--user= : User ID of reviewer}';

    protected $description = 'Record evidence that a manual daily log review was performed';

    public function handle(SecurityEventLogger $logger): int
    {
        $performer = $this->resolvePerformer();

        if (! $performer) {
            $this->error('No reviewer specified. Use --user=ID.');

            return self::FAILURE;
        }

        $review = $logger->recordReview(
            SecurityReview::TYPE_LOG,
            $performer,
            $this->option('notes'),
            ['recorded_via' => 'artisan']
        );

        $this->info("Log review recorded (ID: {$review->id}).");

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

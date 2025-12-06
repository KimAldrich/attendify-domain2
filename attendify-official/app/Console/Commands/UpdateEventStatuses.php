<?php

namespace App\Console\Commands;

use App\Models\Event;
use Illuminate\Console\Command;

class UpdateEventStatuses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'events:update-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto-update event statuses based on start and end times.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $now = now();

        $publishedToOngoing = Event::query()
            ->where('status', 'published')
            ->whereNotNull('start_at')
            ->where('start_at', '<=', $now)
            ->update([
                'status'     => 'ongoing',
                'updated_at' => $now,
            ]);

        $ongoingToFinished = Event::query()
            ->where('status', 'ongoing')
            ->whereNotNull('end_at')
            ->where('end_at', '<=', $now)
            ->update([
                'status'     => 'finished',
                'updated_at' => $now,
            ]);

        $this->info("Events moved to ongoing: {$publishedToOngoing}");
        $this->info("Events moved to finished: {$ongoingToFinished}");

        if ($publishedToOngoing === 0 && $ongoingToFinished === 0) {
            $this->comment('No status changes needed.');
        }

        return Command::SUCCESS;
    }
}

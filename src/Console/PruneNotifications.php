<?php

namespace IlBronza\Notifications\Console;

use IlBronza\Notifications\ExtendedDatabaseNotification;
use Illuminate\Console\Command;

class PruneNotifications extends Command
{
    protected $signature = 'notifications:prune {--days=90 : Minimum age of managed notifications} {--dry-run : Count without deleting}';
    protected $description = 'Remove fully managed notification groups older than the selected retention period.';

    public function handle(): int
    {
        $days = filter_var($this->option('days'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($days === false) { $this->error('--days must be a positive integer.'); return self::FAILURE; }
        $cutoff = now()->subDays($days);
        $count = 0;
        ExtendedDatabaseNotification::query()->whereNull('parent_id')->where('managed_at', '<', $cutoff)
            ->whereDoesntHave('children', fn ($q) => $q->whereNull('managed_at')->orWhere('managed_at', '>=', $cutoff))
            ->chunkById(100, function ($roots) use (&$count, $cutoff) {
                foreach ($roots as $root) {
                    $count += $root->getConnection()->transaction(function () use ($root, $cutoff) {
                        $root = ExtendedDatabaseNotification::whereKey($root->id)->lockForUpdate()->first();
                        if (! $root || $root->parent_id || ! $root->managed_at || $root->managed_at->gte($cutoff)) { return 0; }
                        $children = $root->children()->lockForUpdate()->get();
                        if ($children->contains(fn ($child) => ! $child->managed_at || $child->managed_at->gte($cutoff))) { return 0; }
                        $eligible = $children->count() + 1;
                        if (! $this->option('dry-run')) {
                            $children->each->delete();
                            $root->delete();
                        }
                        return $eligible;
                    });
                }
            });
        $this->info(($this->option('dry-run') ? 'Would remove: ' : 'Removed: ').$count);
        return self::SUCCESS;
    }
}

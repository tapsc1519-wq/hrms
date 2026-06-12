<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\Organization;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('billing:sync-status {--dry-run : Show affected organizations without updating them}', function () {
    $dryRun = (bool) $this->option('dry-run');
    $today = now()->toDateString();

    $expiredTrials = Organization::where('billing_status', 'trial')
        ->whereNotNull('trial_ends_at')
        ->whereDate('trial_ends_at', '<', $today)
        ->get();

    $expiredSubscriptions = Organization::where('billing_status', 'active')
        ->whereNotNull('subscription_ends_at')
        ->whereDate('subscription_ends_at', '<', $today)
        ->get();

    $this->info('Billing status sync');
    $this->line('Expired trials: ' . $expiredTrials->count());
    $this->line('Expired paid subscriptions: ' . $expiredSubscriptions->count());

    if ($dryRun) {
        $rows = $expiredTrials->map(fn ($org) => [$org->id, $org->name, 'Trial expired', $org->trial_ends_at?->format('d-m-Y')])
            ->merge($expiredSubscriptions->map(fn ($org) => [$org->id, $org->name, 'Subscription expired', $org->subscription_ends_at?->format('d-m-Y')]));

        if ($rows->isNotEmpty()) {
            $this->table(['ID', 'Organization', 'Reason', 'Date'], $rows->all());
        }

        $this->warn('Dry run only. No records were updated.');
        return 0;
    }

    $expiredTrials->each(function (Organization $organization) {
        $organization->forceFill(['billing_status' => 'overdue'])->save();
    });

    $expiredSubscriptions->each(function (Organization $organization) {
        $organization->forceFill(['billing_status' => 'overdue'])->save();
    });

    $this->info('Updated organizations: ' . ($expiredTrials->count() + $expiredSubscriptions->count()));
    return 0;
})->purpose('Mark expired trials and paid subscriptions as overdue');

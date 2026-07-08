<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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

Artisan::command('platform:sync-data {--dry-run : Show what would be copied without writing to the platform database}', function () {
    $dryRun = (bool) $this->option('dry-run');
    $sourceConnection = env('DB_CONNECTION', config('database.default', 'mysql'));
    $targetConnection = config('database.platform_connection', 'platform');

    $tables = [
        'products',
        'organization_product_subscriptions',
        'partners',
        'partner_commissions',
    ];

    $sourceDatabase = DB::connection($sourceConnection)->getDatabaseName();
    $targetDatabase = DB::connection($targetConnection)->getDatabaseName();

    $this->info('Platform data sync');
    $this->line("Source: {$sourceConnection} / {$sourceDatabase}");
    $this->line("Target: {$targetConnection} / {$targetDatabase}");

    if ($sourceDatabase === $targetDatabase) {
        $this->warn('Source and target are the same database. Nothing was copied.');
        return 0;
    }

    $rows = [];

    foreach ($tables as $table) {
        $sourceExists = Schema::connection($sourceConnection)->hasTable($table);
        $targetExists = Schema::connection($targetConnection)->hasTable($table);

        if (!$sourceExists || !$targetExists) {
            $rows[] = [$table, $sourceExists ? 'yes' : 'missing', $targetExists ? 'yes' : 'missing', '-', 'skipped'];
            continue;
        }

        $sourceCount = DB::connection($sourceConnection)->table($table)->count();
        $targetCount = DB::connection($targetConnection)->table($table)->count();
        $rows[] = [$table, $sourceCount, $targetCount, $sourceCount, $dryRun ? 'dry-run' : 'copy'];

        if ($dryRun || $sourceCount === 0) {
            continue;
        }

        DB::connection($sourceConnection)
            ->table($table)
            ->orderBy('id')
            ->chunkById(200, function ($records) use ($targetConnection, $table) {
                $payload = $records
                    ->map(fn ($record) => (array) $record)
                    ->all();

                DB::connection($targetConnection)
                    ->table($table)
                    ->upsert($payload, ['id']);
            });
    }

    $this->table(['Table', 'Source', 'Target Before', 'To Copy', 'Action'], $rows);

    if ($dryRun) {
        $this->warn('Dry run only. No records were copied.');
    } else {
        $this->info('Platform data sync complete.');
    }

    return 0;
})->purpose('Copy platform data from the current product database into the configured platform database');

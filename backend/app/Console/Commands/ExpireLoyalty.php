<?php

// Responsable de prise en main : Yedidia ; relecture croisee, attribution non assimilee a une contribution deja realisee.

namespace App\Console\Commands;

use App\Services\LoyaltyService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class ExpireLoyalty extends Command
{
    protected $signature = 'app:expire-loyalty';

    protected $description = 'Enregistrer les expirations sans effacer le journal des points.';

    public function handle(LoyaltyService $loyalty): int
    {
        DB::table('loyalty_accounts')->orderBy('id')->chunkById(100, function ($accounts) use ($loyalty): void {
            foreach ($accounts as $account) {
                DB::transaction(function () use ($account, $loyalty): void {
                    DB::table('loyalty_programs')->where('id', $account->program_id)->lockForUpdate()->first();
                    $loyalty->account($account->program_id, $account->user_id);
                    $loyalty->expire($account->id);
                });
            }
        });
        $this->info('Expirations enregistrées.');

        return self::SUCCESS;
    }
}

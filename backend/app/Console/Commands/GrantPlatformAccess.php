<?php

// Responsable de prise en main : Ryan ; relecture croisee, attribution non assimilee a une contribution deja realisee.

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Audit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class GrantPlatformAccess extends Command
{
    protected $signature = 'app:platform-access {email} {--revoke}';

    protected $description = 'Attribuer ou révoquer le rôle de plateforme pour un compte existant, depuis le serveur.';

    public function handle(): int
    {
        $user = User::where('email', Str::lower(trim($this->argument('email'))))->first();
        if (! $user) {
            $this->error('Inscrivez d’abord ce compte dans l’application.');

            return self::FAILURE;
        }
        if (! $this->confirm('Modifier les droits de plateforme de ce compte ?')) {
            return self::FAILURE;
        }
        DB::transaction(function () use ($user): void {
            DB::table('platform_grants')->updateOrInsert(['user_id' => $user->id], [
                'active' => ! $this->option('revoke'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            Audit::record($user->id, null, 'platform.access_cli', (string) $user->id);
        });
        $this->info('Droits de plateforme mis à jour.');

        return self::SUCCESS;
    }
}

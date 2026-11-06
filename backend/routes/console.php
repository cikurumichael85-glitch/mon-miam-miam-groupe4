<?php

// Responsable de prise en main : Yedidia ; relecture croisee, attribution non assimilee a une contribution deja realisee.

use Illuminate\Support\Facades\Schedule;

// Le serveur de production devra déclencher le planificateur Laravel chaque minute.
Schedule::command('app:expire-loyalty')->daily()->withoutOverlapping();
Schedule::command('app:reconcile-payments')->everyFiveMinutes()->withoutOverlapping();

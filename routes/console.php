<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('nyleo:reminders')->dailyAt('09:00')->timezone('Europe/Paris');
Schedule::command('queue:prune-failed --hours=720')->weekly();

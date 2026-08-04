<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('orders:release-expired')->everyMinute()->withoutOverlapping();

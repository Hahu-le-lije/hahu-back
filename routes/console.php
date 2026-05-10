<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('sync:game-events')
    ->everyMinute();

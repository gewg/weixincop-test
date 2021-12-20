<?php

namespace App\Console;

use App\Jobs\SyncTeacher;
use App\Jobs\DeletTser;
use function foo\func;
use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [

    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function () {
            $syncTeacher = new SyncTeacher();
            $syncTeacher->handle();
        })->name('SyncTeacher')->withoutOverlapping();
//        $schedule->call(function () {
//            $Delete = new DeletTser();
//            $Delete->handle();
//        })->name('DeleteTeacher');
    }
}

<?php

namespace App\Console;

use App\Feedback;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\DB;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
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
            $feedbacks = Feedback::truncate();

            DB::table('feedback')->insert([
                'word' => "keren",
                'count' => 5,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
            DB::table('feedback')->insert([
                'word' => 'top',
                'count' => 3,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
            DB::table('feedback')->insert([
                'word' => 'cool',
                'count' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        })->weekly();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}

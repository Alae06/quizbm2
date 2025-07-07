<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Quiz;
use App\Policies\QuizPolicy;

class AppServiceProvider extends ServiceProvider
{

protected $policies = [
    Quiz::class => QuizPolicy::class,
];
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

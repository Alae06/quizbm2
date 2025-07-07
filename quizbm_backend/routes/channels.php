<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Quiz;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Public channel for quiz leaderboards
Broadcast::channel('quiz.{quizId}', function ($user, $quizId) {
    // This is a public channel, so we can just return true.
    // Or, we could verify that the quiz exists.
    return Quiz::where('id', $quizId)->exists();
}); 
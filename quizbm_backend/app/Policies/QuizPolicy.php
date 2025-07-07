<?php
// app/Policies/QuizPolicy.php

namespace App\Policies;

use App\Models\Quiz;
use App\Models\User;

class QuizPolicy
{
    public function view(User $user, Quiz $quiz)
    {
        return $user->id === $quiz->user_id;
    }

    public function update(User $user, Quiz $quiz)
    {
        return $user->id === $quiz->user_id;
    }

    public function delete(User $user, Quiz $quiz)
    {
        return $user->id === $quiz->user_id;
    }
}
<?php
// routes/api.php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GameController;
use App\Http\Controllers\Api\PublicQuizController;
use App\Http\Controllers\Api\QuizController;
use App\Http\Controllers\Api\QuestionController;
use App\Http\Controllers\Api\StatisticsController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

// Routes publiques
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/quizzes/public', [App\Http\Controllers\Api\PublicQuizController::class, 'index']);
Route::get('/quizzes/public/{slug}', [PublicQuizController::class, 'show']);
Route::post('/quizzes/public/{slug}/attempt', [GameController::class, 'startOrContinueAttempt']);
Route::post('/quizzes/public/{slug}/submit', [PublicQuizController::class, 'submitAttempt']);
// Routes authentifiées
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Only creators can manage quizzes and questions
    Route::middleware(\App\Http\Middleware\IsCreatorMiddleware::class)->group(function () {
        // CRUD Quiz
        Route::apiResource('quizzes', QuizController::class);

        // Questions
        Route::apiResource('quizzes.questions', QuestionController::class)->shallow();
    });

    // Statistiques (dashboard)
    Route::get('/statistics/dashboard', [StatisticsController::class, 'dashboard']);
});
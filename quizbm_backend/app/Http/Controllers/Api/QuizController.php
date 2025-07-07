<?php
// app/Http/Controllers/Api/QuizController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;


class QuizController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $quizzes = Auth::user()->quizzes()
            ->withCount(['questions', 'quizAttempts'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($quizzes);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'max_attempts' => 'sometimes|integer|min:1|max:10|nullable',
            'time_per_question' => 'sometimes|integer|min:5|max:300|nullable',
        ]);

        $quiz = Auth::user()->quizzes()->create([
            'title' => $validatedData['title'],
            'description' => $validatedData['description'],
            'max_attempts' => $validatedData['max_attempts'] ?? 1,
            'time_per_question' => $validatedData['time_per_question'] ?? 30,
            'slug' => Str::random(8),
        ]);

        return response()->json($quiz, 201);
    }

    public function show(Quiz $quiz)
    {
        $this->authorize('view', $quiz);

        $quiz->load(['questions.choices', 'quizAttempts' => function($query) {
            $query->orderBy('score', 'desc')->take(10);
        }]);

        return response()->json($quiz);
    }

    public function update(Request $request, Quiz $quiz)
    {
        $this->authorize('update', $quiz);

        $validatedData = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'max_attempts' => 'sometimes|integer|min:1|max:10|nullable',
            'time_per_question' => 'sometimes|integer|min:5|max:300|nullable',
        ]);

        $quiz->update($validatedData);

        return response()->json($quiz);
    }

    public function destroy(Quiz $quiz)
    {
        $this->authorize('delete', $quiz);

        $quiz->delete();

        return response()->json(['message' => 'Quiz deleted successfully']);
    }
}
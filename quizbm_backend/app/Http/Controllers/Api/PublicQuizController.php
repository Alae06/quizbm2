<?php
// app/Http/Controllers/Api/PublicQuizController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Events\ScoreUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class PublicQuizController extends Controller
{
    public function show($slug)
    {
        $quiz = Quiz::where('slug', $slug)
            ->with(['questions' => function($query) {
                $query->with(['choices' => function($choiceQuery) {
                    // Ne pas exposer is_correct aux participants
                    $choiceQuery->select('id', 'question_id', 'text');
                }]);
            }])
            ->firstOrFail();

        // Retourner les informations du quiz sans les bonnes réponses
        return response()->json([
            'id' => $quiz->id,
            'title' => $quiz->title,
            'slug' => $quiz->slug,
            'description' => $quiz->description,
            'time_per_question' => $quiz->time_per_question,
            'max_attempts' => $quiz->max_attempts,
            'questions' => $quiz->questions->map(function($question) use ($quiz) {
                return [
                    'id' => $question->id,
                    'question_text' => $question->question_text,
                    'type' => $question->type,
                    'time_per_question' => $question->time_per_question ?? $quiz->time_per_question,
                    'choices' => $question->choices
                ];
            })
        ]);
    }

    public function verifyPin(Request $request, Quiz $quiz)
    {
        $request->validate([
            'pin' => 'required|string'
        ]);

        if (!$quiz->pin || !Hash::check($request->pin, $quiz->pin)) {
            return response()->json([
                'message' => 'Invalid PIN'
            ], 422);
        }

        return response()->json([
            'message' => 'PIN verified successfully'
        ]);
    }

    public function submitAttempt(Request $request, $slug)
    {
        $quiz = Quiz::where('slug', $slug)->firstOrFail();

        $request->validate([
            'participant_name' => 'required|string|max:255',
            'participant_email' => 'nullable|email|max:255',
            'answers' => 'required|array',
            'answers.*' => 'required|integer|exists:choices,id',
            'time_taken' => 'required|integer|min:1',
        ]);

        $attemptQuery = QuizAttempt::where('quiz_id', $quiz->id);
        if ($request->participant_email) {
            $attemptQuery->where('participant_email', $request->participant_email);
        } else {
            $attemptQuery->where('ip_address', $request->ip());
        }

        if ($attemptQuery->count() >= $quiz->max_attempts) {
            return response()->json(['message' => 'Maximum attempts reached'], 422);
        }

        $score = $this->calculateScore($quiz, $request->answers);

        $attempt = QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'participant_name' => $request->participant_name,
            'participant_email' => $request->participant_email,
            'ip_address' => $request->ip(),
            'score' => $score['percentage'],
            'time_taken' => $request->time_taken,
        ]);

        foreach ($request->answers as $questionId => $choiceId) {
            $attempt->answers()->create([
                'question_id' => $questionId,
                'choice_id' => $choiceId,
            ]);
        }
        
        broadcast(new ScoreUpdated($quiz, $attempt));

        return response()->json([
            'attempt_id' => $attempt->id,
            'score' => $score['percentage'],
            'correct_answers' => $score['correct'],
            'total_questions' => $score['total'],
            'time_taken' => $request->time_taken,
            'message' => 'Quiz completed successfully!',
            'feedback' => $score['feedback']
        ]);
    }

    private function calculateScore($quiz, $answers)
    {
        $questions = $quiz->questions()->with('choices')->get();
        $correctAnswers = 0;
        $totalQuestions = $questions->count();
        $feedback = [];

        foreach ($questions as $question) {
            $participantAnswer = $answers[$question->id] ?? null;
            $correctChoice = $question->choices()->where('is_correct', true)->first();
            
            $isCorrect = false;
            $participantChoiceText = null;
            $correctChoiceText = null;
            
            if ($participantAnswer) {
                $participantChoice = $question->choices()->where('id', $participantAnswer)->first();
                $participantChoiceText = $participantChoice ? $participantChoice->text : null;
                
                if ($correctChoice && $correctChoice->id == $participantAnswer) {
                    $correctAnswers++;
                    $isCorrect = true;
                }
            }
            
            $correctChoiceText = $correctChoice ? $correctChoice->text : null;
            
            $feedback[] = [
                'question_id' => $question->id,
                'question_text' => $question->question_text,
                'is_correct' => $isCorrect,
                'participant_answer' => $participantChoiceText,
                'correct_answer' => $correctChoiceText
            ];
        }

        $percentage = $totalQuestions > 0 ? round(($correctAnswers / $totalQuestions) * 100, 2) : 0;

        return [
            'correct' => $correctAnswers,
            'total' => $totalQuestions,
            'percentage' => $percentage,
            'feedback' => $feedback
        ];
    }

    public function getLeaderboard($slug)
    {
        $quiz = Quiz::where('slug', $slug)->firstOrFail();
        
        $leaderboard = QuizAttempt::where('quiz_id', $quiz->id)
            ->orderBy('score', 'desc')
            ->orderBy('time_taken', 'asc')
            ->take(10)
            ->get(['participant_name', 'score', 'time_taken', 'created_at']);

        return response()->json($leaderboard);
    }

    public function index()
    {
        $quizzes = \App\Models\Quiz::withCount('questions')
            ->orderBy('created_at', 'desc')
            ->get(['id', 'title', 'description', 'slug', 'created_at']);

        return response()->json($quizzes);
    }
}
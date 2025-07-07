<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Support\Facades\Hash;
use App\Events\ScoreUpdated;

class GameController extends Controller
{
    public function show($slug)
    {
        $quiz = Quiz::where('slug', $slug)
            ->with(['questions.choices' => function($query) {
                $query->select('id', 'question_id', 'text'); // Pas de is_correct
            }])
            ->firstOrFail();
            
        return response()->json($quiz);
    }

    /**
     * Calculate the score based on user's answers and the quiz.
     *
     * @param array $answers
     * @param \App\Models\Quiz $quiz
     * @return int
     */
    protected function calculateScore($answers, $quiz)
    {
        $score = 0;
        foreach ($quiz->questions as $question) {
            if (isset($answers[$question->id])) {
                $selectedChoiceId = $answers[$question->id];
                foreach ($question->choices as $choice) {
                    if ($choice->id == $selectedChoiceId && isset($choice->is_correct) && $choice->is_correct) {
                        $score++;
                        break;
                    }
                }
            }
        }
        return $score;
    }
    
    public function submit(Request $request, $slug)
    {
        $quiz = Quiz::where('slug', $slug)->firstOrFail();
        
        // Calcul du score
        $score = $this->calculateScore($request->answers, $quiz);
        
        // Enregistrement de la tentative
        $attempt = QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'participant_name' => $request->participant_name,
            'participant_email' => $request->participant_email,
            'ip_address' => $request->ip(),
        ]);
        
        // WebSocket pour scoreboard live
        broadcast(new ScoreUpdated($quiz->id, $attempt))->toOthers();
        
        return response()->json(['score' => $score, 'attempt_id' => $attempt->id]);
    }
}

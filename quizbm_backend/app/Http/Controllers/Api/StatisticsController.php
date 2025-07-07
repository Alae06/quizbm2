<?php
// app/Http/Controllers/Api/StatisticsController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class StatisticsController extends Controller
{
    use AuthorizesRequests;
    public function show(Quiz $quiz)
    {
        $this->authorize('view', $quiz);

        $attempts = $quiz->attempts();
        
        $stats = [
            'total_attempts' => $attempts->count(),
            'average_score' => round($attempts->avg('score'), 2),
            'median_score' => $this->getMedianScore($attempts->pluck('score')->sort()->values()),
            'best_score' => $attempts->max('score'),
            'worst_score' => $attempts->min('score'),
            'average_time' => round($attempts->avg('time_taken'), 2),
            'fastest_time' => $attempts->min('time_taken'),
            'slowest_time' => $attempts->max('time_taken'),
        ];

        // Données pour le graphique (7 derniers jours)
        $chartData = $this->getChartData($quiz);

        // Top 10 des participants
        $topParticipants = $attempts->orderBy('score', 'desc')
            ->orderBy('time_taken', 'asc')
            ->take(10)
            ->get(['participant_name', 'score', 'time_taken', 'created_at']);

        // Statistiques par question
        $questionStats = $this->getQuestionStats($quiz);

        return response()->json([
            'general_stats' => $stats,
            'chart_data' => $chartData,
            'top_participants' => $topParticipants,
            'question_stats' => $questionStats,
        ]);
    }

    private function getMedianScore($scores)
    {
        $count = $scores->count();
        if ($count === 0) return 0;
        
        if ($count % 2 === 0) {
            return ($scores[$count/2 - 1] + $scores[$count/2]) / 2;
        } else {
            return $scores[floor($count/2)];
        }
    }

    private function getChartData($quiz)
    {
        $data = [];
        $startDate = Carbon::now()->subDays(6);

        for ($i = 0; $i < 7; $i++) {
            $date = $startDate->copy()->addDays($i);
            $dayAttempts = $quiz->attempts()
                ->whereDate('created_at', $date)
                ->get();

            $data[] = [
                'date' => $date->format('Y-m-d'),
                'attempts' => $dayAttempts->count(),
                'average_score' => $dayAttempts->count() > 0 ? round($dayAttempts->avg('score'), 2) : 0,
            ];
        }

        return $data;
    }

    private function getQuestionStats($quiz)
    {
        $questions = $quiz->questions()->with(['choices', 'answers'])->get();
        $stats = [];

        foreach ($questions as $question) {
            $totalAnswers = $question->answers->count();
            $correctAnswers = 0;

            foreach ($question->answers as $answer) {
                $choice = $question->choices()->find($answer->selected_answer);
                if ($choice && $choice->is_correct) {
                    $correctAnswers++;
                }
            }

            $stats[] = [
                'question_id' => $question->id,
                'question_text' => $question->question_text,
                'total_answers' => $totalAnswers,
                'correct_answers' => $correctAnswers,
                'success_rate' => $totalAnswers > 0 ? round(($correctAnswers / $totalAnswers) * 100, 2) : 0,
            ];
        }

        return $stats;
    }
}
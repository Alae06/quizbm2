<?php
// app/Http/Controllers/Api/QuestionController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Models\Quiz;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class QuestionController extends Controller
{
    use AuthorizesRequests;

    public function index(Quiz $quiz)
    {
        $this->authorize('view', $quiz);
        
        $questions = $quiz->questions()->with('choices')->get();
        
        return response()->json($questions);
    }

    public function store(Request $request, Quiz $quiz)
    {
        $this->authorize('update', $quiz);

        $validatedData = $request->validate([
            'question_text' => 'required|string|max:1000',
            'type' => ['required', Rule::in(['multiple_choice', 'true_false'])],
            'time_per_question' => 'nullable|integer|min:5|max:300',
            'choices' => 'required|array|min:2|max:6',
            'choices.*.text' => 'required|string|max:500',
            'choices.*.is_correct' => 'required|boolean',
        ]);

        $correctAnswers = collect($validatedData['choices'])->where('is_correct', true)->count();
        if ($correctAnswers === 0) {
            return response()->json(['message' => 'At least one correct answer is required'], 422);
        }

        if ($validatedData['type'] === 'true_false' && count($validatedData['choices']) !== 2) {
            return response()->json(['message' => 'True/False questions must have exactly two choices.'], 422);
        }

        $question = $quiz->questions()->create([
            'question_text' => $validatedData['question_text'],
            'type' => $validatedData['type'],
            'time_per_question' => $validatedData['time_per_question'],
        ]);

        foreach ($validatedData['choices'] as $choiceData) {
            $question->choices()->create($choiceData);
        }

        $question->load('choices');

        return response()->json($question, 201);
    }

    public function show(Question $question)
    {
        $this->authorize('view', $question->quiz);
        
        $question->load('choices');
        
        return response()->json($question);
    }

    public function update(Request $request, Question $question)
    {
        $this->authorize('update', $question->quiz);

        // Debug: Log the incoming request
        \Log::info('Question Update Request:', $request->all());

        $validatedData = $request->validate([
            'question_text' => 'sometimes|required|string|max:1000',
            'type' => ['sometimes', 'required', Rule::in(['multiple_choice', 'true_false'])],
            'time_per_question' => 'nullable|integer|min:5|max:300',
            'choices' => 'sometimes|required|array|min:2|max:6',
            'choices.*.text' => 'required|string|max:500',
            'choices.*.is_correct' => 'required|boolean',
        ]);

        // Debug: Log the validated data
        \Log::info('Validated Question Data:', $validatedData);

        $question->update($request->only(['question_text', 'type', 'time_per_question']));

        if ($request->has('choices')) {
            $question->choices()->delete();
            
            // Debug: Log what choices we're creating
            \Log::info('Creating choices:', $validatedData['choices']);
            
            foreach ($validatedData['choices'] as $choiceData) {
                $question->choices()->create($choiceData);
            }
        }

        $question->load('choices');

        // Debug: Log the final result
        \Log::info('Final question with choices:', $question->toArray());

        return response()->json($question);
    }

    public function destroy(Question $question)
    {
        $this->authorize('delete', $question->quiz);
        
        $question->delete();

        return response()->json(['message' => 'Question deleted successfully']);
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Statistics;

class Quiz extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'description', 'max_attempts', 'time_per_question', 'slug', 'user_id'];
    
    public function user() { return $this->belongsTo(User::class); }
    public function questions() { return $this->hasMany(Question::class); }
    public function quizAttempts() { return $this->hasMany(QuizAttempt::class); }
    public function statistics() { return $this->hasOne(Statistics::class); }
}

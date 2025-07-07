<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Quiz;
use App\Models\QuizAttempt;

class ScoreUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $quiz;
    public $attempt;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Quiz $quiz, QuizAttempt $attempt)
    {
        $this->quiz = $quiz;
        $this->attempt = $attempt;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new Channel('quiz.' . $this->quiz->id);
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'score.updated';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'participant_name' => $this->attempt->participant_name,
            'score' => $this->attempt->score,
            'time_taken' => $this->attempt->time_taken,
            'created_at' => $this->attempt->created_at->toDateTimeString(),
        ];
    }
}

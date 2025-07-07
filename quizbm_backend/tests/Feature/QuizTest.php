<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

class QuizTest extends TestCase
{
public function test_authenticated_user_can_create_quiz()
{
    $user = User::factory()->create();
    
    $response = $this->actingAs($user)->postJson('/api/quizzes', [
        'title' => 'Test Quiz',
        'description' => 'A test quiz',
        'max_attempts' => 3,
        'time_per_question' => 30,
    ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('quizzes', ['title' => 'Test Quiz']);
}
    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}

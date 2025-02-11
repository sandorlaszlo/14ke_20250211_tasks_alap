<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     */
    public function test_public_user_cannot_access_tasks(): void
    {
        $response = $this->getJson('/api/tasks');
        $response->assertStatus(401);

        $response = $this->postJson('/api/tasks');
        $response->assertStatus(401);

        $user = User::factory()->create();
        $task = Task::factory()->create();

        $response = $this->getJson('/api/tasks/'. $task->id);
        $response->assertStatus(401);

        $response = $this->putJson('/api/tasks/'. $task->id);
        $response->assertStatus(401);

        $response = $this->deleteJson('/api/tasks/'. $task->id);
        $response->assertStatus(401);
    }

    public function test_authorized_user_can_create_task(): void 
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->postJson('/api/tasks', [
                'name' => 'Test Task',
            ]);
        
        $response->assertStatus(422);

        $response = $this
            ->actingAs($user)
            ->postJson('/api/tasks', [
                'name' => 'Test Task',
                'description' => 'Test Description',
                'priority' => 'invalid_priority'
            ]);
        
        $response->assertStatus(422);

        $response = $this
            ->actingAs($user)
            ->postJson('/api/tasks', [
                'name' => 'Test Task',
                'description' => 'Test Description',
                'priority' => 'low'
            ]);
        
        $response->assertStatus(201);
        $response->assertJsonFragment([
            'name' => 'Test Task'
        ]);
    }

    public function test_non_admin_user_cannot_access_others_tasks(): void {
        $nonAdminUser = User::factory()->create([
            'is_admin' => false
        ]);

        $adminUser = User::factory()->create([
            'is_admin' => true
        ]);

        $task = Task::factory()->create([
            'user_id' => $adminUser->id
        ]);

        $response = $this
            ->actingAs($nonAdminUser)
            ->getJson('/api/tasks/' . $task->id);
        
        $response->assertStatus(403);
    }

    public function test_non_admin_user_can_access_own_tasks(): void {
        $nonAdminUser = User::factory()->create([
            'is_admin' => false
        ]);

        $task = Task::factory()->create([
            'user_id' => $nonAdminUser->id
        ]);

        $response = $this
            ->actingAs($nonAdminUser)
            ->getJson('/api/tasks/' . $task->id);
        
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'name' => $task->name
        ]);
    }

    public function test_admin_user_can_access_all_tasks(): void {
        $nonAdminUser = User::factory()->create([
            'is_admin' => false
        ]);

        $adminUser = User::factory()->create([
            'is_admin' => true
        ]);

        $task = Task::factory()->create([
            'user_id' => $adminUser->id
        ]);

        $task = Task::factory()->create([
            'user_id' => $nonAdminUser->id
        ]);


        $response = $this
            ->actingAs($adminUser)
            ->getJson('/api/tasks/');
        
        $response->assertStatus(200);
        $response->assertJsonCount(2);
    }
}

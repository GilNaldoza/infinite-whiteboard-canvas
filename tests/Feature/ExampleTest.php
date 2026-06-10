<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_redirects_to_boards_list(): void
    {
        $this->get('/')
            ->assertRedirect(route('boards.index'));
    }

    public function test_board_api_can_create_update_and_delete_a_board(): void
    {
        $create = $this->postJson('/api/boards', [
            'name' => 'Planning Board',
            'canvas_data' => '{"className":"Stage"}',
        ]);

        $create->assertCreated()
            ->assertJsonPath('name', 'Planning Board');

        $boardId = $create->json('id');

        $this->postJson('/api/boards', [
            'name' => 'Planning Board',
            'canvas_data' => '{}',
        ])->assertUnprocessable();

        $this->putJson("/api/boards/{$boardId}", [
            'name' => 'Renamed Board',
            'canvas_data' => '{"className":"Stage","children":[]}',
        ])->assertOk()
            ->assertJsonPath('name', 'Renamed Board');

        $this->deleteJson("/api/boards/{$boardId}")
            ->assertNoContent();

        $this->assertDatabaseMissing('boards', [
            'id' => $boardId,
        ]);
    }
}

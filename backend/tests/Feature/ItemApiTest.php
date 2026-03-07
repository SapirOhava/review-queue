<?php

namespace Tests\Feature;

use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_item_with_computed_risk_score(): void
    {
        $payload = [
            'title' => 'Suspicious offer',
            'content' => 'BUY NOW free money https://a.com https://b.com',
        ];

        $response = $this->postJson('/api/items', $payload);

        // 201 Created = a new item was successfully created.
        $response->assertStatus(201);

        $response->assertJsonStructure([
            'item' => [
                'id',
                'title',
                'content',
                'state',
                'risk_score',
                'created_at',
                'updated_at',
                'suggested_action',
            ],
        ]);

        $this->assertDatabaseHas('items', [
            'title' => 'Suspicious offer',
            'state' => 'pending',
        ]);

        $item = Item::first();

        $this->assertNotNull($item);
        $this->assertGreaterThan(0, $item->risk_score);
    }

    public function test_it_reviews_an_item_and_saves_note(): void
    {
        $item = Item::create([
            'title' => 'Test item',
            'content' => 'Normal content',
            'state' => 'pending',
            'risk_score' => 0,
        ]);

        $response = $this->postJson("/api/items/{$item->id}/review", [
            'action' => 'approve',
            'note' => 'Looks good',
        ]);

        // 200 OK = item was successfully reviewed.
        $response->assertStatus(200);

        $response->assertJsonPath('item.state', 'approved');
        $response->assertJsonPath('item.review_note', 'Looks good');

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'state' => 'approved',
            'review_note' => 'Looks good',
        ]);

        $item->refresh();
        $this->assertNotNull($item->reviewed_at);
    }

    public function test_it_prevents_re_reviewing_an_item(): void
    {
        $item = Item::create([
            'title' => 'Already reviewed',
            'content' => 'Normal content',
            'state' => 'approved',
            'review_note' => 'Already handled',
            'risk_score' => 0,
            'reviewed_at' => now(),
        ]);

        $response = $this->postJson("/api/items/{$item->id}/review", [
            'action' => 'reject',
            'note' => 'Trying again',
        ]);

        // 409 Conflict = item is not pending anymore.
        $response->assertStatus(409);

        $response->assertJson([
            'message' => 'Item was already reviewed.',
        ]);
    }

    public function test_it_filters_items_by_state(): void
    {
        Item::create([
            'title' => 'Pending item',
            'content' => 'Pending content',
            'state' => 'pending',
            'risk_score' => 0,
        ]);

        Item::create([
            'title' => 'Approved item',
            'content' => 'Approved content',
            'state' => 'approved',
            'risk_score' => 0,
            'reviewed_at' => now(),
        ]);

        $response = $this->getJson('/api/items?state=pending');

        // 200 OK = filtered list was returned successfully.
        $response->assertStatus(200);

        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.state', 'pending');
        $response->assertJsonPath('data.0.title', 'Pending item');
    }
}

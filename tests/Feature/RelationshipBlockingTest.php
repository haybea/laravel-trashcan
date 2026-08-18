<?php

namespace Haybea\Trashcan\Tests\Feature;

use Haybea\Trashcan\Http\Controllers\TrashcanController;
use Haybea\Trashcan\Tests\Fixtures\Models\Comment;
use Haybea\Trashcan\Tests\Fixtures\Models\Post;
use Haybea\Trashcan\Tests\TestCase;

class RelationshipBlockingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['trashcan.only' => [Post::class, Comment::class]]);
    }

    protected function encoded(): string
    {
        return TrashcanController::encodeModelClass(Post::class);
    }

    public function test_force_delete_blocked_when_related_records_exist_and_config_enabled(): void
    {
        config(['trashcan.block_delete_with_children' => [Post::class]]);

        $post = Post::create(['title' => 'Parent']);
        $post->comments()->create(['body' => 'Still here']);
        $post->delete();

        $this->delete(route('trashcan.force-delete', [$this->encoded(), $post->id]))
            ->assertStatus(422);

        $this->assertNotNull(Post::withTrashed()->find($post->id));
    }

    public function test_force_delete_proceeds_when_config_disabled(): void
    {
        $post = Post::create(['title' => 'Parent']);
        $post->comments()->create(['body' => 'Still here']);
        $post->delete();

        $this->delete(route('trashcan.force-delete', [$this->encoded(), $post->id]))
            ->assertRedirect();

        $this->assertNull(Post::withTrashed()->find($post->id));
    }

    public function test_force_delete_proceeds_when_no_related_records_exist(): void
    {
        config(['trashcan.block_delete_with_children' => [Post::class]]);

        $post = Post::create(['title' => 'Lonely parent']);
        $post->delete();

        $this->delete(route('trashcan.force-delete', [$this->encoded(), $post->id]))
            ->assertRedirect();

        $this->assertNull(Post::withTrashed()->find($post->id));
    }

    public function test_bulk_force_delete_blocked_when_related_records_exist(): void
    {
        config(['trashcan.block_delete_with_children' => [Post::class]]);

        $post = Post::create(['title' => 'Parent']);
        $post->comments()->create(['body' => 'Still here']);
        $post->delete();

        $this->post(route('trashcan.bulk-force-delete', $this->encoded()), ['ids' => [$post->id]])
            ->assertStatus(422);

        $this->assertNotNull(Post::withTrashed()->find($post->id));
    }
}

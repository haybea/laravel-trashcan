<?php

namespace Haybea\Trashcan\Tests\Feature;

use Haybea\Trashcan\Http\Controllers\TrashcanController;
use Haybea\Trashcan\Tests\Fixtures\Models\Comment;
use Haybea\Trashcan\Tests\Fixtures\Models\Post;
use Haybea\Trashcan\Tests\TestCase;

class AffectedChildrenRegressionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['trashcan.only' => [Post::class, Comment::class]]);
    }

    public function test_preview_does_not_mutate_state_or_fire_model_events(): void
    {
        $post = Post::create(['title' => 'Parent']);
        $comment = $post->comments()->create(['body' => 'Child comment']);
        $comment->delete();
        $post->delete();

        $originalDeletedAt = Post::withTrashed()->find($post->id)->deleted_at;

        $restoredFired = false;
        $deletedFired = false;
        Post::restored(function () use (&$restoredFired) { $restoredFired = true; });
        Post::deleted(function () use (&$deletedFired) { $deletedFired = true; });

        $response = $this->getJson(route('trashcan.affected-children', [
            'model' => TrashcanController::encodeModelClass(Post::class),
            'ids' => json_encode([$post->id]),
        ]));

        $response->assertOk()->assertJsonFragment(['model' => 'Comment', 'count' => 1]);

        $this->assertFalse($restoredFired, 'Previewing affected children must not fire a restored event.');
        $this->assertFalse($deletedFired, 'Previewing affected children must not fire a deleted event.');

        $freshDeletedAt = Post::withTrashed()->find($post->id)->deleted_at;
        $this->assertNotNull($freshDeletedAt);
        $this->assertEquals($originalDeletedAt->timestamp, $freshDeletedAt->timestamp);
    }
}

<?php

namespace Haybea\Trashcan\Tests\Feature;

use Haybea\Trashcan\Events\BulkForceDeleted;
use Haybea\Trashcan\Events\BulkRestored;
use Haybea\Trashcan\Events\ItemForceDeleted;
use Haybea\Trashcan\Events\ItemRestored;
use Haybea\Trashcan\Events\TrashEmptied;
use Haybea\Trashcan\Http\Controllers\TrashcanController;
use Haybea\Trashcan\Models\TrashcanActivity;
use Haybea\Trashcan\Tests\Fixtures\Models\Comment;
use Haybea\Trashcan\Tests\Fixtures\Models\Post;
use Haybea\Trashcan\Tests\TestCase;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;

class TrashcanControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['trashcan.only' => [Post::class, Comment::class]]);
    }

    protected function encoded(string $class = Post::class): string
    {
        return TrashcanController::encodeModelClass($class);
    }

    protected function makeUser(): User
    {
        return User::forceCreate([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('secret'),
        ]);
    }

    public function test_index_returns_dashboard_with_stats(): void
    {
        $this->get(route('trashcan.index'))
            ->assertOk()
            ->assertViewHas('stats')
            ->assertViewHas('models');
    }

    public function test_statistics_page_renders(): void
    {
        Post::create(['title' => 'Trashed'])->delete();

        $this->get(route('trashcan.statistics'))
            ->assertOk()
            ->assertSee('Statistics');
    }

    public function test_show_lists_trashed_items_for_known_model(): void
    {
        $post = Post::create(['title' => 'Trashed post']);
        $post->delete();

        $this->get(route('trashcan.show', $this->encoded()))
            ->assertOk()
            ->assertSee('Trashed post');
    }

    public function test_show_returns_404_for_unknown_model(): void
    {
        $this->get(route('trashcan.show', $this->encoded(\Haybea\Trashcan\Tests\Fixtures\Models\PlainModel::class)))
            ->assertNotFound();
    }

    public function test_restore_untrashes_item_logs_and_fires_event(): void
    {
        Event::fake([ItemRestored::class]);
        $post = Post::create(['title' => 'Restore me']);
        $post->delete();

        $this->post(route('trashcan.restore', [$this->encoded(), $post->id]))
            ->assertRedirect();

        $this->assertNull($post->fresh()->deleted_at);
        Event::assertDispatched(ItemRestored::class);
        $this->assertDatabaseHas('trashcan_activities', [
            'action' => TrashcanActivity::ACTION_RESTORED,
            'model_class' => Post::class,
        ]);
    }

    public function test_restore_cascades_configured_relations(): void
    {
        config(['trashcan.restore_with_relations' => [Post::class => ['comments']]]);

        $post = Post::create(['title' => 'Parent']);
        $comment = $post->comments()->create(['body' => 'Child comment']);
        $comment->delete();
        $post->delete();

        $this->post(route('trashcan.restore', [$this->encoded(), $post->id]));

        $this->assertNull($comment->fresh()->deleted_at);
    }

    public function test_restore_falls_back_to_auto_detected_relations_when_config_empty(): void
    {
        config(['trashcan.restore_with_relations' => []]);

        $post = Post::create(['title' => 'Parent']);
        $comment = $post->comments()->create(['body' => 'Child comment']);
        $comment->delete();
        $post->delete();

        $this->post(route('trashcan.restore', [$this->encoded(), $post->id]));

        $this->assertNull($comment->fresh()->deleted_at);
    }

    public function test_force_delete_permanently_removes_item(): void
    {
        Event::fake([ItemForceDeleted::class]);
        $post = Post::create(['title' => 'Delete me']);
        $post->delete();

        $this->delete(route('trashcan.force-delete', [$this->encoded(), $post->id]))
            ->assertRedirect();

        $this->assertNull(Post::withTrashed()->find($post->id));
        Event::assertDispatched(ItemForceDeleted::class);
    }

    public function test_bulk_restore_restores_multiple_items(): void
    {
        Event::fake([BulkRestored::class]);
        $ids = collect(range(1, 3))->map(fn ($i) => tap(Post::create(['title' => "Post {$i}"]))->delete()->id);

        $this->post(route('trashcan.bulk-restore', $this->encoded()), ['ids' => $ids->toArray()])
            ->assertRedirect();

        $this->assertSame(0, Post::onlyTrashed()->count());
        Event::assertDispatched(BulkRestored::class);
    }

    public function test_bulk_force_delete_removes_multiple_items(): void
    {
        Event::fake([BulkForceDeleted::class]);
        $ids = collect(range(1, 3))->map(fn ($i) => tap(Post::create(['title' => "Post {$i}"]))->delete()->id);

        $this->post(route('trashcan.bulk-force-delete', $this->encoded()), ['ids' => $ids->toArray()])
            ->assertRedirect();

        $this->assertSame(0, Post::withTrashed()->count());
        Event::assertDispatched(BulkForceDeleted::class);
    }

    public function test_empty_trash_removes_all_trashed_and_reports_count(): void
    {
        Event::fake([TrashEmptied::class]);
        Post::create(['title' => 'A'])->delete();
        Post::create(['title' => 'B'])->delete();
        Post::create(['title' => 'Still alive']);

        $this->delete(route('trashcan.empty-trash', $this->encoded()))
            ->assertRedirect();

        $this->assertSame(0, Post::onlyTrashed()->count());
        $this->assertSame(1, Post::count());
        Event::assertDispatched(TrashEmptied::class, fn ($event) => $event->count === 2);
    }

    public function test_export_streams_csv_download(): void
    {
        Post::create(['title' => 'Exported'])->delete();

        $response = $this->get(route('trashcan.export', $this->encoded()).'?format=csv')
            ->assertOk();

        $this->assertStringStartsWith('text/csv', $response->headers->get('Content-Type'));
    }

    public function test_export_requires_view_permission(): void
    {
        Gate::define('deniedGate', fn ($user) => false);
        config(['trashcan.model_permissions' => [Post::class => ['view' => 'deniedGate']]]);

        $this->actingAs($this->makeUser())
            ->get(route('trashcan.export', $this->encoded()).'?format=csv')
            ->assertForbidden();
    }

    public function test_denies_restore_when_model_permissions_gate_fails(): void
    {
        Gate::define('deniedGate', fn ($user) => false);
        config(['trashcan.model_permissions' => [Post::class => ['restore' => 'deniedGate']]]);

        $post = Post::create(['title' => 'Protected']);
        $post->delete();

        $this->actingAs($this->makeUser())
            ->post(route('trashcan.restore', [$this->encoded(), $post->id]))
            ->assertForbidden();
    }

    public function test_allows_restore_when_model_permissions_gate_passes(): void
    {
        Gate::define('allowedGate', fn ($user) => true);
        config(['trashcan.model_permissions' => [Post::class => ['restore' => 'allowedGate']]]);

        $post = Post::create(['title' => 'Allowed']);
        $post->delete();

        $this->actingAs($this->makeUser())
            ->post(route('trashcan.restore', [$this->encoded(), $post->id]))
            ->assertRedirect();

        $this->assertNull($post->fresh()->deleted_at);
    }

    public function test_denies_force_delete_when_model_permissions_gate_fails(): void
    {
        Gate::define('deniedGate', fn ($user) => false);
        config(['trashcan.model_permissions' => [Post::class => ['delete' => 'deniedGate']]]);

        $post = Post::create(['title' => 'Protected']);
        $post->delete();

        $this->actingAs($this->makeUser())
            ->delete(route('trashcan.force-delete', [$this->encoded(), $post->id]))
            ->assertForbidden();

        $this->assertNotNull(Post::withTrashed()->find($post->id));
    }
}

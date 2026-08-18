<?php

namespace Haybea\Trashcan\Tests\Unit;

use Haybea\Trashcan\Services\ModelDiscoveryService;
use Haybea\Trashcan\Tests\Fixtures\Models\Comment;
use Haybea\Trashcan\Tests\Fixtures\Models\Post;
use Haybea\Trashcan\Tests\TestCase;

class ModelDiscoveryServiceTest extends TestCase
{
    protected function service(): ModelDiscoveryService
    {
        return $this->app->make(ModelDiscoveryService::class);
    }

    public function test_discovers_models_via_only_config(): void
    {
        config(['trashcan.only' => [Post::class, Comment::class]]);

        $models = $this->service()->getModels();

        $this->assertTrue($models->has(Post::class));
        $this->assertTrue($models->has(Comment::class));
    }

    public function test_respects_exclude_config(): void
    {
        config([
            'trashcan.only' => [Post::class, Comment::class],
            'trashcan.exclude' => [Comment::class],
        ]);

        $models = $this->service()->getModels();

        $this->assertTrue($models->has(Post::class));
        $this->assertFalse($models->has(Comment::class));
    }

    public function test_computes_trashed_count_accurately(): void
    {
        config(['trashcan.only' => [Post::class]]);

        Post::create(['title' => 'Kept'])->delete();
        Post::create(['title' => 'Also trashed'])->delete();
        Post::create(['title' => 'Not trashed']);

        $models = $this->service()->getModels();

        $this->assertSame(2, $models->get(Post::class)['trashed_count']);
    }

    public function test_ignores_models_without_soft_deletes_when_whitelisted(): void
    {
        config(['trashcan.only' => [\Haybea\Trashcan\Tests\Fixtures\Models\PlainModel::class]]);

        $models = $this->service()->getModels();

        $this->assertFalse($models->has(\Haybea\Trashcan\Tests\Fixtures\Models\PlainModel::class));
    }

    public function test_discovers_models_via_filesystem_scan(): void
    {
        config(['trashcan.only' => []]);
        $this->app->useAppPath(__DIR__.'/../Fixtures/app');

        $models = $this->service()->getModels();

        $this->assertTrue($models->has(\App\Models\Post::class));
        $this->assertTrue($models->has(\App\Models\Comment::class));
    }
}

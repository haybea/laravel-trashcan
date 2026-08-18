<?php

namespace Haybea\Trashcan\Tests\Unit;

use Haybea\Trashcan\Events\TrashExported;
use Haybea\Trashcan\Models\TrashcanActivity;
use Haybea\Trashcan\Services\ExportService;
use Haybea\Trashcan\Tests\Fixtures\Models\Post;
use Haybea\Trashcan\Tests\TestCase;
use Illuminate\Support\Facades\Event;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['trashcan.only' => [Post::class]]);
    }

    protected function service(): ExportService
    {
        return $this->app->make(ExportService::class);
    }

    protected function streamedContent(StreamedResponse $response): string
    {
        ob_start();
        $response->sendContent();

        return ob_get_clean();
    }

    public function test_exports_csv_with_header_and_rows(): void
    {
        Post::create(['title' => 'First'])->delete();
        Post::create(['title' => 'Second'])->delete();

        $content = $this->streamedContent($this->service()->export(Post::class, 'csv'));
        $lines = array_values(array_filter(explode("\n", str_replace("\r\n", "\n", trim($content)))));

        $this->assertStringContainsString('title', $lines[0]);
        $this->assertCount(3, $lines); // header + 2 rows
        $this->assertStringContainsString('First', $lines[1]);
        $this->assertStringContainsString('Second', $lines[2]);
    }

    public function test_exports_json_with_metadata_and_data(): void
    {
        Post::create(['title' => 'Only one'])->delete();

        $content = $this->streamedContent($this->service()->export(Post::class, 'json'));
        $data = json_decode($content, true);

        $this->assertSame('Post', $data['model']);
        $this->assertSame(1, $data['count']);
        $this->assertCount(1, $data['data']);
    }

    public function test_respects_max_records_config_cap(): void
    {
        config(['trashcan.export.max_records' => 2]);

        for ($i = 0; $i < 5; $i++) {
            Post::create(['title' => "Post {$i}"])->delete();
        }

        $content = $this->streamedContent($this->service()->export(Post::class, 'json'));
        $data = json_decode($content, true);

        $this->assertSame(2, $data['count']);
    }

    public function test_filters_by_explicit_ids_when_provided(): void
    {
        $keep = Post::create(['title' => 'Keep me']);
        $keep->delete();
        Post::create(['title' => 'Skip me'])->delete();

        $content = $this->streamedContent($this->service()->export(Post::class, 'json', [$keep->id]));
        $data = json_decode($content, true);

        $this->assertSame(1, $data['count']);
        $this->assertSame('Keep me', $data['data'][0]['title']);
    }

    public function test_logs_activity_and_fires_event(): void
    {
        Event::fake([TrashExported::class]);

        Post::create(['title' => 'Exported'])->delete();

        $this->streamedContent($this->service()->export(Post::class, 'csv'));

        Event::assertDispatched(TrashExported::class, function (TrashExported $event) {
            return $event->modelClass === Post::class && $event->count === 1;
        });

        $this->assertDatabaseHas('trashcan_activities', [
            'action' => TrashcanActivity::ACTION_EXPORTED,
            'model_class' => Post::class,
        ]);
    }
}

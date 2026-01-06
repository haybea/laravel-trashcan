<?php

namespace Haybea\Trashcan\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Haybea\Trashcan\Events\TrashExported;

class ExportService
{
    public function __construct(protected ActivityLogger $logger, protected ModelDiscoveryService $discovery) {}

    public function export(string $modelClass, string $format = 'csv', ?array $ids = null): StreamedResponse
    {
        $models = $this->discovery->getModels();
        if (!$models->has($modelClass)) abort(404, 'Model not found');

        $info = $models->get($modelClass);
        $query = $modelClass::onlyTrashed();
        if ($ids) $query->whereIn('id', $ids);

        $items = $query->limit(config('trashcan.export.max_records', 10000))->get();
        $this->logger->logExported($modelClass, $items->count(), $format);
        event(new TrashExported($modelClass, $items->count(), $format));

        return $format === 'json' ? $this->exportJson($items, $info) : $this->exportCsv($items, $info);
    }

    protected function exportCsv(Collection $items, array $info): StreamedResponse
    {
        $filename = strtolower($info['name']) . '_trash_' . date('Y-m-d_His') . '.csv';
        $cols = $items->first() ? array_keys($items->first()->getAttributes()) : ['id', 'deleted_at'];

        return Response::streamDownload(function () use ($items, $cols) {
            $h = fopen('php://output', 'w');
            fputcsv($h, $cols);
            foreach ($items as $item) {
                fputcsv($h, array_map(fn ($c) => is_array($v = $item->{$c}) || is_object($v) ? json_encode($v) : $v, $cols));
            }
            fclose($h);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    protected function exportJson(Collection $items, array $info): StreamedResponse
    {
        return Response::streamDownload(fn () => print json_encode(['model' => $info['name'], 'exported_at' => now()->toIso8601String(), 'count' => $items->count(), 'data' => $items], JSON_PRETTY_PRINT),
            strtolower($info['name']) . '_trash_' . date('Y-m-d_His') . '.json', ['Content-Type' => 'application/json']);
    }
}
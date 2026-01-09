<?php

namespace Haybea\Trashcan\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Haybea\Trashcan\Events\TrashExported;

class ExportService
{
    public function __construct(
        protected ActivityLogger $logger,
        protected ModelDiscoveryService $discoveryService
    ) {}

    public function export(string $modelClass, string $format = 'csv', ?array $ids = null): StreamedResponse
    {
        $models = $this->discoveryService->getModels();

        if (!$models->has($modelClass)) {
            abort(404, 'Model not found');
        }

        $modelInfo = $models->get($modelClass);
        $query = $modelClass::onlyTrashed();

        if ($ids) {
            $query->whereIn('id', $ids);
        }

        $maxRecords = config('trashcan.export.max_records', 10000);
        $items = $query->limit($maxRecords)->get();

        $this->logger->logExported($modelClass, $items->count(), $format);
        event(new TrashExported($modelClass, $items->count(), $format));

        return match ($format) {
            'json' => $this->exportJson($items, $modelInfo),
            default => $this->exportCsv($items, $modelInfo),
        };
    }

    protected function exportCsv(Collection $items, array $modelInfo): StreamedResponse
    {
        $filename = strtolower($modelInfo['name']) . '_trashcan_' . date('Y-m-d_His') . '.csv';
        $columns = $this->getAllColumns($items->first());

        return Response::streamDownload(function () use ($items, $columns) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, $columns);

            foreach ($items as $item) {
                $row = [];
                foreach ($columns as $column) {
                    $value = $item->{$column};
                    $row[] = is_array($value) || is_object($value) ? json_encode($value) : $value;
                }
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    protected function exportJson(Collection $items, array $modelInfo): StreamedResponse
    {
        $filename = strtolower($modelInfo['name']) . '_trashcan_' . date('Y-m-d_His') . '.json';

        return Response::streamDownload(function () use ($items, $modelInfo) {
            echo json_encode([
                'model' => $modelInfo['name'],
                'exported_at' => now()->toIso8601String(),
                'count' => $items->count(),
                'data' => $items->toArray(),
            ], JSON_PRETTY_PRINT);
        }, $filename, ['Content-Type' => 'application/json']);
    }

    protected function getAllColumns($item): array
    {
        if (!$item) {
            return ['id', 'deleted_at'];
        }

        return array_keys($item->getAttributes());
    }
}
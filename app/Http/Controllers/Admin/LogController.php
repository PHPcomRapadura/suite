<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class LogController
{
    public function index(Request $request): JsonResponse
    {
        $page = max((int) $request->integer('page', 1), 1);
        $perPage = min(max((int) $request->integer('per_page', 50), 10), 100);
        $level = strtoupper(trim((string) $request->input('level', '')));
        $search = trim((string) $request->input('search', ''));
        $path = storage_path('logs/laravel.log');

        if (! File::exists($path)) {
            return response()->json([
                'path' => 'storage/logs/laravel.log',
                'entries' => [],
                'count' => 0,
                'current_page' => $page,
                'last_page' => 1,
                'total' => 0,
                'message' => 'O arquivo de log ainda não existe.',
            ]);
        }

        $content = File::get($path);
        $allLines = preg_split('/\R/', $content, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $entries = [];

        foreach ($allLines as $line) {
            if (preg_match('/^\[(?<timestamp>[^]]+)]\s+(?<channel>[^.]+)\.(?<level>[A-Z]+):\s*(?<message>.*)$/', $line, $match)) {
                $entries[] = [
                    'timestamp' => $match['timestamp'],
                    'channel' => $match['channel'],
                    'level' => $match['level'],
                    'message' => $match['message'],
                    'details' => '',
                ];

                continue;
            }

            if ($entries !== []) {
                $last = count($entries) - 1;
                $entries[$last]['details'] .= ($entries[$last]['details'] === '' ? '' : "\n").$line;
            }
        }

        $entries = array_reverse($entries);
        $entries = array_values(array_filter($entries, function (array $entry) use ($level, $search): bool {
            $matchesLevel = $level === '' || $level === 'ALL' || $entry['level'] === $level;
            $haystack = strtolower($entry['message'].' '.$entry['details']);
            $matchesSearch = $search === '' || str_contains($haystack, strtolower($search));

            return $matchesLevel && $matchesSearch;
        }));

        $total = count($entries);
        $lastPage = max((int) ceil($total / $perPage), 1);
        $page = min($page, $lastPage);
        $pageEntries = array_slice($entries, ($page - 1) * $perPage, $perPage);

        return response()->json([
            'path' => 'storage/logs/laravel.log',
            'entries' => $pageEntries,
            'count' => count($pageEntries),
            'current_page' => $page,
            'last_page' => $lastPage,
            'per_page' => $perPage,
            'total' => $total,
            'updated_at' => File::lastModified($path),
        ]);
    }
}

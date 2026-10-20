<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class LogController
{
    public function index(Request $request): JsonResponse
    {
        $lines = min(max((int) $request->integer('lines', 200), 20), 1000);
        $path = storage_path('logs/laravel.log');

        if (! File::exists($path)) {
            return response()->json([
                'path' => 'storage/logs/laravel.log',
                'entries' => [],
                'count' => 0,
                'message' => 'O arquivo de log ainda não existe.',
            ]);
        }

        $content = File::get($path);
        $allLines = preg_split('/\R/', $content, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $recentLines = array_slice($allLines, -$lines);
        $entries = [];

        foreach ($recentLines as $line) {
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

        return response()->json([
            'path' => 'storage/logs/laravel.log',
            'entries' => array_reverse($entries),
            'count' => count($entries),
            'updated_at' => File::lastModified($path),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Speaker;
use App\Services\SpeakerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SpeakerController extends Controller
{
    public function __construct(private SpeakerService $service) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'city', 'state']);
        $paginator = $this->service->list($filters);

        $items = collect($paginator->items())->map(fn ($s) => $this->service->formatSummary($s));

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
        ]);
    }

    public function show(Speaker $speaker): JsonResponse
    {
        return response()->json($this->service->detail($speaker));
    }
}

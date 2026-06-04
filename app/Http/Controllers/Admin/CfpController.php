<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Cfp\StoreCfpRequest;
use App\Http\Requests\Admin\Cfp\UpdateCfpRequest;
use App\Models\Event;
use App\Services\CfpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

class CfpController extends Controller
{
    public function __construct(private CfpService $cfpService) {}

    public function show(Event $event): JsonResponse
    {
        return response()->json(['data' => $this->cfpService->getWithStats($event)]);
    }

    public function store(StoreCfpRequest $request, Event $event): JsonResponse
    {
        try {
            $cfp = $this->cfpService->create($event, $request->validated(), Auth::id());
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json($cfp, Response::HTTP_CREATED);
    }

    public function update(UpdateCfpRequest $request, Event $event): JsonResponse
    {
        /** @var \App\Models\EventCfp|null $cfp */
        $cfp = $event->cfp;

        if (! $cfp) {
            return response()->json(['message' => 'CFP não configurado.'], Response::HTTP_NOT_FOUND);
        }

        return response()->json($this->cfpService->update($cfp, $request->validated()));
    }
}

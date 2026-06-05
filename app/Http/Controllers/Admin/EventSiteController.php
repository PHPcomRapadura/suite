<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EventSite\StoreEventSiteRequest;
use App\Http\Requests\Admin\EventSite\UpdateEventSiteRequest;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EventSiteController extends Controller
{
    public function show(Event $event): JsonResponse
    {
        return response()->json(['data' => $event->site]);
    }

    public function store(StoreEventSiteRequest $request, Event $event): JsonResponse
    {
        if ($event->site) {
            return response()->json(['message' => 'Site já configurado.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $site = $event->site()->create([
            ...$request->validated(),
            'created_by' => Auth::id(),
        ]);

        return response()->json(['data' => $site], Response::HTTP_CREATED);
    }

    public function update(UpdateEventSiteRequest $request, Event $event): JsonResponse
    {
        /** @var \App\Models\EventSiteConfig|null $site */
        $site = $event->site;

        if (! $site) {
            return response()->json(['message' => 'Site não configurado.'], Response::HTTP_NOT_FOUND);
        }

        $site->update($request->validated());

        return response()->json(['data' => $site->fresh()]);
    }

    public function togglePublished(Event $event): JsonResponse
    {
        /** @var \App\Models\EventSiteConfig|null $site */
        $site = $event->site;

        if (! $site) {
            return response()->json(['message' => 'Site não configurado.'], Response::HTTP_NOT_FOUND);
        }

        $site->update(['is_published' => ! $site->is_published]);

        return response()->json(['data' => $site->fresh()]);
    }
}

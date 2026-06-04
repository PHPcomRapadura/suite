<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventCfp;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class CfpPublicController extends Controller
{
    public function events(): JsonResponse
    {
        $events = Event::query()
            ->whereHas('cfp')
            ->whereIn('status', ['rascunho', 'publicado'])
            ->with('cfp')
            ->get()
            ->filter(function (Event $event) {
                /** @var EventCfp $cfp */
                $cfp = $event->cfp;

                return in_array($cfp->status(), ['aguardando', 'aberto']);
            })
            ->sortBy(function (Event $event) {
                /** @var EventCfp $cfp */
                $cfp = $event->cfp;

                /** @var \Illuminate\Support\Carbon $opensAt */
                $opensAt = $cfp->opens_at;

                return $opensAt->timestamp;
            })
            ->values();

        $data = $events->map(function (Event $event) {
            /** @var EventCfp $cfp */
            $cfp = $event->cfp;

            return [
                'id'          => $event->id,
                'name'        => $event->name,
                'slug'        => $event->slug,
                'edition'     => $event->edition,
                'starts_at'   => $event->starts_at,
                'ends_at'     => $event->ends_at,
                'location'    => $event->location,
                'is_online'   => $event->is_online,
                'cover_image' => $event->cover_image,
                'cfp'         => [
                    'opens_at'              => $cfp->opens_at,
                    'closes_at'             => $cfp->closes_at,
                    'speaker_guide'         => $cfp->speaker_guide,
                    'max_talks_per_speaker' => $cfp->max_talks_per_speaker,
                    'status'                => $cfp->status(),
                ],
            ];
        });

        return response()->json(['data' => $data]);
    }

    public function show(Event $event): JsonResponse
    {
        /** @var EventCfp|null $cfp */
        $cfp = $event->cfp;

        if (! $cfp) {
            return response()->json(['message' => 'CFP não configurado.'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(['data' => [
            'id'          => $event->id,
            'name'        => $event->name,
            'edition'     => $event->edition,
            'starts_at'   => $event->starts_at,
            'location'    => $event->location,
            'is_online'   => $event->is_online,
            'cover_image' => $event->cover_image,
            'cfp'         => [
                'opens_at'              => $cfp->opens_at,
                'closes_at'             => $cfp->closes_at,
                'speaker_guide'         => $cfp->speaker_guide,
                'max_talks_per_speaker' => $cfp->max_talks_per_speaker,
                'status'                => $cfp->status(),
            ],
        ]]);
    }
}

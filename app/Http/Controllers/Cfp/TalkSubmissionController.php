<?php

namespace App\Http\Controllers\Cfp;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cfp\StoreTalkRequest;
use App\Models\Event;
use App\Models\EventCfp;
use App\Models\Speaker;
use App\Models\Talk;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TalkSubmissionController extends Controller
{
    public function allMyTalks(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        /** @var Speaker|null $speaker */
        $speaker = $user->speaker;

        if (! $speaker) {
            return response()->json([
                'data' => [],
                'stats' => ['total' => 0, 'approved' => 0, 'pending' => 0, 'rejected' => 0],
            ]);
        }

        $talks = Talk::where('speaker_id', $speaker->id)
            ->with('event:id,name,slug,starts_at,cover_image')
            ->orderByDesc('submitted_at')
            ->get(['id', 'event_id', 'title', 'abstract', 'duration', 'level', 'status', 'submitted_at']);

        $stats = [
            'total' => $talks->count(),
            'approved' => $talks->where('status', 'aprovada')->count(),
            'pending' => $talks->whereIn('status', ['submetida', 'em_analise'])->count(),
            'rejected' => $talks->where('status', 'recusada')->count(),
        ];

        return response()->json(['data' => $talks, 'stats' => $stats]);
    }

    public function myTalks(Event $event): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        /** @var Speaker|null $speaker */
        $speaker = $user->speaker;

        if (! $speaker) {
            return response()->json(['data' => []]);
        }

        $talks = Talk::where('event_id', $event->id)
            ->where('speaker_id', $speaker->id)
            ->orderByDesc('submitted_at')
            ->get(['id', 'title', 'abstract', 'duration', 'level', 'status', 'submitted_at']);

        return response()->json(['data' => $talks]);
    }

    public function myTalksCount(Event $event): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        /** @var Speaker|null $speaker */
        $speaker = $user->speaker;

        if (! $speaker) {
            return response()->json(['count' => 0]);
        }

        $count = Talk::where('event_id', $event->id)
            ->where('speaker_id', $speaker->id)
            ->whereNotIn('status', ['cancelada'])
            ->count();

        return response()->json(['count' => $count]);
    }

    public function store(StoreTalkRequest $request, Event $event): JsonResponse
    {
        /** @var EventCfp|null $cfp */
        $cfp = $event->cfp;

        if (! $cfp || $cfp->status() !== 'aberto') {
            return response()->json(
                ['message' => 'O período de submissão não está aberto.'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        /** @var User $user */
        $user = Auth::user();

        $speaker = Speaker::firstOrCreate(['user_id' => $user->id]);

        if ($cfp->max_talks_per_speaker !== null) {
            $count = Talk::where('event_id', $event->id)
                ->where('speaker_id', $speaker->id)
                ->whereNotIn('status', ['cancelada'])
                ->count();

            if ($count >= $cfp->max_talks_per_speaker) {
                return response()->json(
                    ['message' => "Você já atingiu o limite de {$cfp->max_talks_per_speaker} proposta(s) para este evento."],
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }
        }

        $talk = Talk::create([
            'event_id' => $event->id,
            'speaker_id' => $speaker->id,
            'title' => $request->input('title'),
            'abstract' => $request->input('abstract'),
            'duration' => $request->input('duration'),
            'level' => $request->input('level'),
            'status' => 'submetida',
            'submitted_at' => now(),
        ]);

        return response()->json($talk, Response::HTTP_CREATED);
    }

    public function update(StoreTalkRequest $request, Talk $talk): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        /** @var Speaker|null $speaker */
        $speaker = $user->speaker;

        if (! $speaker || $talk->speaker_id !== $speaker->id) {
            return response()->json(['message' => 'Acesso não autorizado.'], Response::HTTP_FORBIDDEN);
        }

        if (! in_array($talk->status, ['submetida', 'em_analise'])) {
            return response()->json(
                ['message' => 'Esta proposta não pode mais ser editada.'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $talk->update($request->validated());

        return response()->json($talk->fresh());
    }
}

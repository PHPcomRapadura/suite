<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventTask;
use App\Models\EventTaskComment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EventTaskCommentController extends Controller
{
    public function index(Event $event, EventTask $task): JsonResponse
    {
        abort_if($task->event_id !== $event->id, Response::HTTP_NOT_FOUND);

        $comments = $task->comments()->with('author:id,name')->get();

        return response()->json([
            'data' => $comments->map(fn ($c) => $this->formatComment($c))->all(),
        ]);
    }

    public function store(Request $request, Event $event, EventTask $task): JsonResponse
    {
        abort_if($task->event_id !== $event->id, Response::HTTP_NOT_FOUND);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ], [
            'body.required' => 'O comentário não pode estar vazio.',
            'body.max' => 'O comentário deve ter no máximo 2.000 caracteres.',
        ]);

        $comment = EventTaskComment::create([
            'event_task_id' => $task->id,
            'user_id' => Auth::id(),
            'body' => $data['body'],
        ]);

        $comment->load('author:id,name');

        return response()->json(
            ['data' => $this->formatComment($comment)],
            Response::HTTP_CREATED
        );
    }

    public function update(Request $request, Event $event, EventTask $task, EventTaskComment $comment): JsonResponse
    {
        abort_if($task->event_id !== $event->id, Response::HTTP_NOT_FOUND);
        abort_if($comment->event_task_id !== $task->id, Response::HTTP_NOT_FOUND);
        abort_if($comment->user_id !== Auth::id(), Response::HTTP_FORBIDDEN);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ], [
            'body.required' => 'O comentário não pode estar vazio.',
            'body.max' => 'O comentário deve ter no máximo 2.000 caracteres.',
        ]);

        $comment->update(['body' => $data['body']]);
        $comment->load('author:id,name');

        return response()->json(['data' => $this->formatComment($comment)]);
    }

    public function destroy(Event $event, EventTask $task, EventTaskComment $comment): JsonResponse
    {
        abort_if($task->event_id !== $event->id, Response::HTTP_NOT_FOUND);
        abort_if($comment->event_task_id !== $task->id, Response::HTTP_NOT_FOUND);
        abort_if($comment->user_id !== Auth::id(), Response::HTTP_FORBIDDEN);

        $comment->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    private function formatComment(EventTaskComment $comment): array
    {
        return [
            'id' => $comment->id,
            'body' => $comment->body,
            'user_id' => $comment->user_id,
            'author' => $comment->author ? ['id' => $comment->author->id, 'name' => $comment->author->name] : null,
            'is_mine' => $comment->user_id === Auth::id(),
            'created_at' => $comment->created_at?->toISOString(),
            'updated_at' => $comment->updated_at?->toISOString(),
        ];
    }
}

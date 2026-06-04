<?php

namespace App\Http\Controllers\Cfp;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cfp\UpdateSpeakerProfileRequest;
use App\Models\Speaker;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SpeakerProfileController extends Controller
{
    public function show(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        /** @var Speaker|null $speaker */
        $speaker = $user->speaker;

        if (! $speaker) {
            return response()->json(['data' => null]);
        }

        return response()->json(['data' => $this->format($speaker)]);
    }

    public function update(UpdateSpeakerProfileRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $data = $request->validated();
        unset($data['avatar']);

        if ($request->hasFile('avatar')) {
            /** @var Speaker|null $existing */
            $existing = $user->speaker;
            $this->deleteAvatar($existing?->avatar_url);

            /** @var \Illuminate\Http\UploadedFile $file */
            $file = $request->file('avatar');
            $path = "speakers/{$user->id}/avatar.{$file->extension()}";
            Storage::disk('r2')->putFileAs('', $file, $path, 'public');
            $data['avatar_url'] = Storage::disk('r2')->url($path);
        }

        $speaker = Speaker::updateOrCreate(
            ['user_id' => $user->id],
            $data,
        );

        return response()->json(['data' => $this->format($speaker)]);
    }

    /** @return array<string, mixed> */
    private function format(Speaker $speaker): array
    {
        return [
            'bio'          => $speaker->bio,
            'company'      => $speaker->company,
            'city'         => $speaker->city,
            'state'        => $speaker->state,
            'avatar_url'   => $speaker->avatar_url,
            'phone_number' => $speaker->phone_number,
            'website'      => $speaker->website,
            'twitter'      => $speaker->twitter,
            'github'       => $speaker->github,
            'linkedin'     => $speaker->linkedin,
        ];
    }

    private function deleteAvatar(?string $url): void
    {
        if (! $url) {
            return;
        }

        try {
            $baseUrl = rtrim(Storage::disk('r2')->url(''), '/');
        } catch (\RuntimeException) {
            return;
        }

        if ($baseUrl && str_starts_with($url, $baseUrl)) {
            $path = ltrim(substr($url, strlen($baseUrl)), '/');
            Storage::disk('r2')->delete($path);
        }
    }
}

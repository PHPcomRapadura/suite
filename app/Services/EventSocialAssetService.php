<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventSocialAsset;
use App\Models\Talk;
use App\Services\SocialAssets\SocialAssetCanvas;
use App\Services\SocialAssets\Templates\AnnouncementTemplate;
use App\Services\SocialAssets\Templates\SocialAssetTemplate;
use App\Services\SocialAssets\Templates\SpeakerSpotlightTemplate;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class EventSocialAssetService
{
    private const SIZES = [
        'story' => [1080, 1920],
        'post' => [1080, 1080],
    ];

    public function __construct(private readonly SocialAssetCanvas $canvasTools) {}

    public function generate(Event $event, string $format, string $type = 'announcement', ?Talk $talk = null): EventSocialAsset
    {
        [$width, $height] = self::SIZES[$format];

        $site = $event->site;
        $primaryColor = $site?->primary_color ?: '#025c98';
        $secondaryColor = $site?->secondary_color ?: '#f59e0b';

        $canvas = $this->canvasTools->buildBackground($event->cover_image, $width, $height, $primaryColor, $secondaryColor);
        $this->canvasTools->drawOverlay($canvas, $width, $height);
        $this->canvasTools->drawLogo($canvas, $event->logo);

        $template = $this->resolveTemplate($type);
        $template->compose($canvas, $this->canvasTools, [
            'event' => $event,
            'talk' => $talk,
            'format' => $format,
            'width' => $width,
            'height' => $height,
            'tagline' => $site?->hero_tagline,
            'secondary_color' => $secondaryColor,
        ]);

        $content = (string) $canvas->toPng();
        $subjectKey = $this->subjectKey($type, $talk);
        $pathSuffix = $talk ? "-talk{$talk->id}" : '';
        $path = "events/{$event->id}/social/{$type}-{$format}{$pathSuffix}.png";

        Storage::disk('r2')->put($path, $content, 'public');

        return EventSocialAsset::updateOrCreate(
            ['event_id' => $event->id, 'type' => $type, 'format' => $format, 'subject_key' => $subjectKey],
            ['url' => Storage::disk('r2')->url($path), 'path' => $path, 'talk_id' => $talk?->id],
        );
    }

    private function resolveTemplate(string $type): SocialAssetTemplate
    {
        return match ($type) {
            'announcement' => new AnnouncementTemplate,
            'speaker' => new SpeakerSpotlightTemplate,
            default => throw new InvalidArgumentException("Tipo de arte não suportado: {$type}"),
        };
    }

    private function subjectKey(string $type, ?Talk $talk): string
    {
        return match ($type) {
            'speaker' => "talk:{$talk?->id}",
            default => 'event',
        };
    }
}

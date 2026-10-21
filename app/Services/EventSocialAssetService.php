<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventSocialAsset;
use App\Models\EventSponsor;
use App\Models\Talk;
use App\Services\SocialAssets\SocialAssetCanvas;
use App\Services\SocialAssets\Templates\AnnouncementTemplate;
use App\Services\SocialAssets\Templates\SellingOutTemplate;
use App\Services\SocialAssets\Templates\SocialAssetTemplate;
use App\Services\SocialAssets\Templates\SpeakerSpotlightTemplate;
use App\Services\SocialAssets\Templates\SponsorSpotlightTemplate;
use App\Services\SocialAssets\Templates\TomorrowTemplate;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class EventSocialAssetService
{
    private const SIZES = [
        'story' => [1080, 1920],
        'post' => [1080, 1080],
    ];

    public function __construct(private readonly SocialAssetCanvas $canvasTools) {}

    public function generate(Event $event, string $format, string $type = 'announcement', ?Talk $talk = null, ?EventSponsor $sponsor = null): EventSocialAsset
    {
        [$width, $height] = self::SIZES[$format];

        $site = $event->site;
        $primaryColor = $site?->primary_color ?: '#025c98';
        $secondaryColor = $site?->secondary_color ?: '#f59e0b';

        $sponsors = $event->sponsors;

        // A imagem dedicada evita que a composição da capa do site seja
        // recortada de forma inadequada nos formatos story e post.
        $canvas = $this->canvasTools->buildBackground(
            $event->social_base_image ?: $event->cover_image,
            $width,
            $height,
            $primaryColor,
            $secondaryColor,
        );
        $this->canvasTools->drawOverlay($canvas, $width, $height);

        $template = $this->resolveTemplate($type);
        $template->compose($canvas, $this->canvasTools, [
            'event' => $event,
            'talk' => $talk,
            'sponsor' => $sponsor,
            'format' => $format,
            'width' => $width,
            'height' => $height,
            'secondary_color' => $secondaryColor,
            'has_sponsor_footer' => $sponsors->isNotEmpty(),
        ]);

        // Assinatura do evento no canto inferior direito, depois do conteúdo
        // principal para permanecer nítida e não ser coberta pelo scrim.
        $this->canvasTools->drawLogo($canvas, $event->logo, $width, $height, $format, $sponsors->isNotEmpty());

        // Rodapé com logos dos patrocinadores, aplicado após o template para que
        // todos os tipos de arte ganhem o rodapé sem duplicação.
        $this->canvasTools->drawSponsorFooter($canvas, $sponsors, $width, $height, $format);

        $content = (string) $canvas->toPng();
        $subjectKey = $this->subjectKey($type, $talk, $sponsor);
        $pathSuffix = match (true) {
            $talk !== null => "-talk{$talk->id}",
            $sponsor !== null => "-sponsor{$sponsor->id}",
            default => '',
        };
        $path = "events/{$event->id}/social/{$type}-{$format}{$pathSuffix}.png";

        Storage::disk('r2')->put($path, $content, 'public');

        $asset = EventSocialAsset::updateOrCreate(
            ['event_id' => $event->id, 'type' => $type, 'format' => $format, 'subject_key' => $subjectKey],
            ['url' => Storage::disk('r2')->url($path), 'path' => $path, 'talk_id' => $talk?->id, 'sponsor_id' => $sponsor?->id],
        );

        // Ao regerar, url/path são os mesmos e o updateOrCreate não grava nada:
        // o touch() garante que updated_at (a "versão" da arte) avance.
        if (! $asset->wasRecentlyCreated && ! $asset->wasChanged()) {
            $asset->touch();
        }

        return $asset;
    }

    private function resolveTemplate(string $type): SocialAssetTemplate
    {
        return match ($type) {
            'announcement' => new AnnouncementTemplate,
            'speaker' => new SpeakerSpotlightTemplate,
            'sponsor' => new SponsorSpotlightTemplate,
            'selling_out' => new SellingOutTemplate,
            'tomorrow' => new TomorrowTemplate,
            default => throw new InvalidArgumentException("Tipo de arte não suportado: {$type}"),
        };
    }

    private function subjectKey(string $type, ?Talk $talk, ?EventSponsor $sponsor): string
    {
        return match ($type) {
            'speaker' => "talk:{$talk?->id}",
            'sponsor' => "sponsor:{$sponsor?->id}",
            default => 'event',
        };
    }
}

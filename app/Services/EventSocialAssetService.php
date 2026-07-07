<?php

namespace App\Services;

use App\Models\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EventSocialAssetService
{
    public function generate(Event $event, string $format): array
    {
        $path = $this->buildPath($event, $format);
        $relativePath = 'events/'.$event->id.'/social/'.$format.'.svg';

        $site = $event->site;
        $primaryColor = $site?->primary_color ?? '#025c98';
        $secondaryColor = $site?->secondary_color ?? '#f59e0b';
        $tagline = $site?->hero_tagline ?? 'O evento que sua comunidade precisa';
        $fontFamily = $site?->font ?? 'Lexend';

        $content = $this->buildSvg($event, $format, $primaryColor, $secondaryColor, $tagline, $fontFamily);

        Storage::disk('public')->put($relativePath, $content);

        return [
            'format' => $format,
            'asset_url' => Storage::disk('public')->url($relativePath),
            'path' => $relativePath,
        ];
    }

    private function buildPath(Event $event, string $format): string
    {
        return 'events/'.$event->id.'/social/'.$format.'.svg';
    }

    private function buildSvg(Event $event, string $format, string $primaryColor, string $secondaryColor, string $tagline, string $fontFamily): string
    {
        $width = 1080;
        $height = $format === 'story' ? 1920 : 1080;
        $contentWidth = $width - 140;
        $contentHeight = $height - 140;
        $circleX = $width - 220;
        $circleY = $height - 220;
        $bottomY = $height - 260;
        $bottomTextY = $height - 200;
        $title = Str::limit($event->name, 52, '…');
        $location = $event->location ?: 'Confira a programação';
        $description = Str::limit($event->description ?: 'Evento imperdível para a comunidade.', 150, '…');
        $date = $event->starts_at ? $event->starts_at->translatedFormat('d \m\e F \d\e Y') : 'Data em breve';

        return <<<SVG
<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" width="{$width}" height="{$height}" viewBox="0 0 {$width} {$height}">
  <rect width="{$width}" height="{$height}" fill="{$primaryColor}" />
  <rect x="0" y="0" width="{$width}" height="{$height}" fill="url(#grad)" />
  <rect x="70" y="70" width="{$contentWidth}" height="{$contentHeight}" rx="42" fill="rgba(255,255,255,0.12)" stroke="rgba(255,255,255,0.2)" stroke-width="2" />
  <circle cx="{$circleX}" cy="180" r="180" fill="{$secondaryColor}" opacity="0.35" />
  <circle cx="220" cy="{$circleY}" r="260" fill="#ffffff" opacity="0.08" />
  <text x="110" y="360" fill="#ffffff" font-family="{$fontFamily}" font-size="38" font-weight="600">{$event->name}</text>
  <text x="110" y="430" fill="#ffffff" font-family="{$fontFamily}" font-size="26" opacity="0.95">{$tagline}</text>
  <rect x="110" y="500" width="180" height="8" rx="4" fill="{$secondaryColor}" />
  <text x="110" y="620" fill="#ffffff" font-family="{$fontFamily}" font-size="64" font-weight="700">{$title}</text>
  <text x="110" y="690" fill="#ffffff" font-family="{$fontFamily}" font-size="32" opacity="0.95">{$date}</text>
  <text x="110" y="745" fill="#ffffff" font-family="{$fontFamily}" font-size="32" opacity="0.95">{$location}</text>
  <text x="110" y="810" fill="#ffffff" font-family="{$fontFamily}" font-size="28" opacity="0.9">{$description}</text>
  <rect x="110" y="{$bottomY}" width="320" height="92" rx="24" fill="#ffffff" opacity="0.2" />
  <text x="150" y="{$bottomTextY}" fill="#ffffff" font-family="{$fontFamily}" font-size="36" font-weight="700">Garanta sua vaga</text>
  <defs>
    <linearGradient id="grad" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="{$primaryColor}" />
      <stop offset="100%" stop-color="{$secondaryColor}" />
    </linearGradient>
  </defs>
</svg>
SVG;
    }
}

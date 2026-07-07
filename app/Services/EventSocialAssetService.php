<?php

namespace App\Services;

use App\Models\Event;
use Closure;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Geometry\Point;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Typography\FontFactory;
use Throwable;

class EventSocialAssetService
{
    private const SIZES = [
        'story' => [1080, 1920],
        'post' => [1080, 1080],
    ];

    private const PADDING = 70;

    private const OVERLAY_COLOR = 'rgba(0, 0, 0, 0.45)';

    private const LOGO_SIZE = 140;

    // O canvas é rasterizado com GD, que não sabe carregar fontes web (woff/woff2)
    // usadas pelo restante do app; por isso usamos sempre a Lexend variável embutida,
    // independente da fonte escolhida em EventSiteConfig::font.
    private const FONT_PATH = __DIR__.'/../../resources/fonts/Lexend-Variable.ttf';

    private ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver);
    }

    public function generate(Event $event, string $format): array
    {
        [$width, $height] = self::SIZES[$format];

        $site = $event->site;
        $primaryColor = $site?->primary_color ?: '#025c98';
        $secondaryColor = $site?->secondary_color ?: '#f59e0b';

        $canvas = $this->buildBackground($event, $width, $height, $primaryColor, $secondaryColor);
        $this->drawOverlay($canvas, $width, $height);
        $this->drawLogo($canvas, $event);
        $this->drawContent($canvas, $event, $format, $width, $height, $site?->hero_tagline, $secondaryColor);

        $content = (string) $canvas->toPng();
        $path = "events/{$event->id}/social/{$format}.png";

        Storage::disk('r2')->put($path, $content, 'public');

        return [
            'format' => $format,
            'asset_url' => Storage::disk('r2')->url($path),
            'path' => $path,
        ];
    }

    private function buildBackground(Event $event, int $width, int $height, string $primaryColor, string $secondaryColor): ImageInterface
    {
        $cover = $this->fetchImage($event->cover_image);

        if ($cover) {
            return $cover->cover($width, $height);
        }

        return $this->buildGradient($width, $height, $primaryColor, $secondaryColor);
    }

    private function buildGradient(int $width, int $height, string $from, string $to): ImageInterface
    {
        $canvas = $this->manager->create($width, $height);

        [$r1, $g1, $b1] = $this->hexToRgb($from);
        [$r2, $g2, $b2] = $this->hexToRgb($to);

        $step = 6;

        for ($y = 0; $y < $height; $y += $step) {
            $t = $y / max(1, $height - 1);
            $color = sprintf(
                '#%02x%02x%02x',
                (int) round($r1 + ($r2 - $r1) * $t),
                (int) round($g1 + ($g2 - $g1) * $t),
                (int) round($b1 + ($b2 - $b1) * $t),
            );

            $canvas->drawRectangle(0, $y, function ($rectangle) use ($width, $step, $color) {
                $rectangle->size($width, $step);
                $rectangle->background($color);
            });
        }

        return $canvas;
    }

    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    private function drawOverlay(ImageInterface $canvas, int $width, int $height): void
    {
        $canvas->drawRectangle(0, 0, function ($rectangle) use ($width, $height) {
            $rectangle->size($width, $height);
            $rectangle->background(self::OVERLAY_COLOR);
        });
    }

    private function drawLogo(ImageInterface $canvas, Event $event): void
    {
        $logo = $this->fetchImage($event->logo);

        if (! $logo) {
            return;
        }

        $logo->scaleDown(width: self::LOGO_SIZE, height: self::LOGO_SIZE);
        $canvas->place($logo, 'top-left', self::PADDING, self::PADDING);
    }

    private function drawContent(ImageInterface $canvas, Event $event, string $format, int $width, int $height, ?string $tagline, string $secondaryColor): void
    {
        $contentWidth = $width - (self::PADDING * 2);
        $cursorY = (int) round($height * ($format === 'story' ? 0.42 : 0.30));
        $gap = 30;

        $title = Str::limit($event->name, 70, '…');
        $cursorY += $this->drawTextBlock($canvas, $title, self::PADDING, $cursorY, function (FontFactory $font) use ($contentWidth) {
            $font->filename(self::FONT_PATH);
            $font->size(50);
            $font->color('#ffffff');
            $font->lineHeight(1.15);
            $font->wrap($contentWidth);
        }) + $gap;

        if ($tagline) {
            $cursorY += $this->drawTextBlock($canvas, $tagline, self::PADDING, $cursorY, function (FontFactory $font) use ($contentWidth) {
                $font->filename(self::FONT_PATH);
                $font->size(26);
                $font->color('rgba(255, 255, 255, 0.95)');
                $font->wrap($contentWidth);
            }) + $gap;
        }

        $date = $event->starts_at ? $event->starts_at->translatedFormat('d \d\e F \d\e Y') : 'Data em breve';
        $location = $event->location ?: 'Confira a programação';
        $cursorY += $this->drawTextBlock($canvas, "{$date} · {$location}", self::PADDING, $cursorY, function (FontFactory $font) use ($contentWidth) {
            $font->filename(self::FONT_PATH);
            $font->size(28);
            $font->color('#ffffff');
            $font->wrap($contentWidth);
        }) + $gap;

        if ($event->description) {
            $description = Str::limit($event->description, 150, '…');
            $this->drawTextBlock($canvas, $description, self::PADDING, $cursorY, function (FontFactory $font) use ($contentWidth) {
                $font->filename(self::FONT_PATH);
                $font->size(24);
                $font->color('rgba(255, 255, 255, 0.9)');
                $font->lineHeight(1.3);
                $font->wrap($contentWidth);
            });
        }

        $ctaY = $height - 200;
        $canvas->drawRectangle(self::PADDING, $ctaY, function ($rectangle) use ($secondaryColor) {
            $rectangle->size(300, 86);
            $rectangle->background($secondaryColor);
        });
        $canvas->text('Garanta sua vaga', self::PADDING + 28, $ctaY + 28, function (FontFactory $font) {
            $font->filename(self::FONT_PATH);
            $font->size(28);
            $font->color('#ffffff');
        });
    }

    /**
     * Desenha o bloco de texto e devolve a altura real ocupada (incluindo quebras
     * de linha do wrap), para que o próximo bloco seja posicionado sem sobrepor.
     */
    private function drawTextBlock(ImageInterface $canvas, string $text, int $x, int $y, Closure $fontCallback): int
    {
        $font = (new FontFactory($fontCallback))();

        $canvas->text($text, $x, $y, $font);

        $processor = $canvas->driver()->fontProcessor();
        $lineCount = count($processor->textBlock($text, $font, new Point(0, 0))->lines());

        return $processor->leading($font) * ($lineCount - 1) + $processor->capHeight($font);
    }

    private function fetchImage(?string $url): ?ImageInterface
    {
        if (! $url) {
            return null;
        }

        try {
            $content = $this->resolveR2Content($url) ?? $this->fetchRemote($url);

            return $content ? $this->manager->read($content) : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function resolveR2Content(string $url): ?string
    {
        try {
            $baseUrl = rtrim(Storage::disk('r2')->url(''), '/');
        } catch (\RuntimeException) {
            return null;
        }

        if (! $baseUrl || ! str_starts_with($url, $baseUrl)) {
            return null;
        }

        $path = ltrim(substr($url, strlen($baseUrl)), '/');

        return Storage::disk('r2')->exists($path) ? Storage::disk('r2')->get($path) : null;
    }

    private function fetchRemote(string $url): ?string
    {
        $response = Http::timeout(5)->get($url);

        return $response->successful() ? $response->body() : null;
    }
}

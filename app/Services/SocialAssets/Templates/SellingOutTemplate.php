<?php

namespace App\Services\SocialAssets\Templates;

use App\Models\Event;
use App\Services\SocialAssets\SocialAssetCanvas;
use Illuminate\Support\Str;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Typography\FontFactory;

class SellingOutTemplate implements SocialAssetTemplate
{
    private const ALERT_COLOR = '#dc2626';

    private const RIBBON_HEIGHT = 130;

    public function compose(ImageInterface $canvas, SocialAssetCanvas $tools, array $context): void
    {
        /** @var Event $event */
        $event = $context['event'];
        $format = $context['format'];
        $width = $context['width'];
        $height = $context['height'];

        $centerX = (int) ($width / 2);
        $contentWidth = $width - (SocialAssetCanvas::PADDING * 2);
        $ribbonTop = (int) round($height * ($format === 'story' ? 0.36 : 0.24));

        $canvas->drawRectangle(0, $ribbonTop, function ($rectangle) use ($width) {
            $rectangle->size($width, self::RIBBON_HEIGHT);
            $rectangle->background(self::ALERT_COLOR);
        });
        $canvas->text('INGRESSOS ESGOTANDO', $centerX, $ribbonTop + (self::RIBBON_HEIGHT / 2), function (FontFactory $font) use ($contentWidth) {
            $font->filename(SocialAssetCanvas::FONT_PATH);
            $font->size(52);
            $font->color('#ffffff');
            $font->align('center');
            $font->valign('middle');
            $font->wrap($contentWidth);
        });

        $cursorY = $ribbonTop + self::RIBBON_HEIGHT + 50;
        $gap = 24;

        $cursorY += $tools->drawTextBlock($canvas, 'Garanta o seu agora antes que acabe!', $centerX, $cursorY, function (FontFactory $font) use ($contentWidth) {
            $font->filename(SocialAssetCanvas::FONT_PATH);
            $font->size(30);
            $font->color('#ffffff');
            $font->align('center');
            $font->wrap($contentWidth);
        }) + $gap;

        $name = Str::limit($event->name, 70, '…');
        $cursorY += $tools->drawTextBlock($canvas, $name, $centerX, $cursorY, function (FontFactory $font) use ($contentWidth) {
            $font->filename(SocialAssetCanvas::FONT_PATH);
            $font->size(48);
            $font->color('#ffffff');
            $font->align('center');
            $font->wrap($contentWidth);
        }) + $gap;

        $date = $event->starts_at ? $event->starts_at->translatedFormat('d \d\e F \d\e Y') : 'Data em breve';
        $location = $event->location ?: 'Confira a programação';
        $tools->drawTextBlock($canvas, "{$date} · {$location}", $centerX, $cursorY, function (FontFactory $font) use ($contentWidth) {
            $font->filename(SocialAssetCanvas::FONT_PATH);
            $font->size(28);
            $font->color('rgba(255, 255, 255, 0.85)');
            $font->align('center');
            $font->wrap($contentWidth);
        });

        $this->drawCta($canvas, $width, $height);
    }

    private function drawCta(ImageInterface $canvas, int $width, int $height): void
    {
        $ctaWidth = 300;
        $ctaHeight = 86;
        $ctaX = (int) (($width - $ctaWidth) / 2);
        $ctaY = $height - 200;

        $canvas->drawRectangle($ctaX, $ctaY, function ($rectangle) use ($ctaWidth, $ctaHeight) {
            $rectangle->size($ctaWidth, $ctaHeight);
            $rectangle->background(self::ALERT_COLOR);
        });
        $canvas->text('Garanta já', (int) ($width / 2), $ctaY + ($ctaHeight / 2), function (FontFactory $font) {
            $font->filename(SocialAssetCanvas::FONT_PATH);
            $font->size(30);
            $font->color('#ffffff');
            $font->align('center');
            $font->valign('middle');
        });
    }
}

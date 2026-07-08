<?php

namespace App\Services\SocialAssets\Templates;

use App\Models\Event;
use App\Services\SocialAssets\SocialAssetCanvas;
use Illuminate\Support\Str;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Typography\FontFactory;

class TomorrowTemplate implements SocialAssetTemplate
{
    private const RIBBON_HEIGHT = 170;

    public function compose(ImageInterface $canvas, SocialAssetCanvas $tools, array $context): void
    {
        /** @var Event $event */
        $event = $context['event'];
        $format = $context['format'];
        $width = $context['width'];
        $height = $context['height'];
        $secondaryColor = $context['secondary_color'];

        $centerX = (int) ($width / 2);
        $contentWidth = $width - (SocialAssetCanvas::PADDING * 2);
        $ribbonTop = (int) round($height * ($format === 'story' ? 0.33 : 0.20));

        $canvas->drawRectangle(0, $ribbonTop, function ($rectangle) use ($width, $secondaryColor) {
            $rectangle->size($width, self::RIBBON_HEIGHT);
            $rectangle->background($secondaryColor);
        });
        $canvas->text('É AMANHÃ!', $centerX, $ribbonTop + (self::RIBBON_HEIGHT / 2), function (FontFactory $font) use ($contentWidth) {
            $font->filename(SocialAssetCanvas::FONT_PATH);
            $font->size(72);
            $font->color('#ffffff');
            $font->align('center');
            $font->valign('middle');
            $font->wrap($contentWidth);
        });

        $cursorY = $ribbonTop + self::RIBBON_HEIGHT + 50;
        $gap = 24;

        $cursorY += $tools->drawTextBlock($canvas, 'Prepare-se, a gente se vê lá!', $centerX, $cursorY, function (FontFactory $font) use ($contentWidth) {
            $font->filename(SocialAssetCanvas::FONT_PATH);
            $font->size(30);
            $font->color('#ffffff');
            $font->align('center');
            $font->wrap($contentWidth);
        }) + $gap;

        $name = Str::limit($event->name, 70, '…');
        $cursorY += $tools->drawTextBlock($canvas, $name, $centerX, $cursorY, function (FontFactory $font) use ($contentWidth) {
            $font->filename(SocialAssetCanvas::FONT_PATH);
            $font->size(46);
            $font->color('#ffffff');
            $font->align('center');
            $font->wrap($contentWidth);
        }) + $gap;

        $date = $event->starts_at
            ? $event->starts_at->translatedFormat('d \d\e F \d\e Y \à\s H\hi')
            : 'Data em breve';
        $cursorY += $tools->drawTextBlock($canvas, $date, $centerX, $cursorY, function (FontFactory $font) use ($contentWidth) {
            $font->filename(SocialAssetCanvas::FONT_PATH);
            $font->size(28);
            $font->color('rgba(255, 255, 255, 0.9)');
            $font->align('center');
            $font->wrap($contentWidth);
        }) + $gap;

        $location = $event->location ?: 'Confira a programação';
        $tools->drawTextBlock($canvas, $location, $centerX, $cursorY, function (FontFactory $font) use ($contentWidth) {
            $font->filename(SocialAssetCanvas::FONT_PATH);
            $font->size(26);
            $font->color('rgba(255, 255, 255, 0.8)');
            $font->align('center');
            $font->wrap($contentWidth);
        });

        $this->drawCta($canvas, $width, $height, $secondaryColor);
    }

    private function drawCta(ImageInterface $canvas, int $width, int $height, string $secondaryColor): void
    {
        $ctaWidth = 300;
        $ctaHeight = 86;
        $ctaX = (int) (($width - $ctaWidth) / 2);
        $ctaY = $height - 200;

        $canvas->drawRectangle($ctaX, $ctaY, function ($rectangle) use ($ctaWidth, $ctaHeight, $secondaryColor) {
            $rectangle->size($ctaWidth, $ctaHeight);
            $rectangle->background($secondaryColor);
        });
        $canvas->text('Não perca!', (int) ($width / 2), $ctaY + ($ctaHeight / 2), function (FontFactory $font) {
            $font->filename(SocialAssetCanvas::FONT_PATH);
            $font->size(30);
            $font->color('#ffffff');
            $font->align('center');
            $font->valign('middle');
        });
    }
}

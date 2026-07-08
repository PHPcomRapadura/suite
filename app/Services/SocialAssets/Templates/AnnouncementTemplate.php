<?php

namespace App\Services\SocialAssets\Templates;

use App\Models\Event;
use App\Services\SocialAssets\SocialAssetCanvas;
use Illuminate\Support\Str;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Typography\FontFactory;

class AnnouncementTemplate implements SocialAssetTemplate
{
    public function compose(ImageInterface $canvas, SocialAssetCanvas $tools, array $context): void
    {
        /** @var Event $event */
        $event = $context['event'];
        $format = $context['format'];
        $height = $context['height'];
        $width = $context['width'];
        $tagline = $context['tagline'];
        $secondaryColor = $context['secondary_color'];

        $contentWidth = $width - (SocialAssetCanvas::PADDING * 2);
        $cursorY = (int) round($height * ($format === 'story' ? 0.42 : 0.30));
        $gap = 30;

        $title = Str::limit($event->name, 70, '…');
        $cursorY += $tools->drawTextBlock($canvas, $title, SocialAssetCanvas::PADDING, $cursorY, function (FontFactory $font) use ($contentWidth) {
            $font->filename(SocialAssetCanvas::FONT_PATH);
            $font->size(50);
            $font->color('#ffffff');
            $font->lineHeight(1.15);
            $font->wrap($contentWidth);
        }) + $gap;

        if ($tagline) {
            $cursorY += $tools->drawTextBlock($canvas, $tagline, SocialAssetCanvas::PADDING, $cursorY, function (FontFactory $font) use ($contentWidth) {
                $font->filename(SocialAssetCanvas::FONT_PATH);
                $font->size(26);
                $font->color('rgba(255, 255, 255, 0.95)');
                $font->wrap($contentWidth);
            }) + $gap;
        }

        $date = $event->starts_at ? $event->starts_at->translatedFormat('d \d\e F \d\e Y') : 'Data em breve';
        $location = $event->location ?: 'Confira a programação';
        $cursorY += $tools->drawTextBlock($canvas, "{$date} · {$location}", SocialAssetCanvas::PADDING, $cursorY, function (FontFactory $font) use ($contentWidth) {
            $font->filename(SocialAssetCanvas::FONT_PATH);
            $font->size(28);
            $font->color('#ffffff');
            $font->wrap($contentWidth);
        }) + $gap;

        if ($event->description) {
            $description = Str::limit($event->description, 150, '…');
            $tools->drawTextBlock($canvas, $description, SocialAssetCanvas::PADDING, $cursorY, function (FontFactory $font) use ($contentWidth) {
                $font->filename(SocialAssetCanvas::FONT_PATH);
                $font->size(24);
                $font->color('rgba(255, 255, 255, 0.9)');
                $font->lineHeight(1.3);
                $font->wrap($contentWidth);
            });
        }

        $ctaY = $height - 200;
        $canvas->drawRectangle(SocialAssetCanvas::PADDING, $ctaY, function ($rectangle) use ($secondaryColor) {
            $rectangle->size(300, 86);
            $rectangle->background($secondaryColor);
        });
        $canvas->text('Garanta sua vaga', SocialAssetCanvas::PADDING + 28, $ctaY + 28, function (FontFactory $font) {
            $font->filename(SocialAssetCanvas::FONT_PATH);
            $font->size(28);
            $font->color('#ffffff');
        });
    }
}

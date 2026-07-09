<?php

namespace App\Services\SocialAssets\Templates;

use App\Models\Event;
use App\Services\SocialAssets\EventMeta;
use App\Services\SocialAssets\SocialAssetCanvas;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Typography\FontFactory;

class TomorrowTemplate implements SocialAssetTemplate
{
    private const RIBBON_HEIGHT = 220;

    public function compose(ImageInterface $canvas, SocialAssetCanvas $tools, array $context): void
    {
        /** @var Event $event */
        $event = $context['event'];
        $format = $context['format'];
        $width = $context['width'];
        $height = $context['height'];
        $secondaryColor = $context['secondary_color'];

        $isStory = $format === 'story';
        $hasFooter = ! empty($context['has_sponsor_footer']);
        $centerX = (int) ($width / 2);
        $contentWidth = $width - (SocialAssetCanvas::PADDING * 2);

        $tools->drawScrim($canvas, $width, $height, $isStory ? 0.50 : 0.40);

        $ribbonRatio = $isStory ? ($hasFooter ? 0.28 : 0.36) : ($hasFooter ? 0.16 : 0.22);
        // No post a proporção pode cair acima da área do logo da comunidade
        // (PADDING + 140px) — clampar para a faixa nunca cobri-lo.
        $ribbonTop = max((int) round($height * $ribbonRatio), SocialAssetCanvas::PADDING + 160);
        $this->drawRibbon($canvas, $centerX, $ribbonTop, $width, $contentWidth, $secondaryColor);

        $cursorY = $ribbonTop + self::RIBBON_HEIGHT + 60;

        $cursorY += $tools->drawTextBlock($canvas, 'Prepare-se, a gente se vê lá!', $centerX, $cursorY, function (FontFactory $font) use ($contentWidth) {
            $font->filename(SocialAssetCanvas::FONT_SEMIBOLD);
            $font->size(34);
            $font->color('#ffffff');
            $font->align('center');
            $font->wrap($contentWidth);
        }) + 30;

        $cursorY += $tools->drawTextBlock($canvas, $event->name, $centerX, $cursorY, function (FontFactory $font) use ($contentWidth) {
            $font->filename(SocialAssetCanvas::FONT_BOLD);
            $font->size(66);
            $font->color('#ffffff');
            $font->lineHeight(1.08);
            $font->align('center');
            $font->wrap($contentWidth);
        }) + 22;

        $cursorY += $tools->drawTextBlock($canvas, EventMeta::dateTime($event), $centerX, $cursorY, function (FontFactory $font) use ($contentWidth) {
            $font->filename(SocialAssetCanvas::FONT_SEMIBOLD);
            $font->size(32);
            $font->color('rgba(255, 255, 255, 0.9)');
            $font->align('center');
            $font->wrap($contentWidth);
        }) + 10;

        $location = EventMeta::location($event);
        if ($location) {
            $cursorY += $tools->drawTextBlock($canvas, $location, $centerX, $cursorY, function (FontFactory $font) use ($contentWidth) {
                $font->filename(SocialAssetCanvas::FONT_REGULAR);
                $font->size(30);
                $font->color('rgba(255, 255, 255, 0.8)');
                $font->align('center');
                $font->wrap($contentWidth);
            });
        }

        $tools->drawButton($canvas, 'Não perca!', $centerX, $cursorY + 66, $secondaryColor);
    }

    private function drawRibbon(ImageInterface $canvas, int $centerX, int $ribbonTop, int $width, int $contentWidth, string $secondaryColor): void
    {
        $canvas->drawRectangle(0, $ribbonTop, function ($rectangle) use ($width, $secondaryColor) {
            $rectangle->size($width, self::RIBBON_HEIGHT);
            $rectangle->background($secondaryColor);
        });
        $canvas->drawRectangle(0, $ribbonTop + self::RIBBON_HEIGHT + 10, function ($rectangle) use ($width, $secondaryColor) {
            $rectangle->size($width, 12);
            $rectangle->background($secondaryColor);
        });

        $processor = $canvas->driver()->fontProcessor();
        $font = (new FontFactory(function (FontFactory $f) use ($contentWidth) {
            $f->filename(SocialAssetCanvas::FONT_BLACK);
            $f->size(100);
            $f->color('#ffffff');
            $f->align('center');
            $f->wrap($contentWidth);
        }))();
        $capHeight = $processor->capHeight($font);
        $font->setValignment('top');
        $textY = (int) ($ribbonTop + ((self::RIBBON_HEIGHT - $capHeight) / 2));
        $canvas->text('É AMANHÃ!', $centerX, $textY, $font);
    }
}

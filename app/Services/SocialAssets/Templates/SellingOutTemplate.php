<?php

namespace App\Services\SocialAssets\Templates;

use App\Models\Event;
use App\Services\SocialAssets\EventMeta;
use App\Services\SocialAssets\SocialAssetCanvas;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Typography\FontFactory;

class SellingOutTemplate implements SocialAssetTemplate
{
    private const ALERT_COLOR = '#dc2626';

    private const RIBBON_HEIGHT = 210;

    public function compose(ImageInterface $canvas, SocialAssetCanvas $tools, array $context): void
    {
        /** @var Event $event */
        $event = $context['event'];
        $format = $context['format'];
        $width = $context['width'];
        $height = $context['height'];

        $isStory = $format === 'story';
        $hasFooter = ! empty($context['has_sponsor_footer']);
        $centerX = (int) ($width / 2);
        $contentWidth = $width - (SocialAssetCanvas::PADDING * 2);

        $tools->drawScrim($canvas, $width, $height, $isStory ? 0.50 : 0.40);

        $ribbonRatio = $isStory ? ($hasFooter ? 0.30 : 0.38) : ($hasFooter ? 0.18 : 0.24);
        // No post a proporção pode cair acima da área do logo da comunidade
        // (PADDING + 140px) — clampar para a faixa nunca cobri-lo.
        $ribbonTop = max((int) round($height * $ribbonRatio), SocialAssetCanvas::PADDING + 160);
        $this->drawRibbon($canvas, $centerX, $ribbonTop, $width, $contentWidth);

        $cursorY = $ribbonTop + self::RIBBON_HEIGHT + 60;

        $cursorY += $tools->drawTextBlock($canvas, 'Garanta o seu agora antes que acabe!', $centerX, $cursorY, function (FontFactory $font) use ($contentWidth) {
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

        [$date, $location] = EventMeta::dateAndLocation($event);

        $cursorY += $tools->drawTextBlock($canvas, $date, $centerX, $cursorY, function (FontFactory $font) use ($contentWidth) {
            $font->filename(SocialAssetCanvas::FONT_SEMIBOLD);
            $font->size(32);
            $font->color('rgba(255, 255, 255, 0.9)');
            $font->align('center');
            $font->wrap($contentWidth);
        }) + 10;

        if ($location) {
            $cursorY += $tools->drawTextBlock($canvas, $location, $centerX, $cursorY, function (FontFactory $font) use ($contentWidth) {
                $font->filename(SocialAssetCanvas::FONT_REGULAR);
                $font->size(30);
                $font->color('rgba(255, 255, 255, 0.8)');
                $font->align('center');
                $font->wrap($contentWidth);
            });
        }

        $tools->drawButton($canvas, 'Garanta já', $centerX, $cursorY + 66, self::ALERT_COLOR);
    }

    private function drawRibbon(ImageInterface $canvas, int $centerX, int $ribbonTop, int $width, int $contentWidth): void
    {
        // faixa principal full-bleed
        $canvas->drawRectangle(0, $ribbonTop, function ($rectangle) use ($width) {
            $rectangle->size($width, self::RIBBON_HEIGHT);
            $rectangle->background(self::ALERT_COLOR);
        });
        // segunda faixa fina deslocada abaixo (detalhe)
        $canvas->drawRectangle(0, $ribbonTop + self::RIBBON_HEIGHT + 10, function ($rectangle) use ($width) {
            $rectangle->size($width, 12);
            $rectangle->background(self::ALERT_COLOR);
        });

        $processor = $canvas->driver()->fontProcessor();
        $font = (new FontFactory(function (FontFactory $f) use ($contentWidth) {
            $f->filename(SocialAssetCanvas::FONT_BLACK);
            $f->size(92);
            $f->color('#ffffff');
            $f->align('center');
            $f->wrap($contentWidth);
        }))();
        $capHeight = $processor->capHeight($font);
        $font->setValignment('top');
        $textY = (int) ($ribbonTop + ((self::RIBBON_HEIGHT - $capHeight) / 2));
        $canvas->text('INGRESSOS ESGOTANDO', $centerX, $textY, $font);
    }
}

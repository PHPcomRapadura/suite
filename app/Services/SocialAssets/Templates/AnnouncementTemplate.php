<?php

namespace App\Services\SocialAssets\Templates;

use App\Models\Event;
use App\Services\SocialAssets\EventMeta;
use App\Services\SocialAssets\SocialAssetCanvas;
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
        $secondaryColor = $context['secondary_color'];

        $isStory = $format === 'story';
        $hasFooter = ! empty($context['has_sponsor_footer']);
        $left = SocialAssetCanvas::PADDING;
        $contentWidth = $width - (SocialAssetCanvas::PADDING * 2);

        $tools->drawScrim($canvas, $width, $height, $isStory ? 0.40 : 0.30);

        $date = EventMeta::dateTime($event);
        $location = EventMeta::fullLocation($event);
        $titleSize = $isStory ? 104 : 84;

        // Bloco de conteúdo ancorado embaixo (story) / metade inferior (post),
        // dentro das safe zones do Instagram (~250px topo/rodapé no story).
        // Quando há rodapé de patrocínio, sobe o bloco para não colidir.
        $anchor = $isStory ? ($hasFooter ? 0.46 : 0.56) : ($hasFooter ? 0.30 : 0.42);
        $cursorY = (int) round($height * $anchor);

        // kicker
        $cursorY += $tools->drawTextBlock($canvas, 'EVENTO', $left, $cursorY, function (FontFactory $font) use ($secondaryColor) {
            $font->filename(SocialAssetCanvas::FONT_SEMIBOLD);
            $font->size(30);
            $font->color($secondaryColor);
        }) + 24;

        // barra de acento
        $tools->drawAccentBar($canvas, $left, $cursorY, $secondaryColor);
        $cursorY += 8 + 34;

        // título-herói em Black
        $cursorY += $tools->drawTextBlock($canvas, $event->name, $left, $cursorY, function (FontFactory $font) use ($contentWidth, $titleSize) {
            $font->filename(SocialAssetCanvas::FONT_BLACK);
            $font->size($titleSize);
            $font->color('#ffffff');
            $font->lineHeight(1.05);
            $font->wrap($contentWidth);
        }) + 40;

        // data (SemiBold, linha própria)
        $cursorY += $tools->drawTextBlock($canvas, $date, $left, $cursorY, function (FontFactory $font) {
            $font->filename(SocialAssetCanvas::FONT_SEMIBOLD);
            $font->size(34);
            $font->color('#ffffff');
        }) + 12;

        // endereço completo (Regular, linha própria)
        if ($location) {
            $tools->drawTextBlock($canvas, $location, $left, $cursorY, function (FontFactory $font) use ($contentWidth) {
                $font->filename(SocialAssetCanvas::FONT_REGULAR);
                $font->size(32);
                $font->color('rgba(255, 255, 255, 0.85)');
                $font->wrap($contentWidth);
            });
        }
    }
}

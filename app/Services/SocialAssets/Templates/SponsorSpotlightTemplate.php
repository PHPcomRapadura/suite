<?php

namespace App\Services\SocialAssets\Templates;

use App\Models\Event;
use App\Models\EventSponsor;
use App\Services\SocialAssets\EventMeta;
use App\Services\SocialAssets\SocialAssetCanvas;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Typography\FontFactory;

class SponsorSpotlightTemplate implements SocialAssetTemplate
{
    private const LEVEL_LABELS = [
        'rapadura_com_castanha' => 'Rapadura com Castanha',
        'rapadura_com_coco' => 'Rapadura com Coco',
        'rapadura_tradicional' => 'Rapadura Tradicional',
    ];

    private const CARD_WIDTH = 800;

    private const CARD_HEIGHT = 440;

    private const CARD_RADIUS = 32;

    public function compose(ImageInterface $canvas, SocialAssetCanvas $tools, array $context): void
    {
        /** @var EventSponsor $sponsor */
        $sponsor = $context['sponsor'];
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

        $tools->drawScrim($canvas, $width, $height, $isStory ? 0.40 : 0.30);

        // Cartão menor no post (espaço curto) para caber CTA + rodapé.
        $cardWidth = $isStory ? self::CARD_WIDTH : 620;
        $cardHeight = $isStory ? self::CARD_HEIGHT : 340;

        // kicker
        $kickerY = (int) round($height * ($isStory ? 0.16 : 0.08));
        $tools->drawTextBlock($canvas, 'PATROCINADOR OFICIAL', $centerX, $kickerY, function (FontFactory $font) use ($contentWidth, $secondaryColor) {
            $font->filename(SocialAssetCanvas::FONT_SEMIBOLD);
            $font->size(30);
            $font->color($secondaryColor);
            $font->align('center');
            $font->wrap($contentWidth);
        });

        $cardTop = $kickerY + 60;
        $this->drawLogoCard($canvas, $tools, $sponsor, $centerX, $cardTop, $cardWidth, $cardHeight);

        // badge de nível sobreposto à borda inferior do cartão
        $badgeLabel = self::LEVEL_LABELS[$sponsor->level] ?? 'Patrocinador';
        $badgeY = $cardTop + $cardHeight - 29;
        $tools->drawPillLabel($canvas, mb_strtoupper($badgeLabel), $centerX, $badgeY, $secondaryColor);

        $cursorY = $cardTop + $cardHeight + ($isStory ? 80 : 60);

        // evento + data (meta)
        $cursorY += $tools->drawTextBlock($canvas, $event->name, $centerX, $cursorY, function (FontFactory $font) use ($contentWidth, $isStory) {
            $font->filename(SocialAssetCanvas::FONT_BOLD);
            $font->size($isStory ? 52 : 44);
            $font->color('#ffffff');
            $font->lineHeight(1.1);
            $font->align('center');
            $font->wrap($contentWidth);
        }) + 14;

        $cursorY += $tools->drawTextBlock($canvas, EventMeta::date($event), $centerX, $cursorY, function (FontFactory $font) use ($contentWidth) {
            $font->filename(SocialAssetCanvas::FONT_REGULAR);
            $font->size(30);
            $font->color('rgba(255, 255, 255, 0.82)');
            $font->align('center');
            $font->wrap($contentWidth);
        });

        $footerReserve = $hasFooter ? ($isStory ? 320 : 230) : ($isStory ? 200 : 130);
        $ctaY = min($cursorY + 50, $height - $footerReserve - 100);
        $tools->drawButton($canvas, 'Conheça nosso patrocinador', $centerX, $ctaY, $secondaryColor, '#ffffff', 30);
    }

    private function drawLogoCard(ImageInterface $canvas, SocialAssetCanvas $tools, EventSponsor $sponsor, int $centerX, int $cardTop, int $cardWidth, int $cardHeight): void
    {
        $cardX = $centerX - (int) ($cardWidth / 2);
        $tools->drawRoundedCard($canvas, $cardX, $cardTop, $cardWidth, $cardHeight, '#ffffff', self::CARD_RADIUS);

        $logo = $tools->fetchImage($sponsor->logo_url);

        if ($logo) {
            $logo->contain($cardWidth - 120, $cardHeight - 120, '#ffffff', 'center');
            $logoX = $centerX - (int) ($logo->width() / 2);
            $logoY = $cardTop + (int) (($cardHeight - $logo->height()) / 2);
            $canvas->place($logo, 'top-left', $logoX, $logoY);

            return;
        }

        // fallback sem logo: nome do patrocinador dentro do cartão
        $canvas->text($sponsor->name, $centerX, $cardTop + (int) ($cardHeight / 2), function (FontFactory $font) use ($cardWidth) {
            $font->filename(SocialAssetCanvas::FONT_BOLD);
            $font->size(48);
            $font->color('#111827');
            $font->align('center');
            $font->valign('middle');
            $font->wrap($cardWidth - 120);
        });
    }
}

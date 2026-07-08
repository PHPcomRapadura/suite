<?php

namespace App\Services\SocialAssets\Templates;

use App\Models\Event;
use App\Models\EventSponsor;
use App\Services\SocialAssets\SocialAssetCanvas;
use Illuminate\Support\Str;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Typography\FontFactory;

class SponsorSpotlightTemplate implements SocialAssetTemplate
{
    private const LEVEL_LABELS = [
        'rapadura_com_castanha' => 'Rapadura com Castanha',
        'rapadura_com_coco' => 'Rapadura com Coco',
        'rapadura_tradicional' => 'Rapadura Tradicional',
    ];

    private const CARD_WIDTH = 780;

    private const CARD_HEIGHT = 440;

    private const CARD_TOP = 260;

    public function compose(ImageInterface $canvas, SocialAssetCanvas $tools, array $context): void
    {
        /** @var EventSponsor $sponsor */
        $sponsor = $context['sponsor'];
        /** @var Event $event */
        $event = $context['event'];
        $width = $context['width'];
        $height = $context['height'];
        $secondaryColor = $context['secondary_color'];

        $centerX = (int) ($width / 2);
        $contentWidth = $width - (SocialAssetCanvas::PADDING * 2);

        $badgeLabel = self::LEVEL_LABELS[$sponsor->level] ?? 'Patrocinador';
        $canvas->text(mb_strtoupper("Patrocinador {$badgeLabel}"), $centerX, self::CARD_TOP - 70, function (FontFactory $font) use ($contentWidth, $secondaryColor) {
            $font->filename(SocialAssetCanvas::FONT_PATH);
            $font->size(24);
            $font->color($secondaryColor);
            $font->align('center');
            $font->valign('top');
            $font->wrap($contentWidth);
        });

        $this->drawLogoCard($canvas, $tools, $sponsor, $centerX);

        $cursorY = self::CARD_TOP + self::CARD_HEIGHT + 50;
        $gap = 24;

        $name = Str::limit($sponsor->name, 70, '…');
        $cursorY += $tools->drawTextBlock($canvas, $name, $centerX, $cursorY, function (FontFactory $font) use ($contentWidth) {
            $font->filename(SocialAssetCanvas::FONT_PATH);
            $font->size(44);
            $font->color('#ffffff');
            $font->align('center');
            $font->wrap($contentWidth);
        }) + $gap;

        $date = $event->starts_at ? $event->starts_at->translatedFormat('d \d\e F \d\e Y') : 'Data em breve';
        $tools->drawTextBlock($canvas, "{$event->name} · {$date}", $centerX, $cursorY, function (FontFactory $font) use ($contentWidth) {
            $font->filename(SocialAssetCanvas::FONT_PATH);
            $font->size(28);
            $font->color('rgba(255, 255, 255, 0.85)');
            $font->align('center');
            $font->wrap($contentWidth);
        });

        $this->drawCta($canvas, $width, $height, $secondaryColor);
    }

    private function drawLogoCard(ImageInterface $canvas, SocialAssetCanvas $tools, EventSponsor $sponsor, int $centerX): void
    {
        $cardX = $centerX - (int) (self::CARD_WIDTH / 2);
        $canvas->drawRectangle($cardX, self::CARD_TOP, function ($rectangle) {
            $rectangle->size(self::CARD_WIDTH, self::CARD_HEIGHT);
            $rectangle->background('#ffffff');
        });

        $logo = $tools->fetchImage($sponsor->logo_url);

        if ($logo) {
            $logo->contain(self::CARD_WIDTH - 80, self::CARD_HEIGHT - 80, '#ffffff', 'center');
            $logoX = $centerX - (int) ($logo->width() / 2);
            $logoY = self::CARD_TOP + (int) ((self::CARD_HEIGHT - $logo->height()) / 2);
            $canvas->place($logo, 'top-left', $logoX, $logoY);

            return;
        }

        $canvas->text($sponsor->name, $centerX, self::CARD_TOP + (int) (self::CARD_HEIGHT / 2), function (FontFactory $font) {
            $font->filename(SocialAssetCanvas::FONT_PATH);
            $font->size(40);
            $font->color('#111827');
            $font->align('center');
            $font->valign('middle');
            $font->wrap(self::CARD_WIDTH - 80);
        });
    }

    private function drawCta(ImageInterface $canvas, int $width, int $height, string $secondaryColor): void
    {
        $ctaWidth = 420;
        $ctaHeight = 86;
        $ctaX = (int) (($width - $ctaWidth) / 2);
        $ctaY = $height - 200;

        $canvas->drawRectangle($ctaX, $ctaY, function ($rectangle) use ($ctaWidth, $ctaHeight, $secondaryColor) {
            $rectangle->size($ctaWidth, $ctaHeight);
            $rectangle->background($secondaryColor);
        });
        $canvas->text('Conheça nosso patrocinador', (int) ($width / 2), $ctaY + ($ctaHeight / 2), function (FontFactory $font) {
            $font->filename(SocialAssetCanvas::FONT_PATH);
            $font->size(26);
            $font->color('#ffffff');
            $font->align('center');
            $font->valign('middle');
        });
    }
}

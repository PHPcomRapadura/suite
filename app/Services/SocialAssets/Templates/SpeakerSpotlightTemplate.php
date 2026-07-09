<?php

namespace App\Services\SocialAssets\Templates;

use App\Models\Event;
use App\Models\Speaker;
use App\Models\Talk;
use App\Services\SocialAssets\EventMeta;
use App\Services\SocialAssets\SocialAssetCanvas;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Typography\FontFactory;

class SpeakerSpotlightTemplate implements SocialAssetTemplate
{
    private const AVATAR_SIZE = 320;

    private const RING_WIDTH = 8;

    public function compose(ImageInterface $canvas, SocialAssetCanvas $tools, array $context): void
    {
        /** @var Talk $talk */
        $talk = $context['talk'];
        /** @var Event $event */
        $event = $context['event'];
        $format = $context['format'];
        $width = $context['width'];
        $height = $context['height'];
        $secondaryColor = $context['secondary_color'];

        $isStory = $format === 'story';
        $hasFooter = ! empty($context['has_sponsor_footer']);
        $speaker = $talk->speaker;
        $centerX = (int) ($width / 2);
        $contentWidth = $width - (SocialAssetCanvas::PADDING * 2);

        $tools->drawScrim($canvas, $width, $height, $isStory ? 0.42 : 0.30, 0.90);

        // Em post o espaço é curto: avatar menor e mais no topo.
        $avatarSize = $isStory ? self::AVATAR_SIZE : 240;
        $avatarTop = (int) round($height * ($isStory ? 0.14 : 0.06));
        $this->drawAvatar($canvas, $tools, $speaker?->avatar_url, $speaker?->user?->name, $centerX, $avatarTop, $avatarSize, $secondaryColor);

        $cursorY = $avatarTop + $avatarSize + self::RING_WIDTH + ($isStory ? 56 : 36);

        // kicker
        $cursorY += $tools->drawTextBlock($canvas, 'PALESTRA CONFIRMADA', $centerX, $cursorY, function (FontFactory $font) use ($contentWidth, $secondaryColor) {
            $font->filename(SocialAssetCanvas::FONT_SEMIBOLD);
            $font->size(30);
            $font->color($secondaryColor);
            $font->align('center');
            $font->wrap($contentWidth);
        }) + 22;

        // nome do palestrante (Bold, herói primário)
        $nameSize = $isStory ? 76 : 60;
        $cursorY += $tools->drawTextBlock($canvas, $speaker?->user?->name ?: 'Palestrante', $centerX, $cursorY, function (FontFactory $font) use ($contentWidth, $nameSize) {
            $font->filename(SocialAssetCanvas::FONT_BOLD);
            $font->size($nameSize);
            $font->color('#ffffff');
            $font->lineHeight(1.05);
            $font->align('center');
            $font->wrap($contentWidth);
        }) + 18;

        $companyLocation = collect([$speaker?->company, $this->formatLocation($speaker)])->filter()->implode(' · ');
        if ($companyLocation) {
            $cursorY += $tools->drawTextBlock($canvas, $companyLocation, $centerX, $cursorY, function (FontFactory $font) use ($contentWidth) {
                $font->filename(SocialAssetCanvas::FONT_REGULAR);
                $font->size(30);
                $font->color('rgba(255, 255, 255, 0.82)');
                $font->align('center');
                $font->wrap($contentWidth);
            }) + 34;
        } else {
            $cursorY += 34;
        }

        // título da palestra (SemiBold, herói secundário)
        $titleSize = $isStory ? 52 : 44;
        $cursorY += $tools->drawTextBlock($canvas, $talk->title, $centerX, $cursorY, function (FontFactory $font) use ($contentWidth, $titleSize) {
            $font->filename(SocialAssetCanvas::FONT_SEMIBOLD);
            $font->size($titleSize);
            $font->color('#ffffff');
            $font->lineHeight(1.15);
            $font->align('center');
            $font->wrap($contentWidth);
        }) + 30;

        // evento + data (meta)
        $cursorY += $tools->drawTextBlock($canvas, "{$event->name} · ".EventMeta::date($event), $centerX, $cursorY, function (FontFactory $font) use ($contentWidth) {
            $font->filename(SocialAssetCanvas::FONT_REGULAR);
            $font->size(28);
            $font->color('rgba(255, 255, 255, 0.72)');
            $font->align('center');
            $font->wrap($contentWidth);
        });

        $footerReserve = $hasFooter ? ($isStory ? 320 : 230) : ($isStory ? 200 : 130);
        $ctaY = min($cursorY + 50, $height - $footerReserve - 100);
        $tools->drawButton($canvas, 'Inscreva-se', $centerX, $ctaY, $secondaryColor);
    }

    private function drawAvatar(ImageInterface $canvas, SocialAssetCanvas $tools, ?string $avatarUrl, ?string $name, int $centerX, int $top, int $size, string $secondaryColor): void
    {
        $avatar = $tools->fetchImage($avatarUrl);

        if ($avatar) {
            $tools->drawCircularAvatar($canvas, $avatar, $centerX, $top, $size, $secondaryColor, self::RING_WIDTH);

            return;
        }

        $radius = (int) ($size / 2);
        $avatarCenterY = $top + $radius;

        $canvas->drawCircle($centerX, $avatarCenterY, function ($circle) use ($radius, $secondaryColor) {
            $circle->radius($radius + self::RING_WIDTH);
            $circle->background($secondaryColor);
        });
        $canvas->drawCircle($centerX, $avatarCenterY, function ($circle) use ($radius) {
            $circle->radius($radius);
            $circle->background('rgba(255, 255, 255, 0.14)');
        });

        $canvas->text($this->initials($name), $centerX, $avatarCenterY, function (FontFactory $font) use ($size) {
            $font->filename(SocialAssetCanvas::FONT_BLACK);
            $font->size((int) ($size * 0.38));
            $font->color('#ffffff');
            $font->align('center');
            $font->valign('middle');
        });
    }

    private function initials(?string $name): string
    {
        if (! $name) {
            return '?';
        }

        $words = array_filter(explode(' ', $name));
        $letters = array_map(fn (string $word) => mb_substr($word, 0, 1), array_slice($words, 0, 2));

        return mb_strtoupper(implode('', $letters));
    }

    private function formatLocation(?Speaker $speaker): ?string
    {
        if (! $speaker || (! $speaker->city && ! $speaker->state)) {
            return null;
        }

        return collect([$speaker->city, $speaker->state])->filter()->implode(', ');
    }
}

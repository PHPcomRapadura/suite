<?php

namespace App\Services\SocialAssets\Templates;

use App\Models\Event;
use App\Models\Speaker;
use App\Models\Talk;
use App\Services\SocialAssets\SocialAssetCanvas;
use Illuminate\Support\Str;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Typography\FontFactory;

class SpeakerSpotlightTemplate implements SocialAssetTemplate
{
    private const AVATAR_SIZE = 300;

    private const AVATAR_TOP = 160;

    private const FRAME_GAP = 14;

    public function compose(ImageInterface $canvas, SocialAssetCanvas $tools, array $context): void
    {
        /** @var Talk $talk */
        $talk = $context['talk'];
        /** @var Event $event */
        $event = $context['event'];
        $width = $context['width'];
        $height = $context['height'];
        $secondaryColor = $context['secondary_color'];

        $speaker = $talk->speaker;
        $centerX = (int) ($width / 2);
        $contentWidth = $width - (SocialAssetCanvas::PADDING * 2);

        $this->drawAvatar($canvas, $tools, $speaker?->avatar_url, $speaker?->user?->name, $centerX, $secondaryColor);

        $cursorY = self::AVATAR_TOP + self::AVATAR_SIZE + self::FRAME_GAP + 50;
        $gap = 24;

        $cursorY += $tools->drawTextBlock($canvas, $speaker?->user?->name ?: 'Palestrante', $centerX, $cursorY, function (FontFactory $font) use ($contentWidth) {
            $font->filename(SocialAssetCanvas::FONT_PATH);
            $font->size(50);
            $font->color('#ffffff');
            $font->align('center');
            $font->wrap($contentWidth);
        }) + $gap;

        $companyLocation = collect([$speaker?->company, $this->formatLocation($speaker)])->filter()->implode(' · ');
        if ($companyLocation) {
            $cursorY += $tools->drawTextBlock($canvas, $companyLocation, $centerX, $cursorY, function (FontFactory $font) use ($contentWidth) {
                $font->filename(SocialAssetCanvas::FONT_PATH);
                $font->size(28);
                $font->color('rgba(255, 255, 255, 0.85)');
                $font->align('center');
                $font->wrap($contentWidth);
            }) + $gap;
        }

        $cursorY += $tools->drawTextBlock($canvas, 'PALESTRA', $centerX, $cursorY, function (FontFactory $font) use ($contentWidth, $secondaryColor) {
            $font->filename(SocialAssetCanvas::FONT_PATH);
            $font->size(22);
            $font->color($secondaryColor);
            $font->align('center');
            $font->wrap($contentWidth);
        }) + $gap;

        $title = Str::limit($talk->title, 90, '…');
        $cursorY += $tools->drawTextBlock($canvas, $title, $centerX, $cursorY, function (FontFactory $font) use ($contentWidth) {
            $font->filename(SocialAssetCanvas::FONT_PATH);
            $font->size(36);
            $font->color('#ffffff');
            $font->lineHeight(1.2);
            $font->align('center');
            $font->wrap($contentWidth);
        }) + $gap;

        $date = $event->starts_at ? $event->starts_at->translatedFormat('d \d\e F \d\e Y') : 'Data em breve';
        $tools->drawTextBlock($canvas, "{$event->name} · {$date}", $centerX, $cursorY, function (FontFactory $font) use ($contentWidth) {
            $font->filename(SocialAssetCanvas::FONT_PATH);
            $font->size(24);
            $font->color('rgba(255, 255, 255, 0.75)');
            $font->align('center');
            $font->wrap($contentWidth);
        });

        $this->drawCta($canvas, $width, $height, $secondaryColor);
    }

    private function drawAvatar(ImageInterface $canvas, SocialAssetCanvas $tools, ?string $avatarUrl, ?string $name, int $centerX, string $secondaryColor): void
    {
        $avatar = $tools->fetchImage($avatarUrl);

        if ($avatar) {
            $avatar->cover(self::AVATAR_SIZE, self::AVATAR_SIZE);

            $frameSize = self::AVATAR_SIZE + (self::FRAME_GAP * 2);
            $frameX = $centerX - (int) ($frameSize / 2);
            $canvas->drawRectangle($frameX, self::AVATAR_TOP - self::FRAME_GAP, function ($rectangle) use ($frameSize, $secondaryColor) {
                $rectangle->size($frameSize, $frameSize);
                $rectangle->background($secondaryColor);
            });

            $avatarX = $centerX - (int) (self::AVATAR_SIZE / 2);
            $canvas->place($avatar, 'top-left', $avatarX, self::AVATAR_TOP);

            return;
        }

        $radius = (int) (self::AVATAR_SIZE / 2);
        $avatarCenterY = self::AVATAR_TOP + $radius;

        $canvas->drawCircle($centerX, $avatarCenterY, function ($circle) use ($radius, $secondaryColor) {
            $circle->radius($radius);
            $circle->background($secondaryColor);
        });

        $canvas->text($this->initials($name), $centerX, $avatarCenterY, function (FontFactory $font) {
            $font->filename(SocialAssetCanvas::FONT_PATH);
            $font->size(110);
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
        $canvas->text('Inscreva-se', (int) ($width / 2), $ctaY + ($ctaHeight / 2), function (FontFactory $font) {
            $font->filename(SocialAssetCanvas::FONT_PATH);
            $font->size(28);
            $font->color('#ffffff');
            $font->align('center');
            $font->valign('middle');
        });
    }
}

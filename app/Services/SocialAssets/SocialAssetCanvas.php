<?php

namespace App\Services\SocialAssets;

use Closure;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Geometry\Point;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Typography\FontFactory;
use Throwable;

/**
 * Primitivos de desenho compartilhados por todos os templates de arte de
 * divulgação (fundo, overlay, logo, texto medido). Não conhece Event nem
 * nenhum outro model — recebe URLs/cores/textos prontos.
 */
class SocialAssetCanvas
{
    public const PADDING = 70;

    private const OVERLAY_COLOR = 'rgba(0, 0, 0, 0.45)';

    private const LOGO_SIZE = 140;

    // O canvas é rasterizado com GD, que não sabe carregar fontes web (woff/woff2)
    // usadas pelo restante do app; por isso usamos sempre a Lexend variável embutida,
    // independente da fonte escolhida em EventSiteConfig::font.
    public const FONT_PATH = __DIR__.'/../../../resources/fonts/Lexend-Variable.ttf';

    private ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver);
    }

    public function buildBackground(?string $coverImageUrl, int $width, int $height, string $primaryColor, string $secondaryColor): ImageInterface
    {
        $cover = $this->fetchImage($coverImageUrl);

        if ($cover) {
            return $cover->cover($width, $height);
        }

        return $this->buildGradient($width, $height, $primaryColor, $secondaryColor);
    }

    public function buildGradient(int $width, int $height, string $from, string $to): ImageInterface
    {
        $canvas = $this->manager->create($width, $height);

        [$r1, $g1, $b1] = $this->hexToRgb($from);
        [$r2, $g2, $b2] = $this->hexToRgb($to);

        $step = 6;

        for ($y = 0; $y < $height; $y += $step) {
            $t = $y / max(1, $height - 1);
            $color = sprintf(
                '#%02x%02x%02x',
                (int) round($r1 + ($r2 - $r1) * $t),
                (int) round($g1 + ($g2 - $g1) * $t),
                (int) round($b1 + ($b2 - $b1) * $t),
            );

            $canvas->drawRectangle(0, $y, function ($rectangle) use ($width, $step, $color) {
                $rectangle->size($width, $step);
                $rectangle->background($color);
            });
        }

        return $canvas;
    }

    public function drawOverlay(ImageInterface $canvas, int $width, int $height): void
    {
        $canvas->drawRectangle(0, 0, function ($rectangle) use ($width, $height) {
            $rectangle->size($width, $height);
            $rectangle->background(self::OVERLAY_COLOR);
        });
    }

    public function drawLogo(ImageInterface $canvas, ?string $logoUrl): void
    {
        $logo = $this->fetchImage($logoUrl);

        if (! $logo) {
            return;
        }

        $logo->scaleDown(width: self::LOGO_SIZE, height: self::LOGO_SIZE);
        $canvas->place($logo, 'top-left', self::PADDING, self::PADDING);
    }

    /**
     * Desenha o bloco de texto e devolve a altura real ocupada (incluindo quebras
     * de linha do wrap), para que o próximo bloco seja posicionado sem sobrepor.
     */
    public function drawTextBlock(ImageInterface $canvas, string $text, int $x, int $y, Closure $fontCallback): int
    {
        $font = (new FontFactory($fontCallback))();
        // O valign padrão da lib é "bottom" (y = base do texto, que cresce para
        // cima) — o oposto do que este método promete ("y = topo do bloco,
        // avança para baixo"). Forçar "top" aqui evita que blocos pequenos
        // seguidos de blocos com fonte bem maior colidam visualmente.
        $font->setValignment('top');

        $canvas->text($text, $x, $y, $font);

        $processor = $canvas->driver()->fontProcessor();
        $lineCount = count($processor->textBlock($text, $font, new Point(0, 0))->lines());

        return $processor->leading($font) * ($lineCount - 1) + $processor->capHeight($font);
    }

    public function fetchImage(?string $url): ?ImageInterface
    {
        if (! $url) {
            return null;
        }

        try {
            $content = $this->resolveR2Content($url) ?? $this->fetchRemote($url);

            return $content ? $this->manager->read($content) : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    private function resolveR2Content(string $url): ?string
    {
        try {
            $baseUrl = rtrim(Storage::disk('r2')->url(''), '/');
        } catch (\RuntimeException) {
            return null;
        }

        if (! $baseUrl || ! str_starts_with($url, $baseUrl)) {
            return null;
        }

        $path = ltrim(substr($url, strlen($baseUrl)), '/');

        return Storage::disk('r2')->exists($path) ? Storage::disk('r2')->get($path) : null;
    }

    private function fetchRemote(string $url): ?string
    {
        $response = Http::timeout(5)->get($url);

        return $response->successful() ? $response->body() : null;
    }
}

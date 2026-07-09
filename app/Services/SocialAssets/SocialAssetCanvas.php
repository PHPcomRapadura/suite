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
 * divulgação (fundo, overlay, scrim, logo, texto medido, pílula de CTA,
 * avatar circular, cartão arredondado). Não conhece Event nem nenhum outro
 * model — recebe URLs/cores/textos prontos.
 */
class SocialAssetCanvas
{
    public const PADDING = 70;

    private const OVERLAY_COLOR = 'rgba(0, 0, 0, 0.25)';

    private const LOGO_SIZE = 140;

    // O canvas é rasterizado com GD, que não sabe carregar fontes web (woff/woff2).
    // A Lexend variável renderiza apenas a instância padrão no GD (não há bold real),
    // então usamos instâncias estáticas: cada peso é um TTF próprio.
    private const FONT_DIR = __DIR__.'/../../../resources/fonts';

    public const FONT_REGULAR = self::FONT_DIR.'/Lexend-Regular.ttf';

    public const FONT_MEDIUM = self::FONT_DIR.'/Lexend-Medium.ttf';

    public const FONT_SEMIBOLD = self::FONT_DIR.'/Lexend-SemiBold.ttf';

    public const FONT_BOLD = self::FONT_DIR.'/Lexend-Bold.ttf';

    public const FONT_BLACK = self::FONT_DIR.'/Lexend-Black.ttf';

    // Mantida por compatibilidade — aponta para o peso Regular estático.
    public const FONT_PATH = self::FONT_REGULAR;

    private ImageManager $manager;

    /**
     * Cache de bytes por URL dentro da mesma instância: os logos dos
     * patrocinadores são buscados a cada arte gerada, e hosts externos
     * intermitentes faziam o mesmo logo aparecer numas artes e cair no
     * fallback noutras. Cacheia inclusive a falha (null) para não martelar
     * um host fora do ar.
     *
     * @var array<string, ?string>
     */
    private array $imageCache = [];

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

    /**
     * Overlay base leve sobre a imagem inteira, para manter o logo do topo
     * legível sobre capas claras.
     */
    public function drawOverlay(ImageInterface $canvas, int $width, int $height): void
    {
        $canvas->drawRectangle(0, 0, function ($rectangle) use ($width, $height) {
            $rectangle->size($width, $height);
            $rectangle->background(self::OVERLAY_COLOR);
        });
    }

    /**
     * Scrim em gradiente vertical: transparente no topo -> escuro embaixo,
     * cobrindo a região onde o texto vive, garantindo contraste. No GD o
     * gradiente é aproximado por faixas horizontais com alpha crescente.
     */
    public function drawScrim(ImageInterface $canvas, int $width, int $height, float $startRatio = 0.42, float $maxAlpha = 0.88): void
    {
        $scrimTop = (int) round($height * $startRatio);
        $scrimHeight = $height - $scrimTop;

        if ($scrimHeight <= 0) {
            return;
        }

        $bands = 120;
        $bandHeight = (int) ceil($scrimHeight / $bands);

        for ($i = 0; $i < $bands; $i++) {
            $t = $i / ($bands - 1);
            $alpha = $maxAlpha * $t;
            $y = $scrimTop + ($i * $bandHeight);

            $canvas->drawRectangle(0, $y, function ($rectangle) use ($width, $bandHeight, $alpha) {
                $rectangle->size($width, $bandHeight + 1);
                $rectangle->background(sprintf('rgba(8, 12, 20, %.3f)', $alpha));
            });
        }
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
     * Barra de acento fina (estrutura de marca), tipicamente acima de um título-herói.
     */
    public function drawAccentBar(ImageInterface $canvas, int $x, int $y, string $color, int $barWidth = 120, int $barHeight = 8): void
    {
        $canvas->drawRectangle($x, $y, function ($rectangle) use ($barWidth, $barHeight, $color) {
            $rectangle->size($barWidth, $barHeight);
            $rectangle->background($color);
        });
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

        return $this->blockHeight($canvas, $text, $font);
    }

    /**
     * Mede a altura que um bloco de texto ocuparia (sem desenhar), útil para
     * layouts ancorados que precisam calcular a altura total antes de posicionar.
     */
    public function measureTextBlock(ImageInterface $canvas, string $text, Closure $fontCallback): int
    {
        $font = (new FontFactory($fontCallback))();
        $font->setValignment('top');

        return $this->blockHeight($canvas, $text, $font);
    }

    /**
     * Altura real do bloco considerando o wrap: boxSize() só mede uma linha, então
     * contamos as linhas do textBlock (que respeita ->wrap) e somamos o leading.
     */
    private function blockHeight(ImageInterface $canvas, string $text, object $font): int
    {
        $processor = $canvas->driver()->fontProcessor();
        $lineCount = count($processor->textBlock($text, $font, new Point(0, 0))->lines());

        return (int) ($processor->leading($font) * ($lineCount - 1) + $processor->capHeight($font));
    }

    /**
     * CTA em pílula centralizada horizontalmente. O texto é medido e centrado
     * de verdade (horizontal via align center, vertical via capHeight) sobre um
     * retângulo com pontas arredondadas (2 círculos nas extremidades).
     */
    public function drawButton(ImageInterface $canvas, string $label, int $centerX, int $y, string $backgroundColor, string $textColor = '#ffffff', int $fontSize = 34, int $height = 100): void
    {
        $font = (new FontFactory(function (FontFactory $f) use ($fontSize, $textColor) {
            $f->filename(self::FONT_BOLD);
            $f->size($fontSize);
            $f->color($textColor);
        }))();

        $processor = $canvas->driver()->fontProcessor();
        $textWidth = (int) $processor->boxSize($label, $font)->width();
        $capHeight = $processor->capHeight($font);

        $radius = (int) ($height / 2);
        $paddingX = 60;
        $buttonWidth = $textWidth + ($paddingX * 2) + $height;
        $buttonX = (int) ($centerX - ($buttonWidth / 2));

        // corpo central
        $canvas->drawRectangle($buttonX + $radius, $y, function ($rectangle) use ($buttonWidth, $radius, $height, $backgroundColor) {
            $rectangle->size($buttonWidth - (2 * $radius), $height);
            $rectangle->background($backgroundColor);
        });
        // pontas arredondadas
        $canvas->drawCircle($buttonX + $radius, $y + $radius, function ($circle) use ($radius, $backgroundColor) {
            $circle->radius($radius);
            $circle->background($backgroundColor);
        });
        $canvas->drawCircle($buttonX + $buttonWidth - $radius, $y + $radius, function ($circle) use ($radius, $backgroundColor) {
            $circle->radius($radius);
            $circle->background($backgroundColor);
        });

        // texto centrado
        $font->setAlignment('center');
        $font->setValignment('top');
        $textY = (int) ($y + (($height - $capHeight) / 2));
        $canvas->text($label, $centerX, $textY, $font);
    }

    /**
     * Pílula pequena de rótulo (badge). Retorna a largura ocupada.
     */
    public function drawPillLabel(ImageInterface $canvas, string $label, int $centerX, int $y, string $backgroundColor, string $textColor = '#ffffff', int $fontSize = 26, int $height = 58): int
    {
        $font = (new FontFactory(function (FontFactory $f) use ($fontSize, $textColor) {
            $f->filename(self::FONT_BOLD);
            $f->size($fontSize);
            $f->color($textColor);
        }))();

        $processor = $canvas->driver()->fontProcessor();
        $textWidth = (int) $processor->boxSize($label, $font)->width();
        $capHeight = $processor->capHeight($font);

        $radius = (int) ($height / 2);
        $paddingX = 34;
        $pillWidth = $textWidth + ($paddingX * 2) + $height;
        $pillX = (int) ($centerX - ($pillWidth / 2));

        $canvas->drawRectangle($pillX + $radius, $y, function ($rectangle) use ($pillWidth, $radius, $height, $backgroundColor) {
            $rectangle->size($pillWidth - (2 * $radius), $height);
            $rectangle->background($backgroundColor);
        });
        $canvas->drawCircle($pillX + $radius, $y + $radius, function ($circle) use ($radius, $backgroundColor) {
            $circle->radius($radius);
            $circle->background($backgroundColor);
        });
        $canvas->drawCircle($pillX + $pillWidth - $radius, $y + $radius, function ($circle) use ($radius, $backgroundColor) {
            $circle->radius($radius);
            $circle->background($backgroundColor);
        });

        $font->setAlignment('center');
        $font->setValignment('top');
        $textY = (int) ($y + (($height - $capHeight) / 2));
        $canvas->text($label, $centerX, $textY, $font);

        return $pillWidth;
    }

    /**
     * Cartão retangular com cantos arredondados. Composto por 3 retângulos
     * (corpo + duas colunas laterais internas) e 4 círculos nos cantos, já que
     * o GD não tem primitivo de retângulo arredondado.
     */
    public function drawRoundedCard(ImageInterface $canvas, int $x, int $y, int $cardWidth, int $cardHeight, string $color, int $radius = 32): void
    {
        // sombra sutil deslocada
        $shadowOffset = 12;
        $canvas->drawRectangle($x + $radius, $y + $shadowOffset, function ($rectangle) use ($cardWidth, $radius, $cardHeight) {
            $rectangle->size($cardWidth - (2 * $radius), $cardHeight);
            $rectangle->background('rgba(0, 0, 0, 0.20)');
        });

        // corpo central (altura total, largura menos os cantos)
        $canvas->drawRectangle($x + $radius, $y, function ($rectangle) use ($cardWidth, $radius, $cardHeight, $color) {
            $rectangle->size($cardWidth - (2 * $radius), $cardHeight);
            $rectangle->background($color);
        });
        // colunas laterais (largura do raio, altura menos os cantos)
        $canvas->drawRectangle($x, $y + $radius, function ($rectangle) use ($radius, $cardHeight, $color) {
            $rectangle->size($radius, $cardHeight - (2 * $radius));
            $rectangle->background($color);
        });
        $canvas->drawRectangle($x + $cardWidth - $radius, $y + $radius, function ($rectangle) use ($radius, $cardHeight, $color) {
            $rectangle->size($radius, $cardHeight - (2 * $radius));
            $rectangle->background($color);
        });
        // cantos
        foreach ([
            [$x + $radius, $y + $radius],
            [$x + $cardWidth - $radius, $y + $radius],
            [$x + $radius, $y + $cardHeight - $radius],
            [$x + $cardWidth - $radius, $y + $cardHeight - $radius],
        ] as [$cx, $cy]) {
            $canvas->drawCircle($cx, $cy, function ($circle) use ($radius, $color) {
                $circle->radius($radius);
                $circle->background($color);
            });
        }
    }

    /**
     * Avatar circular com anel de acento. Recorta a imagem quadrada num círculo
     * via máscara per-pixel no GD nativo (a lib não tem recorte circular), e
     * desenha um círculo de acento maior por trás como anel.
     */
    public function drawCircularAvatar(ImageInterface $canvas, ImageInterface $avatar, int $centerX, int $top, int $size, string $ringColor, int $ringWidth = 8): void
    {
        $avatar->cover($size, $size);
        $circular = $this->circularMask($avatar, $size);

        $ringRadius = (int) ($size / 2) + $ringWidth;
        $centerY = $top + (int) ($size / 2);

        $canvas->drawCircle($centerX, $centerY, function ($circle) use ($ringRadius, $ringColor) {
            $circle->radius($ringRadius);
            $circle->background($ringColor);
        });

        $canvas->place($circular, 'top-left', $centerX - (int) ($size / 2), $top);
    }

    private function circularMask(ImageInterface $avatar, int $size): ImageInterface
    {
        $source = $avatar->core()->native();

        $masked = imagecreatetruecolor($size, $size);
        imagesavealpha($masked, true);
        imagealphablending($masked, false);
        $transparent = imagecolorallocatealpha($masked, 0, 0, 0, 127);
        imagefill($masked, 0, 0, $transparent);

        $center = $size / 2;
        $radiusSq = ($size / 2) * ($size / 2);

        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                $dx = $x - $center + 0.5;
                $dy = $y - $center + 0.5;
                if ((($dx * $dx) + ($dy * $dy)) <= $radiusSq) {
                    imagesetpixel($masked, $x, $y, imagecolorat($source, $x, $y));
                }
            }
        }

        return $this->manager->read($masked);
    }

    /**
     * Rodapé com os logos dos patrocinadores, em chips brancos arredondados
     * numa fileira centralizada na base da arte. Cada logo é escalado para uma
     * altura uniforme via contain(). Se houver muitos, reduz a escala para caber.
     *
     * @param  iterable<int, object{name: string, logo_url: ?string}>  $sponsors
     */
    public function drawSponsorFooter(ImageInterface $canvas, iterable $sponsors, int $width, int $height, string $format): void
    {
        $sponsors = collect($sponsors)->values();

        if ($sponsors->isEmpty()) {
            return;
        }

        $isStory = $format === 'story';
        // Limita a uma fileira enxuta; acima disso a escala cai para caber.
        $sponsors = $sponsors->take(6);
        $count = $sponsors->count();

        $bottomMargin = $isStory ? 60 : 50;
        $chipPadding = 18;
        $chipRadius = 16;
        $gap = 20;

        // altura do logo diminui conforme a quantidade, para caber em uma linha
        $logoHeight = match (true) {
            $count <= 3 => 76,
            $count <= 4 => 66,
            default => 56,
        };
        $chipHeight = $logoHeight + ($chipPadding * 2);

        $available = $width - (self::PADDING * 2) - ($gap * ($count - 1));
        $maxChipWidth = (int) ($available / $count);

        // pré-carrega e mede cada chip
        $chips = [];
        $totalWidth = 0;
        foreach ($sponsors as $sponsor) {
            $logo = $this->fetchImage($sponsor->logo_url ?? null);
            $chipWidth = $logoHeight + ($chipPadding * 2); // mínimo quadrado

            if ($logo) {
                $logo->contain($maxChipWidth - ($chipPadding * 2), $logoHeight, '#ffffff', 'center');
                $chipWidth = min($maxChipWidth, $logo->width() + ($chipPadding * 2));
            }

            $chips[] = ['logo' => $logo, 'name' => $sponsor->name ?? '', 'width' => (int) $chipWidth];
            $totalWidth += (int) $chipWidth;
        }
        $totalWidth += $gap * ($count - 1);

        $rowY = $height - $bottomMargin - $chipHeight;

        // kicker acima da fileira
        $kickerFont = (new FontFactory(function (FontFactory $f) {
            $f->filename(self::FONT_SEMIBOLD);
            $f->size(24);
            $f->color('rgba(255, 255, 255, 0.85)');
            $f->align('center');
        }))();
        $kickerFont->setValignment('top');
        $kickerHeight = (int) $canvas->driver()->fontProcessor()->capHeight($kickerFont);
        $canvas->text('PATROCÍNIO', (int) ($width / 2), $rowY - 20 - $kickerHeight, $kickerFont);

        $x = (int) (($width - $totalWidth) / 2);

        foreach ($chips as $chip) {
            $this->drawRoundedCard($canvas, $x, $rowY, $chip['width'], $chipHeight, '#ffffff', $chipRadius);

            if ($chip['logo']) {
                $logoX = $x + (int) (($chip['width'] - $chip['logo']->width()) / 2);
                $logoY = $rowY + (int) (($chipHeight - $chip['logo']->height()) / 2);
                $canvas->place($chip['logo'], 'top-left', $logoX, $logoY);
            } elseif ($chip['name'] !== '') {
                $canvas->text($chip['name'], $x + (int) ($chip['width'] / 2), $rowY + (int) ($chipHeight / 2), function (FontFactory $font) use ($chip, $chipPadding) {
                    $font->filename(self::FONT_SEMIBOLD);
                    $font->size(22);
                    $font->color('#111827');
                    $font->align('center');
                    $font->valign('middle');
                    $font->wrap($chip['width'] - ($chipPadding * 2));
                });
            }

            $x += $chip['width'] + $gap;
        }
    }

    public function fetchImage(?string $url): ?ImageInterface
    {
        if (! $url) {
            return null;
        }

        if (! array_key_exists($url, $this->imageCache)) {
            try {
                $this->imageCache[$url] = $this->resolveR2Content($url) ?? $this->fetchRemote($url);
            } catch (Throwable) {
                $this->imageCache[$url] = null;
            }
        }

        $content = $this->imageCache[$url];

        try {
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
        // Hosts externos (logos de patrocinadores) falham de forma
        // intermitente — sem retry, o mesmo logo aparecia numas artes e
        // caía no fallback de texto noutras.
        $response = Http::timeout(5)->retry(3, 250, throw: false)->get($url);

        return $response->successful() ? $response->body() : null;
    }
}

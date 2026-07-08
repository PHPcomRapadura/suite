<?php

namespace App\Services\SocialAssets\Templates;

use App\Services\SocialAssets\SocialAssetCanvas;
use Intervention\Image\Interfaces\ImageInterface;

interface SocialAssetTemplate
{
    /**
     * Desenha o conteúdo específico do template sobre um canvas que já tem
     * fundo, overlay e logo do evento aplicados.
     *
     * @param  array<string, mixed>  $context
     */
    public function compose(ImageInterface $canvas, SocialAssetCanvas $tools, array $context): void;
}

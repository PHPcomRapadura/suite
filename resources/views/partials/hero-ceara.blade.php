{{-- Cinco referências em xilogravura, alinhadas pela base. --}}
<div class="hero-ceara" aria-hidden="true">
    @foreach ([
        'galinha-choca' => '0 50 990 550',
        'iracema' => '1070 40 466 590',
        'belchior' => '0 565 385 440',
        'ponte-metalica' => '385 720 650 304',
        'castelao' => '1030 700 506 280',
    ] as $reference => $crop)
        @php([$cropX, $cropY, $cropWidth, $cropHeight] = explode(' ', $crop))
        <svg class="hero-ceara__motif hero-ceara__motif--{{ $reference }}" viewBox="{{ $crop }}" preserveAspectRatio="xMidYMax meet" fill="none" focusable="false">
            <defs>
                <clipPath id="hero-ceara-clip-{{ $reference }}">
                    <rect x="{{ $cropX }}" y="{{ $cropY }}" width="{{ $cropWidth }}" height="{{ $cropHeight }}" />
                </clipPath>
            </defs>
            <image href="{{ asset(in_array($reference, ['galinha-choca', 'iracema']) ? 'images/hero-ceara-cordel-v2.png' : 'images/hero-ceara-cordel.png') }}" width="1536" height="1024" clip-path="url(#hero-ceara-clip-{{ $reference }})" />
        </svg>
    @endforeach
</div>

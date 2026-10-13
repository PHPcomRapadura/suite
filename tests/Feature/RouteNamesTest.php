<?php

use Illuminate\Support\Facades\Route;

// `php artisan route:cache` falha com "Another route has already been assigned
// name [...]" quando duas rotas têm o mesmo nome — e `optimize` esconde o erro.
// As rotas de página da SPA não podem reutilizar os nomes das rotas de API.
it('não tem nomes de rota duplicados', function () {
    $names = collect(Route::getRoutes()->getRoutes())
        ->map(fn ($route) => $route->getName())
        // Rotas sem nome próprio dentro de um grupo ficam só com o prefixo
        // ("admin."); o route:cache trata esses nomes como ausentes.
        ->filter(fn (?string $name) => $name !== null && ! str_ends_with($name, '.'));

    expect($names->duplicates()->values()->all())->toBe([]);
});

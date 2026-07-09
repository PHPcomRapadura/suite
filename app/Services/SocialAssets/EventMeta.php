<?php

namespace App\Services\SocialAssets;

use App\Models\Event;

/**
 * Helpers de formatação de metadados de evento para as artes de divulgação.
 * Mantém os textos curtos: nada de endereço completo nas artes.
 */
class EventMeta
{
    public static function date(Event $event): string
    {
        return $event->starts_at
            ? $event->starts_at->translatedFormat('d \d\e F \d\e Y')
            : 'Data em breve';
    }

    public static function dateTime(Event $event): string
    {
        return $event->starts_at
            ? $event->starts_at->translatedFormat('d \d\e F \d\e Y \à\s H\hi')
            : 'Data em breve';
    }

    /**
     * Local resumido: primeiro segmento do location antes do primeiro " - "
     * (nome do lugar), acrescido de cidade/UF se detectável no fim da string.
     * Ex.: "Uni 7 - Av. Washington Soares - Fortaleza - CE" => "Uni 7 · Fortaleza - CE".
     * Fallback seguro: sem " - ", devolve a string inteira.
     */
    public static function location(Event $event): ?string
    {
        $location = trim((string) $event->location);

        if ($location === '') {
            return null;
        }

        $segments = array_map('trim', explode(' - ', $location));

        if (count($segments) === 1) {
            return $segments[0];
        }

        $venue = $segments[0];

        // Tenta reconhecer "Cidade - UF" no fim (UF = 2 letras).
        $last = end($segments);
        $beforeLast = count($segments) >= 2 ? $segments[count($segments) - 2] : null;

        if ($beforeLast !== null && preg_match('/^[A-Za-z]{2}$/', $last)) {
            $cityUf = "{$beforeLast} - ".mb_strtoupper($last);

            return $venue === $beforeLast ? $cityUf : "{$venue} · {$cityUf}";
        }

        return "{$venue} · {$last}";
    }

    /**
     * Meta em duas linhas: [data, local resumido]. Local pode ser null.
     *
     * @return array{0: string, 1: ?string}
     */
    public static function dateAndLocation(Event $event): array
    {
        return [self::date($event), self::location($event)];
    }
}

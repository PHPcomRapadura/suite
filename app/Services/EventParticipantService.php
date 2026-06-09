<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventParticipant;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class EventParticipantService
{
    private const REQUIRED_COLUMNS = ['Ordem de inscrição', 'Nome', 'Email'];

    public function import(Event $event, UploadedFile $file): array
    {
        $content = file_get_contents($file->getRealPath());

        $encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        if ($encoding && $encoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
        }

        $lines = explode("\n", trim($content));
        $rows = array_map(fn ($line) => str_getcsv($line, ';'), $lines);
        $headers = array_shift($rows);

        if ($headers === null) {
            throw ValidationException::withMessages(['csv' => 'O arquivo CSV está vazio.']);
        }

        $headers = array_map('trim', $headers);
        $map = array_flip($headers);

        foreach (self::REQUIRED_COLUMNS as $col) {
            if (! isset($map[$col])) {
                throw ValidationException::withMessages([
                    'csv' => "Coluna obrigatória ausente: \"{$col}\".",
                ]);
            }
        }

        $imported = 0;
        $updated = 0;
        $errors = [];

        foreach ($rows as $lineIndex => $row) {
            if (empty(array_filter($row))) {
                continue;
            }

            $lineNumber = $lineIndex + 2;

            try {
                $registrationOrder = (int) ($row[$map['Ordem de inscrição']] ?? 0);
                $email = strtolower(trim($row[$map['Email']] ?? ''));

                if ($registrationOrder === 0) {
                    $errors[] = "Linha {$lineNumber}: Ordem de inscrição inválida.";
                    continue;
                }

                if ($email === '') {
                    $errors[] = "Linha {$lineNumber}: campo Email ausente.";
                    continue;
                }

                $exists = EventParticipant::where('event_id', $event->id)
                    ->where('registration_order', $registrationOrder)
                    ->exists();

                EventParticipant::updateOrCreate(
                    ['event_id' => $event->id, 'registration_order' => $registrationOrder],
                    [
                        'first_name' => mb_strtoupper(trim($row[$map['Nome']] ?? ''), 'UTF-8'),
                        'last_name' => mb_strtoupper(trim($row[$map['Sobrenome']] ?? ''), 'UTF-8'),
                        'email' => $email,
                        'ticket_type' => trim($row[$map['Tipo de ingresso']] ?? ''),
                        'amount' => self::parseAmount($row[$map['Valor']] ?? '0'),
                        'purchased_at' => \Carbon\Carbon::parse($row[$map['Data compra']] ?? now()),
                        'payment_status' => trim($row[$map['Estado de pagamento']] ?? ''),
                        'checked_in' => strtolower(trim($row[$map['Check-in']] ?? '')) === 'sim',
                        'discount_coupon' => ($row[$map['Cupom de Desconto']] ?? '') ?: null,
                        'payment_method' => ($row[$map['Método de pagamento']] ?? '') ?: null,
                    ]
                );

                $exists ? $updated++ : $imported++;
            } catch (\Throwable $e) {
                $errors[] = "Linha {$lineNumber}: erro ao processar registro.";
            }
        }

        return [
            'imported' => $imported,
            'updated' => $updated,
            'errors' => $errors,
        ];
    }

    public function list(Event $event, array $filters): LengthAwarePaginator
    {
        return EventParticipant::where('event_id', $event->id)
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($filters['ticket_type'] ?? null, fn ($q, $v) => $q->where('ticket_type', $v))
            ->when($filters['payment_status'] ?? null, fn ($q, $v) => $q->where('payment_status', $v))
            ->when(
                isset($filters['checked_in']) && $filters['checked_in'] !== '',
                fn ($q) => $q->where('checked_in', filter_var($filters['checked_in'], FILTER_VALIDATE_BOOLEAN))
            )
            ->orderBy('registration_order')
            ->paginate(50);
    }

    public function summary(Event $event): array
    {
        $participants = EventParticipant::where('event_id', $event->id)->get(['payment_status', 'checked_in', 'ticket_type']);

        return [
            'total' => $participants->count(),
            'approved' => $participants->where('payment_status', 'Aprovado')->count(),
            'checked_in' => $participants->where('checked_in', true)->count(),
            'ticket_types' => $participants->pluck('ticket_type')->unique()->sort()->values()->all(),
        ];
    }

    public function delete(EventParticipant $participant): void
    {
        $participant->delete();
    }

    public function clear(Event $event): int
    {
        return EventParticipant::where('event_id', $event->id)->delete();
    }

    public function formatParticipant(EventParticipant $participant): array
    {
        return [
            'id' => $participant->id,
            'registration_order' => $participant->registration_order,
            'first_name' => $participant->first_name,
            'last_name' => $participant->last_name,
            'full_name' => $participant->full_name,
            'email' => $participant->email,
            'ticket_type' => $participant->ticket_type,
            'amount' => $participant->amount,
            'purchased_at' => $participant->purchased_at?->toIso8601String(),
            'payment_status' => $participant->payment_status,
            'checked_in' => $participant->checked_in,
            'discount_coupon' => $participant->discount_coupon,
            'payment_method' => $participant->payment_method,
        ];
    }

    private static function parseAmount(string $raw): float
    {
        $clean = str_replace(['R$', ' ', '.'], '', $raw);
        $clean = str_replace(',', '.', $clean);

        return (float) $clean;
    }
}

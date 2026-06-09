<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventExpense;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

class EventExpenseService
{
    private const CATEGORY_LABELS = [
        'alimentacao' => 'Alimentação',
        'transporte' => 'Transporte',
        'hospedagem' => 'Hospedagem',
        'equipamentos' => 'Equipamentos',
        'marketing' => 'Marketing e Divulgação',
        'infraestrutura' => 'Infraestrutura',
        'palestrantes' => 'Ajuda de Custo — Palestrantes',
        'premiacao' => 'Premiação e Brindes',
        'outros' => 'Outros',
    ];

    public function list(Event $event, array $filters): LengthAwarePaginator
    {
        return EventExpense::where('event_id', $event->id)
            ->when($filters['category'] ?? null, fn ($q, $c) => $q->where('category', $c))
            ->when(
                isset($filters['is_paid']) && $filters['is_paid'] !== '',
                fn ($q) => $q->where('is_paid', filter_var($filters['is_paid'], FILTER_VALIDATE_BOOLEAN))
            )
            ->when($filters['date_from'] ?? null, fn ($q, $d) => $q->where('date', '>=', $d))
            ->when($filters['date_to'] ?? null, fn ($q, $d) => $q->where('date', '<=', $d))
            ->orderBy('date', 'desc')
            ->with(['creator:id,name', 'updater:id,name'])
            ->paginate(12);
    }

    public function summary(Event $event, array $filters = []): array
    {
        $query = EventExpense::where('event_id', $event->id)
            ->when($filters['category'] ?? null, fn ($q, $c) => $q->where('category', $c))
            ->when(
                isset($filters['is_paid']) && $filters['is_paid'] !== '',
                fn ($q) => $q->where('is_paid', filter_var($filters['is_paid'], FILTER_VALIDATE_BOOLEAN))
            )
            ->when($filters['date_from'] ?? null, fn ($q, $d) => $q->where('date', '>=', $d))
            ->when($filters['date_to'] ?? null, fn ($q, $d) => $q->where('date', '<=', $d));

        $all = (clone $query)->get(['category', 'amount', 'is_paid']);

        $total = $all->sum('amount');
        $paid = $all->where('is_paid', true)->sum('amount');
        $pending = $all->where('is_paid', false)->sum('amount');

        $byCategory = [];
        foreach (array_keys(self::CATEGORY_LABELS) as $cat) {
            $sum = $all->where('category', $cat)->sum('amount');
            if ($sum > 0) {
                $byCategory[$cat] = round($sum, 2);
            }
        }

        return [
            'total' => round($total, 2),
            'paid' => round($paid, 2),
            'pending' => round($pending, 2),
            'by_category' => $byCategory,
        ];
    }

    public function create(Event $event, array $data, ?UploadedFile $receipt, int $createdBy): EventExpense
    {
        /** @var EventExpense $expense */
        $expense = $event->expenses()->create([
            ...$data,
            'created_by' => $createdBy,
        ]);

        if ($receipt) {
            $expense->update(['receipt_url' => $this->uploadReceipt($expense, $receipt)]);
        }

        return $expense->load(['creator:id,name', 'updater:id,name']);
    }

    public function update(EventExpense $expense, array $data, ?UploadedFile $receipt, int $updatedBy): EventExpense
    {
        $expense->update([...$data, 'updated_by' => $updatedBy]);

        if ($receipt) {
            $this->deleteReceipt($expense);
            $expense->update(['receipt_url' => $this->uploadReceipt($expense, $receipt)]);
        }

        return $expense->load(['creator:id,name', 'updater:id,name']);
    }

    public function delete(EventExpense $expense): void
    {
        $this->deleteReceipt($expense);
        $expense->delete();
    }

    public function uploadReceipt(EventExpense $expense, UploadedFile $file): string
    {
        $path = "events/{$expense->event_id}/expenses/{$expense->id}/receipt.{$file->extension()}";
        Storage::disk('r2')->putFileAs('', $file, $path, 'public');

        return Storage::disk('r2')->url($path);
    }

    public function deleteReceipt(EventExpense $expense): void
    {
        if (! $expense->receipt_url) {
            return;
        }

        try {
            $baseUrl = rtrim(Storage::disk('r2')->url(''), '/');
        } catch (\RuntimeException) {
            return;
        }

        if ($baseUrl && str_starts_with($expense->receipt_url, $baseUrl)) {
            $path = ltrim(substr($expense->receipt_url, strlen($baseUrl)), '/');
            Storage::disk('r2')->delete($path);
        }
    }

    public function formatExpense(EventExpense $expense): array
    {
        $data = $expense->toArray();
        $data['category_label'] = self::CATEGORY_LABELS[$expense->category] ?? $expense->category;

        return $data;
    }

    public static function categoryLabels(): array
    {
        return self::CATEGORY_LABELS;
    }
}

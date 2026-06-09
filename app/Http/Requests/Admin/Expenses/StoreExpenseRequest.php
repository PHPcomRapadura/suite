<?php

namespace App\Http\Requests\Admin\Expenses;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category' => ['required', Rule::in([
                'alimentacao', 'transporte', 'hospedagem', 'equipamentos',
                'marketing', 'infraestrutura', 'palestrantes', 'premiacao', 'outros',
            ])],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'is_paid' => ['boolean'],
            'receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'category.required' => 'Selecione uma categoria.',
            'category.in' => 'Categoria inválida.',
            'description.required' => 'Informe uma descrição.',
            'description.max' => 'A descrição deve ter no máximo 255 caracteres.',
            'amount.required' => 'Informe o valor da despesa.',
            'amount.min' => 'O valor deve ser maior que zero.',
            'date.required' => 'Informe a data da despesa.',
            'date.before_or_equal' => 'A data não pode ser futura.',
            'receipt.mimes' => 'O comprovante deve ser JPG, PNG, WebP ou PDF.',
            'receipt.max' => 'O comprovante deve ter no máximo 5 MB.',
            'notes.max' => 'As observações devem ter no máximo 1.000 caracteres.',
        ];
    }
}

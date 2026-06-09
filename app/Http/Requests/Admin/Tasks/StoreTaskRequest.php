<?php

namespace App\Http\Requests\Admin\Tasks;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['nullable', Rule::in(['a_fazer', 'em_andamento', 'em_revisao', 'impedimento', 'concluida'])],
            'priority' => ['nullable', Rule::in(['baixa', 'media', 'alta'])],
            'assigned_to' => ['nullable', 'integer', Rule::exists('users', 'id')->whereIn('role', ['admin', 'colaborador'])],
            'due_date' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Informe um título para a tarefa.',
            'title.max' => 'O título deve ter no máximo 255 caracteres.',
            'description.max' => 'A descrição deve ter no máximo 5.000 caracteres.',
            'status.in' => 'Status inválido.',
            'priority.in' => 'Prioridade inválida.',
            'assigned_to.exists' => 'Responsável inválido.',
            'due_date.date' => 'Data de prazo inválida.',
        ];
    }
}

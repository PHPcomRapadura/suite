<?php

namespace App\Http\Requests\Admin\EventSite;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEventSiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'layout' => ['required', 'integer', 'in:1,2,3'],
            'primary_color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'font' => ['required', 'string', 'in:lexend,inter,poppins,space_grotesk'],
            'hero_tagline' => ['nullable', 'string', 'max:255'],
            'ticket_url' => ['nullable', 'url', 'max:500'],
            'code_of_conduct' => ['nullable', 'string'],
            'faq' => ['nullable', 'array'],
            'faq.*.question' => ['required', 'string', 'max:500'],
            'faq.*.answer' => ['required', 'string'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'layout.in' => 'Escolha um dos layouts disponíveis (1, 2 ou 3).',
            'primary_color.regex' => 'A cor primária deve ser um hexadecimal válido (ex: #025c98).',
            'secondary_color.regex' => 'A cor secundária deve ser um hexadecimal válido.',
            'font.in' => 'Fonte inválida.',
            'ticket_url.url' => 'Informe uma URL válida para o link de ingressos.',
            'faq.*.question.required' => 'Cada item do FAQ precisa ter uma pergunta.',
            'faq.*.answer.required' => 'Cada item do FAQ precisa ter uma resposta.',
        ];
    }
}

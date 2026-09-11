<?php

namespace App\Http\Requests\Receivable;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReceivableRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'project_id' => ['nullable', 'exists:projects,id'],
            'tanggal' => ['required', 'date'],
            'nomor_invoice' => ['nullable', 'string', 'max:100'],
            'jatuh_tempo' => ['nullable', 'date', 'after_or_equal:tanggal'],
            'nominal' => ['required', 'numeric', 'min:0'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

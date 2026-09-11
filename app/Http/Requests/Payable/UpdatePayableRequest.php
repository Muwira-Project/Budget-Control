<?php

namespace App\Http\Requests\Payable;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePayableRequest extends FormRequest
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
            'akun_id' => ['required', 'exists:akuns,id'],
            'pihak_type_id' => ['required', 'exists:master_types,id'],
            'pihak_item_id' => ['required', 'exists:master_items,id'],
            'tanggal' => ['required', 'date'],
            'nomor_invoice' => ['nullable', 'string', 'max:100'],
            'jatuh_tempo' => ['nullable', 'date', 'after_or_equal:tanggal'],
            'nominal' => ['required', 'numeric', 'min:0'],
            'jenis_pajak' => ['nullable', Rule::in(['ppn', 'pph'])],
            'pajak_include' => ['nullable', 'boolean'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

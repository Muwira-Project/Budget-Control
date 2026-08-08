<?php

namespace App\Http\Requests\Realisasi;

use Illuminate\Foundation\Http\FormRequest;

class StoreRealisasiRequest extends FormRequest
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
            'project_id' => ['required', 'exists:projects,id'],
            'akun_id' => ['required', 'exists:akuns,id'],
            'vendor_id' => ['nullable', 'exists:vendors,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'mandor_id' => ['nullable', 'exists:mandors,id'],
            'investor_id' => ['nullable', 'exists:investors,id'],
            'kategori_id' => ['nullable', 'exists:kategoris,id'],
            'tanggal' => ['required', 'date'],
            'nominal' => ['required', 'numeric', 'min:0'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

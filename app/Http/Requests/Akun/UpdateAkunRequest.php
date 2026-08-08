<?php

namespace App\Http\Requests\Akun;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAkunRequest extends FormRequest
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
     * @param  int|null  $ignoreId  Akun id to ignore on the unique kode_akun check.
     * @return array<string, array<int, mixed>>
     */
    public function rules(?int $ignoreId = null): array
    {
        return [
            'kode_akun' => ['required', 'string', 'max:50', Rule::unique('akuns', 'kode_akun')->ignore($ignoreId)],
            'nama_akun' => ['required', 'string', 'max:255'],
            'jenis_akun' => ['required', Rule::in(['pendapatan', 'pengeluaran'])],
            'kategori_id' => ['nullable', 'exists:kategoris,id'],
        ];
    }
}

<?php

namespace App\Http\Requests\Cashflow;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCashflowRequest extends FormRequest
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
            'tanggal' => ['required', 'date'],
            'jenis' => ['required', Rule::in(['masuk', 'keluar'])],
            'sumber' => ['required', Rule::in(['pendapatan', 'pelunasan_ar', 'pelunasan_ap', 'pengeluaran_lain'])],
            'nominal' => ['required', 'numeric', 'min:0'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
            'cash_account_id' => ['nullable', 'integer', 'exists:cash_accounts,id'],
            'akun_id' => ['nullable', 'integer', 'exists:akuns,id'],
            'status' => ['required', Rule::in(['draft'])],
        ];
    }
}

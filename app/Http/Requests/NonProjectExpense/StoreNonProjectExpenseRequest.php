<?php

namespace App\Http\Requests\NonProjectExpense;

use Illuminate\Foundation\Http\FormRequest;

class StoreNonProjectExpenseRequest extends FormRequest
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
            'akun_id' => ['required', 'exists:akuns,id'],
            'vendor_id' => ['nullable', 'exists:vendors,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'mandor_id' => ['nullable', 'exists:mandors,id'],
            'investor_id' => ['nullable', 'exists:investors,id'],
            'nominal' => ['required', 'numeric', 'min:0'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

<?php

namespace App\Http\Requests\ProjectAkun;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectAkunRequest extends FormRequest
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
            'type' => ['required', 'in:ap,ar,other_income,other_outcome'],
            'pihak_type_id' => ['nullable', 'exists:master_types,id'],
            'pihak_item_id' => ['nullable', 'exists:master_items,id'],
            'payable_id' => ['nullable', 'exists:payables,id'],
            'receivable_id' => ['nullable', 'exists:receivables,id'],
            'custom_name' => ['nullable', 'string', 'max:255'],
            'outstanding_balance' => ['nullable', 'numeric', 'min:0'],
            'budget' => ['required', 'numeric', 'min:0'],
            'allocation' => ['required', 'numeric', 'min:0', 'lte:budget'],
        ];
    }
}

<?php

namespace App\Http\Requests\BudgetPlan;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBudgetPlanRequest extends FormRequest
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
            'periode' => ['required', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'estimasi_pendapatan' => ['required', 'numeric', 'min:0'],
            'target_laba' => ['nullable', 'numeric'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.akun_id' => ['required', 'distinct', 'exists:akuns,id'],
            'items.*.nominal' => ['required', 'numeric', 'min:0'],
            'items.*.tanggal_mulai' => ['nullable', 'date'],
            'items.*.tanggal_selesai' => ['nullable', 'date', 'after_or_equal:items.*.tanggal_mulai'],
        ];
    }
}

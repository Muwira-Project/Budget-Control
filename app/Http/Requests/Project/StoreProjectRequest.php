<?php

namespace App\Http\Requests\Project;

use App\Enums\ProjectStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends FormRequest
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
        $rules = [
            'kode' => ['required', 'string', 'max:50', Rule::unique('projects', 'kode')],
            'po_number' => ['nullable', 'string', 'max:100', Rule::unique('projects', 'po_number'), 'required_if:status,done'],
            'nama' => ['required', 'string', 'max:255'],
            'lokasi' => ['nullable', 'string', 'max:255'],
            'pic' => ['nullable', 'string', 'max:255'],
            'project_category_id' => ['nullable', 'integer', 'exists:master_items,id'],
            'sub_work' => ['nullable', 'string', 'max:500'],
            'periode' => ['nullable', 'string', 'max:100'],
            'jenis' => ['required', Rule::in(['barang', 'jasa'])],
            'qty' => ['nullable', 'numeric', 'min:0'],
            'satuan' => ['nullable', 'string', 'max:50'],
            'harga_satuan' => ['nullable', 'numeric', 'min:0'],
            'pajak' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'tanggal_mulai' => ['nullable', 'date'],
            'target_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
            'status' => ['required', Rule::in(array_column(ProjectStatus::cases(), 'value'))],
        ];

        return $rules;
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // For new projects, current status is implicitly Draft
            $newStatus = ProjectStatus::tryFrom($this->input('status'));

            if ($newStatus && ! ProjectStatus::Draft->canTransitionTo($newStatus)) {
                $validator->errors()->add(
                    'status',
                    "Tidak bisa membuat project dengan status {$newStatus->label()}. Project baru harus berstatus Draft, In Progress, atau Cancelled."
                );
            }
        });
    }
}

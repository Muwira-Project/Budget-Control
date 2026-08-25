<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\ProjectStatus;

class UpdateProjectRequest extends FormRequest
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
     * @param  int|null  $ignoreId  Project id to ignore on the unique kode check.
     * @return array<string, array<int, mixed>>
     */
    public function rules(?int $ignoreId = null): array
    {
        $rules = [
            'kode' => ['required', 'string', 'max:50', Rule::unique('projects', 'kode')->ignore($ignoreId)],
            'po_number' => ['nullable', 'string', 'max:100', Rule::unique('projects', 'po_number')->ignore($ignoreId), 'required_if:status,done'],
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
            $projectId = $this->route('project')?->id ?? $this->route('id');
            
            if ($projectId) {
                $project = \App\Models\Project::find($projectId);
                
                if ($project && $this->input('status') !== $project->status->value) {
                    $oldStatus = $project->status;
                    $newStatus = ProjectStatus::tryFrom($this->input('status'));
                    
                    if ($newStatus && !$oldStatus->canTransitionTo($newStatus)) {
                        $validator->errors()->add(
                            'status', 
                            "Tidak bisa mengubah status dari {$oldStatus->label()} ke {$newStatus->label()}."
                        );
                    }
                }
            }
        });
    }
}

<?php

namespace App\Http\Requests\ProjectAkun;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectAkunRequest extends FormRequest
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
            'budget' => ['required', 'numeric', 'min:0'],
            'allocation' => ['required', 'numeric', 'min:0', 'lte:budget'],
        ];
    }
}

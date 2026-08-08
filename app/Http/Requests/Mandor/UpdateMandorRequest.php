<?php

namespace App\Http\Requests\Mandor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMandorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(?int $ignoreId = null): array
    {
        return [
            'kode' => ['required', 'string', 'max:50', Rule::unique('mandors', 'kode')->ignore($ignoreId)],
            'nama' => ['required', 'string', 'max:255'],
            'telepon' => ['nullable', 'string', 'max:50'],
            'alamat' => ['nullable', 'string', 'max:255'],
        ];
    }
}

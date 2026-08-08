<?php

namespace App\Http\Requests\Kategori;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateKategoriRequest extends FormRequest
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
     * @param  int|null  $ignoreId  Kategori id to ignore on the unique kode check.
     * @return array<string, array<int, mixed>>
     */
    public function rules(?int $ignoreId = null): array
    {
        return [
            'kode' => ['required', 'string', 'max:50', Rule::unique('kategoris', 'kode')->ignore($ignoreId)],
            'nama' => ['required', 'string', 'max:255'],
        ];
    }
}

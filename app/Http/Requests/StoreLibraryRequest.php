<?php

namespace App\Http\Requests;

use App\Traits\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Str;

class StoreLibraryRequest extends FormRequest
{
    use ApiResponse;

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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $libraryId = $this->route('library')?->id;

        return [
            'name'        => ['required', 'string', 'max:255'],
            'code'        => ['required', 'string', 'max:50', "unique:libraries,code,{$libraryId}"],
            'slug'        => ['nullable', 'string', 'max:255', "unique:libraries,slug,{$libraryId}"],
            'description' => ['nullable', 'string'],
            'address'     => ['nullable', 'string', 'max:255'],
            'phone'       => ['nullable', 'string', 'max:20'],
            'email'       => ['nullable', 'email', 'max:255'],
            'is_active'   => ['boolean'],
        ];
    }

    /**
     * Prepare the data for validation: derive slug from name when missing.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => $this->input('slug') ?: Str::slug($this->input('name')),
        ]);
    }

    /**
     * Return a JSON 422 response instead of the default redirect.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            $this->validationErrorResponse($validator)
        );
    }
}

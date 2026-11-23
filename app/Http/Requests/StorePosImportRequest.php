<?php

namespace App\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class StorePosImportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return $this->user()?->restaurant_id !== null
            ? Response::allow()
            : Response::deny('Your account must be assigned to a restaurant before importing CSV files.');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'csv_file' => [
                'required',
                File::types(['csv', 'txt'])->max('10mb'),
                'extensions:csv',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'csv_file.required' => 'Please select a POS CSV file.',
            'csv_file.extensions' => 'The uploaded file must have a .csv extension.',
        ];
    }
}

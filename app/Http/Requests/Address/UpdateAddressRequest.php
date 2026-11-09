<?php

namespace App\Http\Requests\Address;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'recipient_name' => ['sometimes', 'string', 'max:255'],
            'phone_number'   => ['sometimes', 'string', 'max:20'],
            'city'           => ['sometimes', 'string', 'max:100'],
            'street_address' => ['sometimes', 'string', 'max:255'],
            'postal_code'    => ['nullable', 'string', 'max:20'],
            'is_default'     => ['sometimes', 'boolean'],
        ];
    }
}
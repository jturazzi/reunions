<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreEventTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::user()?->is_admin ?? false;
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255', 'unique:event_types,name'],
            'description' => ['nullable', 'string'],
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::user()?->is_admin ?? false;
    }

    public function rules(): array
    {
        return [
            'title'         => ['required', 'string', 'max:255'],
            'description'   => ['nullable', 'string'],
            'start_at'      => ['required', 'date'],
            'end_at'        => ['required', 'date', 'after:start_at'],
            'location'      => ['nullable', 'string', 'max:255'],
            'event_type_id' => ['required', 'exists:event_types,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'end_at.after' => 'La date de fin doit être postérieure à la date de début.',
        ];
    }
}

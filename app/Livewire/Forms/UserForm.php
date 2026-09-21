<?php

namespace App\Livewire\Forms;

use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Form;

class UserForm extends Form
{
    public ?int $editingId = null;

    public string $name = '';

    public string $email = '';

    public $profile_photo = null;

    public ?string $contact_number = null;

    public ?string $office = null;

    public ?string $address = null;

    public string $user_type = 'user';

    public bool $is_active = true;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($this->editingId)],
            'contact_number' => ['nullable', 'string', 'regex:'.User::PH_CONTACT_REGEX],
            'office' => ['nullable', 'required_if:user_type,admin', 'string', 'min:2', 'max:150'],
            'address' => ['nullable', 'string', 'min:5', 'max:500'],
            'user_type' => ['required', Rule::in(['super_admin', 'admin', 'user'])],
            'is_active' => ['boolean'],
            'profile_photo' => ['nullable', 'image', 'max:2048'],
        ];
    }

    protected function messages(): array
    {
        return ['contact_number.regex' => 'Enter a valid 11-digit PH mobile number starting with 09.'];
    }
}

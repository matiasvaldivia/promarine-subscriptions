<?php
namespace App\Http\Requests\Admin;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'name'     => 'sometimes|string|max:255',
            'username' => 'sometimes|string|max:50|unique:users,username,' . $this->route('user')->id,
            'email'    => 'sometimes|email|unique:users,email,' . $this->route('user')->id,
            'password' => 'nullable|string|min:8|confirmed',
            'roles'    => 'nullable|array',
            'roles.*'  => 'integer|exists:roles,id',
        ];
    }
}

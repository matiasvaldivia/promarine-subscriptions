<?php
namespace App\Http\Requests\Admin;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'name'                  => 'required|string|max:255',
            'username'              => 'required|string|max:50|unique:users,username',
            'email'                 => 'required|email|unique:users,email',
            'password'              => 'required|string|min:8|confirmed',
            'roles'                 => 'nullable|array',
            'roles.*'               => 'integer|exists:roles,id',
        ];
    }
}

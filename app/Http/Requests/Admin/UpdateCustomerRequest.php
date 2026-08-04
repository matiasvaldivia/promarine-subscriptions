<?php
namespace App\Http\Requests\Admin;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'name'           => 'sometimes|string|max:255',
            'email'          => 'sometimes|email|unique:mock_customers,email,' . $this->route('customer')->id,
            'phone'          => 'nullable|string|max:30',
            'province'       => 'nullable|string|max:100',
            'locality'       => 'nullable|string|max:100',
            'postal_code'    => 'nullable|string|max:20',
            'address'        => 'nullable|string|max:255',
            'address_number' => 'nullable|string|max:20',
            'apartment'      => 'nullable|string|max:50',
            'status'         => 'nullable|in:active,inactive,blocked',
        ];
    }
}

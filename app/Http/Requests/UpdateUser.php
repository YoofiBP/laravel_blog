<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUser extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => [],
            "email" => ['email'],
            'address' => [],
            "phoneNo" => ['size:10', 'starts_with:024,054,059,055,020,050,026,056,027,057']
        ];
    }
}

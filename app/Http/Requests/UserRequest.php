<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; //TODO - https://laravel.com/docs/8.x/validation#authorizing-form-requests
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|max: 255',
            'email' => 'required|email|max: 255',
            'password' => 'required' //TODO - https://laravel.com/docs/8.x/validation#validating-passwords
        ];
    }
}

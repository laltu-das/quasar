<?php

namespace Laltu\Quasar\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EnvatoLicenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'envatoItemId' => 'required|numeric',
            'licenseKey' => 'required|string',
        ];
    }

}

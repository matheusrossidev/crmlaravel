<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * FormRequest unico pra criar/atualizar Lead. Usa o metodo HTTP pra decidir
 * se os campos sao required (POST) ou sometimes (PUT/PATCH — permite update
 * parcial pra fluxos como `updateLeadTags` que enviam so {name, tags}).
 */
class LeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Auth e tenancy ja sao garantidos pelos middlewares 'auth' e 'tenant'
        // que rodam antes desse FormRequest. Nao ha por que duplicar aqui.
        return true;
    }

    public function rules(): array
    {
        $isCreate = $this->isMethod('POST');

        // POST -> required, PUT/PATCH -> sometimes|required (permite update parcial)
        $req = $isCreate ? 'required' : 'sometimes|required';

        return [
            'name'        => "{$req}|string|max:255",
            'phone'       => 'nullable|string|max:20',
            'email'       => 'nullable|email|max:191',
            'company'     => 'nullable|string|max:191',
            'value'       => 'nullable|numeric|min:0',
            'source'      => 'nullable|string|max:100',
            'tags'        => 'nullable|array',
            'tags.*'      => 'string|max:50',
            'pipeline_id' => "{$req}|integer|exists:pipelines,id",
            'stage_id'    => "{$req}|integer|exists:pipeline_stages,id",
            'notes'       => 'nullable|string|max:1000000',
            'birthday'    => 'nullable|date',
        ];
    }
}

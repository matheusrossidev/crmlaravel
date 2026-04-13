<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Forms;

use App\Http\Controllers\Controller;
use App\Models\Form;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FormBuilderController extends Controller
{
    public function edit(Form $form): View
    {
        return view('tenant.forms.builder', compact('form'));
    }

    public function save(Request $request, Form $form): JsonResponse
    {
        $data = $request->validate([
            'fields'   => 'required|array|min:1',
            'fields.*' => 'required|array',
            'fields.*.id'       => 'required|string|max:50',
            'fields.*.type'     => 'required|string|max:30',
            'fields.*.label'    => 'required|string|max:191',
            'fields.*.required' => 'nullable|boolean',
            'fields.*.placeholder' => 'nullable|string|max:191',
            'fields.*.help_text'   => 'nullable|string|max:500',
            'fields.*.options'     => 'nullable|array',
            'fields.*.order'       => 'nullable|integer',
        ]);

        // Sort by order
        $fields = collect($data['fields'])->sortBy('order')->values()->toArray();

        $form->update(['fields' => $fields]);

        return response()->json(['success' => true, 'fields' => $fields]);
    }
}

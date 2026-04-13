<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Forms;

use App\Http\Controllers\Controller;
use App\Models\CustomFieldDefinition;
use App\Models\Form;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FormMappingController extends Controller
{
    public function edit(Form $form): View
    {
        $customFields = CustomFieldDefinition::where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'label', 'field_type']);

        return view('tenant.forms.mapping', compact('form', 'customFields'));
    }

    public function save(Request $request, Form $form): JsonResponse
    {
        $data = $request->validate([
            'mappings' => 'required|array',
        ]);

        $form->update(['mappings' => $data['mappings']]);

        return response()->json(['success' => true]);
    }
}

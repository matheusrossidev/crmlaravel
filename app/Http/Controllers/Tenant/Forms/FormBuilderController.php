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
            'fields.*.id'          => 'required|string|max:50',
            'fields.*.type'        => 'required|string|max:30',
            'fields.*.label'       => 'required|string|max:191',
            'fields.*.required'    => 'nullable|boolean',
            'fields.*.placeholder' => 'nullable|string|max:191',
            'fields.*.help_text'   => 'nullable|string|max:500',
            'fields.*.options'     => 'nullable|array',
            'fields.*.order'       => 'nullable|integer',
            'fields.*.step_id'     => 'nullable|string|max:50',

            // Multi-step: steps definition
            'steps'           => 'nullable|array',
            'steps.*.id'      => 'required|string|max:50',
            'steps.*.title'   => 'required|string|max:191',

            // Conditional logic
            'conditional_logic'                  => 'nullable|array',
            'conditional_logic.*.target_field_id' => 'required|string|max:50',
            'conditional_logic.*.field_id'        => 'nullable|string|max:50',
            'conditional_logic.*.operator'        => 'required|string|in:equals,not_equals,contains,not_empty,is_empty',
            'conditional_logic.*.value'           => 'nullable|string|max:500',
        ]);

        // Sort fields by order
        $fields = collect($data['fields'])->sortBy('order')->values()->toArray();

        $update = ['fields' => $fields];

        if (isset($data['steps'])) {
            $update['steps'] = $data['steps'];
        }

        // Only save valid conditions (with source field set)
        $conditions = collect($data['conditional_logic'] ?? [])
            ->filter(fn (array $c) => ! empty($c['field_id']))
            ->values()
            ->toArray();

        $update['conditional_logic'] = $conditions ?: null;

        $form->update($update);

        return response()->json(['success' => true, 'fields' => $fields]);
    }
}

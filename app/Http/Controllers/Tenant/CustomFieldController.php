<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\CustomFieldDefinition;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CustomFieldController extends Controller
{
    private function authorizeAdmin(): void
    {
        if (!auth()->user()->isAdmin() && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Sem permissão para gerenciar campos personalizados.');
        }
    }

    public function index(): View
    {
        $this->authorizeAdmin();

        $fields = CustomFieldDefinition::orderBy('sort_order')->orderBy('created_at')->get();

        return view('tenant.settings.custom-fields', compact('fields'));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeAdmin();

        $request->validate([
            'label'        => 'required|string|max:100',
            'field_type'   => 'required|in:text,textarea,number,currency,date,select,multiselect,checkbox,url,phone,email,file',
            'options'      => 'nullable|string',
            'is_required'  => 'boolean',
            'show_on_card' => 'boolean',
            'is_active'    => 'boolean',
        ]);

        // Verificar limite de campos do tenant
        $tenant = auth()->user()->tenant;
        if ($tenant && $tenant->max_custom_fields > 0) {
            $count = CustomFieldDefinition::count();
            if ($count >= $tenant->max_custom_fields) {
                return response()->json([
                    'success' => false,
                    'message' => "Limite de {$tenant->max_custom_fields} campos personalizados atingido para este plano.",
                ], 422);
            }
        }

        $name = Str::slug($request->input('label'), '_');

        // Garantir unicidade do name
        $baseName  = $name;
        $counter   = 1;
        while (CustomFieldDefinition::where('name', $name)->exists()) {
            $name = $baseName . '_' . $counter++;
        }

        $optionsJson = null;
        if (in_array($request->input('field_type'), ['select', 'multiselect']) && $request->filled('options')) {
            $optionsJson = array_values(array_filter(
                array_map('trim', explode("\n", $request->input('options')))
            ));
        }

        $maxSort = CustomFieldDefinition::max('sort_order') ?? 0;

        $field = CustomFieldDefinition::create([
            'name'         => $name,
            'label'        => $request->input('label'),
            'field_type'   => $request->input('field_type'),
            'options_json' => $optionsJson,
            'is_required'  => $request->boolean('is_required'),
            'show_on_card' => $request->boolean('show_on_card'),
            'is_active'    => $request->boolean('is_active', true),
            'sort_order'   => $maxSort + 1,
        ]);

        return response()->json([
            'success' => true,
            'field'   => $this->formatField($field),
        ], 201);
    }

    public function update(Request $request, CustomFieldDefinition $field): JsonResponse
    {
        $this->authorizeAdmin();

        $request->validate([
            'label'        => 'required|string|max:100',
            'options'      => 'nullable|string',
            'is_required'  => 'boolean',
            'show_on_card' => 'boolean',
            'is_active'    => 'boolean',
            'sort_order'   => 'nullable|integer|min:0',
        ]);

        $optionsJson = $field->options_json;
        if (in_array($field->field_type, ['select', 'multiselect'])) {
            if ($request->filled('options')) {
                $optionsJson = array_values(array_filter(
                    array_map('trim', explode("\n", $request->input('options')))
                ));
            } else {
                $optionsJson = null;
            }
        }

        $field->update([
            'label'        => $request->input('label'),
            'options_json' => $optionsJson,
            'is_required'  => $request->boolean('is_required'),
            'show_on_card' => $request->boolean('show_on_card'),
            'is_active'    => $request->boolean('is_active'),
            'sort_order'   => $request->input('sort_order', $field->sort_order),
        ]);

        return response()->json([
            'success' => true,
            'field'   => $this->formatField($field->fresh()),
            'message' => 'Campo atualizado.',
        ]);
    }

    public function destroy(CustomFieldDefinition $field): JsonResponse
    {
        $this->authorizeAdmin();

        $field->delete(); // cascade deleta os valores

        return response()->json(['success' => true]);
    }

    private function formatField(CustomFieldDefinition $f): array
    {
        return [
            'id'           => $f->id,
            'name'         => $f->name,
            'label'        => $f->label,
            'field_type'   => $f->field_type,
            'options_json' => $f->options_json,
            'is_required'  => $f->is_required,
            'show_on_card' => $f->show_on_card,
            'is_active'    => $f->is_active,
            'sort_order'   => $f->sort_order,
        ];
    }
}

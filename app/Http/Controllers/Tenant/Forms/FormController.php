<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Forms;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\Pipeline;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class FormController extends Controller
{
    public function index(): View
    {
        $forms = Form::with('pipeline:id,name', 'stage:id,name')
            ->withCount('submissions')
            ->orderByDesc('created_at')
            ->get();

        $activeCount       = $forms->where('is_active', true)->count();
        $submissionsMonth  = \App\Models\FormSubmission::whereMonth('submitted_at', now()->month)
            ->whereYear('submitted_at', now()->year)
            ->count();

        return view('tenant.forms.index', compact('forms', 'activeCount', 'submissionsMonth'));
    }

    public function create(): View
    {
        $pipelines = Pipeline::with('stages')->orderBy('sort_order')->get();
        $users     = User::where('tenant_id', activeTenantId())->orderBy('name')->get(['id', 'name']);

        return view('tenant.forms.create', compact('pipelines', 'users'));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'              => 'required|string|max:100',
            'type'              => 'required|string|in:classic,conversational,multistep,popup,embed',
            'pipeline_id'       => 'nullable|integer|exists:pipelines,id',
            'stage_id'          => 'nullable|integer|exists:pipeline_stages,id',
            'assigned_user_id'  => 'nullable|integer|exists:users,id',
            'source_utm'        => 'nullable|string|max:100',
            'confirmation_type' => 'nullable|string|in:message,redirect',
            'confirmation_value' => 'nullable|string|max:2000',
            'notify_emails'     => 'nullable|array',
            'notify_emails.*'   => 'email',
            'max_submissions'   => 'nullable|integer|min:1',
            'expires_at'        => 'nullable|date',
            // Branding
            'brand_color'        => 'nullable|string|max:10',
            'background_color'   => 'nullable|string|max:10',
            'card_color'         => 'nullable|string|max:10',
            'button_color'       => 'nullable|string|max:10',
            'button_text_color'  => 'nullable|string|max:10',
            'label_color'        => 'nullable|string|max:10',
            'input_border_color' => 'nullable|string|max:10',
            'input_bg_color'     => 'nullable|string|max:10',
            'input_text_color'   => 'nullable|string|max:10',
            'font_family'        => 'nullable|string|max:50',
            'border_radius'      => 'nullable|integer|min:0|max:20',
            'logo_url'           => 'nullable|string|max:500',
            'logo_alignment'     => 'nullable|string|in:left,center,right',
        ]);

        $data['slug'] = Str::slug($data['name']) . '-' . Str::random(6);

        $form = Form::create($data);

        return response()->json([
            'success'  => true,
            'form'     => $form,
            'redirect' => route('forms.builder', $form),
        ]);
    }

    public function edit(Form $form): View
    {
        $pipelines = Pipeline::with('stages')->orderBy('sort_order')->get();
        $users     = User::where('tenant_id', activeTenantId())->orderBy('name')->get(['id', 'name']);

        return view('tenant.forms.edit', compact('form', 'pipelines', 'users'));
    }

    public function update(Request $request, Form $form): JsonResponse
    {
        $data = $request->validate([
            'name'               => 'sometimes|required|string|max:100',
            'type'               => 'sometimes|string|in:classic,conversational,multistep,popup,embed',
            'slug'               => 'sometimes|string|max:100|unique:forms,slug,' . $form->id,
            'pipeline_id'        => 'nullable|integer|exists:pipelines,id',
            'stage_id'           => 'nullable|integer|exists:pipeline_stages,id',
            'assigned_user_id'   => 'nullable|integer|exists:users,id',
            'source_utm'         => 'nullable|string|max:100',
            'confirmation_type'  => 'nullable|string|in:message,redirect',
            'confirmation_value' => 'nullable|string|max:2000',
            'notify_emails'      => 'nullable|array',
            'notify_emails.*'    => 'email',
            'max_submissions'    => 'nullable|integer|min:1',
            'expires_at'         => 'nullable|date',
            'send_whatsapp_welcome' => 'nullable|boolean',
            'create_task'        => 'nullable|boolean',
            'task_days_offset'   => 'nullable|integer|min:1|max:30',
            'sequence_id'        => 'nullable|integer',
            'list_id'            => 'nullable|integer',
            // Branding
            'brand_color'        => 'nullable|string|max:10',
            'background_color'   => 'nullable|string|max:10',
            'card_color'         => 'nullable|string|max:10',
            'button_color'       => 'nullable|string|max:10',
            'button_text_color'  => 'nullable|string|max:10',
            'label_color'        => 'nullable|string|max:10',
            'input_border_color' => 'nullable|string|max:10',
            'input_bg_color'     => 'nullable|string|max:10',
            'input_text_color'   => 'nullable|string|max:10',
            'font_family'        => 'nullable|string|max:50',
            'border_radius'      => 'nullable|integer|min:0|max:20',
            'logo_url'           => 'nullable|string|max:500',
            'logo_alignment'     => 'nullable|string|in:left,center,right',
        ]);

        $form->update($data);

        return response()->json(['success' => true, 'form' => $form->fresh()]);
    }

    public function destroy(Form $form): JsonResponse
    {
        $form->delete();

        return response()->json(['success' => true]);
    }

    public function toggle(Form $form): JsonResponse
    {
        $form->update(['is_active' => ! $form->is_active]);

        return response()->json(['success' => true, 'is_active' => $form->is_active]);
    }

    public function uploadLogo(Request $request, Form $form): JsonResponse
    {
        $request->validate([
            'logo' => 'required|file|max:2048|mimes:png,jpg,jpeg,svg,webp',
        ]);

        $path = $request->file('logo')->store("forms/{$form->id}", 'public');
        $url  = asset('storage/' . $path);

        $form->update(['logo_url' => $url]);

        return response()->json(['success' => true, 'logo_url' => $url]);
    }
}

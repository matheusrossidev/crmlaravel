<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Form;
use App\Services\Forms\FormSubmissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FormPublicController extends Controller
{
    public function show(string $slug): View
    {
        $form = Form::withoutGlobalScope('tenant')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        if (! $form->isAcceptingSubmissions()) {
            return view('forms.closed', compact('form'));
        }

        // Track page view
        Form::withoutGlobalScope('tenant')
            ->where('id', $form->id)
            ->increment('views_count');

        return view('forms.public', compact('form'));
    }

    public function submit(Request $request, string $slug): JsonResponse|View
    {
        $form = Form::withoutGlobalScope('tenant')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        try {
            $service = app(FormSubmissionService::class);
            $submission = $service->process(
                $form,
                $request->except(['_token', '_website_url']),
                $request->ip(),
                $request->userAgent(),
            );

            if ($request->expectsJson()) {
                return response()->json([
                    'success'            => true,
                    'confirmation_type'  => $form->confirmation_type,
                    'confirmation_value' => $form->confirmation_value ?? __('forms.default_thanks'),
                ]);
            }

            if ($form->confirmation_type === 'redirect' && $form->confirmation_value) {
                return redirect()->away($form->confirmation_value);
            }

            return view('forms.thanks', [
                'form'    => $form,
                'message' => $form->confirmation_value ?? __('forms.default_thanks'),
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'errors' => $e->errors()], 422);
            }
            return back()->withErrors($e->errors())->withInput();

        } catch (\RuntimeException $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Serve embed script for external sites.
     */
    public function script(string $slug): \Illuminate\Http\Response
    {
        $form = Form::withoutGlobalScope('tenant')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if (! $form) {
            return response('/* Form not found */', 404)
                ->header('Content-Type', 'application/javascript');
        }

        $formUrl = $form->getPublicUrl();
        $js = <<<JS
(function() {
    var iframe = document.createElement('iframe');
    iframe.src = '{$formUrl}?embed=1';
    iframe.style.width = '100%';
    iframe.style.border = 'none';
    iframe.style.minHeight = '500px';
    iframe.setAttribute('loading', 'lazy');
    var scripts = document.getElementsByTagName('script');
    var current = scripts[scripts.length - 1];
    current.parentNode.insertBefore(iframe, current.nextSibling);
    window.addEventListener('message', function(e) {
        if (e.data && e.data.type === 'syncro-form-height') {
            iframe.style.height = e.data.height + 'px';
        }
    });
})();
JS;

        return response($js, 200)
            ->header('Content-Type', 'application/javascript')
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Cache-Control', 'public, max-age=3600');
    }
}

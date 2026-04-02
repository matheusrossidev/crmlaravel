<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeedbackController extends Controller
{
    public function create(Request $request): View
    {
        $urlOrigin = $request->query('from', '');

        return view('tenant.feedback.create', compact('urlOrigin'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type'        => 'required|in:new_feature,improvement,bug,ux_ui,integration,other',
            'area'        => 'nullable|string|max:30',
            'title'       => 'required|string|max:100',
            'description' => 'required|string|max:5000',
            'impact'      => 'nullable|in:blocker,high,medium,low',
            'priority'    => 'nullable|integer|min:1|max:5',
            'can_contact' => 'nullable|boolean',
            'url_origin'  => 'nullable|string|max:500',
            'evidence'    => ['nullable', 'file', 'max:5120', new \App\Rules\SafeImage],
        ]);

        $user   = auth()->user();
        $tenant = $user->tenant;

        $feedback = Feedback::create([
            'tenant_id'     => $tenant->id ?? activeTenantId(),
            'user_id'       => $user->id,
            'type'          => $data['type'],
            'area'          => $data['area'] ?? null,
            'title'         => $data['title'],
            'description'   => $data['description'],
            'impact'        => $data['impact'] ?? null,
            'priority'      => $data['priority'] ?? 3,
            'can_contact'   => $data['can_contact'] ?? false,
            'contact_email' => ($data['can_contact'] ?? false) ? $user->email : null,
            'url_origin'    => $data['url_origin'] ?? null,
            'plan_name'     => $tenant->plan ?? null,
            'user_role'     => $user->role ?? null,
        ]);

        if ($request->hasFile('evidence')) {
            $path = $request->file('evidence')->store('feedbacks', 'public');
            $feedback->update(['evidence_path' => $path]);
        }

        return redirect()->route('feedback.create')->with('success', true);
    }
}

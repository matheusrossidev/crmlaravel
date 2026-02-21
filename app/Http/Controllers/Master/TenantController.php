<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TenantController extends Controller
{
    public function index(): View
    {
        $tenants = Tenant::withCount(['users', 'leads'])
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('master.tenants.index', compact('tenants'));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|max:150|unique:users,email',
            'password' => 'required|string|min:8',
            'plan'     => 'required|in:free,starter,pro,enterprise',
        ]);

        $slug = Str::slug($request->input('name')) . '-' . Str::random(6);

        $tenant = DB::transaction(function () use ($request, $slug) {
            $tenant = Tenant::create([
                'name'   => $request->input('name'),
                'slug'   => $slug,
                'plan'   => $request->input('plan'),
                'status' => 'active',
            ]);

            User::create([
                'tenant_id' => $tenant->id,
                'name'      => $request->input('admin_name', $request->input('name')),
                'email'     => $request->input('email'),
                'password'  => $request->input('password'),
                'role'      => 'admin',
            ]);

            return $tenant;
        });

        return response()->json([
            'success' => true,
            'tenant'  => [
                'id'          => $tenant->id,
                'name'        => $tenant->name,
                'plan'        => $tenant->plan,
                'status'      => $tenant->status,
                'created_at'  => $tenant->created_at->format('d/m/Y'),
                'users_count' => 1,
                'leads_count' => 0,
            ],
        ], 201);
    }

    public function show(Tenant $tenant): View
    {
        $users = User::where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get();

        $leadsStats = [
            'total'  => Lead::forTenant($tenant->id)->count(),
            'active' => Lead::forTenant($tenant->id)->whereNull('won_at')->whereNull('lost_at')->count(),
            'won'    => Lead::forTenant($tenant->id)->whereNotNull('won_at')->count(),
            'lost'   => Lead::forTenant($tenant->id)->whereNotNull('lost_at')->count(),
        ];

        return view('master.tenants.show', compact('tenant', 'users', 'leadsStats'));
    }

    public function update(Request $request, Tenant $tenant): JsonResponse
    {
        $request->validate([
            'status'       => 'required|in:active,inactive,suspended',
            'plan'         => 'required|in:free,starter,pro,enterprise',
            'max_users'    => 'nullable|integer|min:0',
            'max_leads'    => 'nullable|integer|min:0',
            'max_pipelines'=> 'nullable|integer|min:0',
        ]);

        $tenant->update($request->only('status', 'plan', 'max_users', 'max_leads', 'max_pipelines'));

        return response()->json(['success' => true, 'message' => 'Empresa atualizada.']);
    }

    public function destroy(Tenant $tenant): JsonResponse
    {
        $tenant->delete();

        return response()->json(['success' => true]);
    }
}

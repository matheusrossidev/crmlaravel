<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\LostSaleReason;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LostSaleReasonController extends Controller
{
    public function index(): View
    {
        $reasons = LostSaleReason::orderBy('sort_order')->get();

        return view('tenant.settings.lost-reasons', compact('reasons'));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => 'required|string|max:100']);

        $data['sort_order'] = LostSaleReason::max('sort_order') + 1;
        $data['is_active']  = true;

        $reason = LostSaleReason::create($data);

        return response()->json(['success' => true, 'reason' => $reason]);
    }

    public function update(Request $request, LostSaleReason $reason): JsonResponse
    {
        $data = $request->validate([
            'name'      => 'required|string|max:100',
            'is_active' => 'boolean',
        ]);

        $reason->update($data);

        return response()->json(['success' => true, 'reason' => $reason]);
    }

    public function destroy(LostSaleReason $reason): JsonResponse
    {
        if ($reason->lostSales()->exists()) {
            $reason->update(['is_active' => false]);
            return response()->json(['success' => true, 'deactivated' => true]);
        }

        $reason->delete();

        return response()->json(['success' => true]);
    }
}

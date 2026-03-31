<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\PartnerRank;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PartnerRankController extends Controller
{
    public function index(): View
    {
        $ranks = PartnerRank::orderBy('sort_order')->orderBy('min_sales')->get();

        return view('master.partner-ranks.index', compact('ranks'));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'           => 'required|string|max:50',
            'min_sales'      => 'required|integer|min:0',
            'commission_pct' => 'required|numeric|min:0|max:100',
            'color'          => 'nullable|string|max:20',
            'sort_order'     => 'nullable|integer|min:0',
            'image'          => 'nullable|image|max:2048',
        ]);

        $rank = PartnerRank::create([
            'name'           => $data['name'],
            'min_sales'      => $data['min_sales'],
            'commission_pct' => $data['commission_pct'],
            'color'          => $data['color'] ?? '#6b7280',
            'sort_order'     => $data['sort_order'] ?? 0,
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('partner-ranks', 'public');
            $rank->update(['image_path' => $path]);
        }

        return response()->json(['success' => true, 'rank' => $rank]);
    }

    public function update(Request $request, PartnerRank $rank): JsonResponse
    {
        $data = $request->validate([
            'name'           => 'required|string|max:50',
            'min_sales'      => 'required|integer|min:0',
            'commission_pct' => 'required|numeric|min:0|max:100',
            'color'          => 'nullable|string|max:20',
            'sort_order'     => 'nullable|integer|min:0',
            'image'          => 'nullable|image|max:2048',
        ]);

        $rank->update([
            'name'           => $data['name'],
            'min_sales'      => $data['min_sales'],
            'commission_pct' => $data['commission_pct'],
            'color'          => $data['color'] ?? $rank->color,
            'sort_order'     => $data['sort_order'] ?? $rank->sort_order,
        ]);

        if ($request->hasFile('image')) {
            if ($rank->image_path) {
                \Storage::disk('public')->delete($rank->image_path);
            }
            $path = $request->file('image')->store('partner-ranks', 'public');
            $rank->update(['image_path' => $path]);
        }

        return response()->json(['success' => true, 'rank' => $rank->fresh()]);
    }

    public function destroy(PartnerRank $rank): JsonResponse
    {
        if ($rank->image_path) {
            \Storage::disk('public')->delete($rank->image_path);
        }
        $rank->delete();

        return response()->json(['success' => true]);
    }
}

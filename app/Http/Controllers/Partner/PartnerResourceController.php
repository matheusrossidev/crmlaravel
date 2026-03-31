<?php

declare(strict_types=1);

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\PartnerResource;
use Illuminate\View\View;

class PartnerResourceController extends Controller
{
    public function index(): View
    {
        $resources = PartnerResource::published()
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->get();

        $categories = $resources->pluck('category')->filter()->unique()->sort()->values();

        return view('partner.resources.index', compact('resources', 'categories'));
    }

    public function show(string $slug): View
    {
        $resource = PartnerResource::where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        return view('partner.resources.show', compact('resource'));
    }
}

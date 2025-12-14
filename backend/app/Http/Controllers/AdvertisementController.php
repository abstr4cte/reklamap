<?php

namespace App\Http\Controllers;

use App\Models\Advertisement;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdvertisementController extends Controller
{
    public function index(Request $request)
    {
        $query = Advertisement::query();

        if ($request->has('ids')) {
            $ids = explode(',', $request->input('ids'));
            $query->whereIn('id', $ids);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string',
            'type' => 'required|string',
            'location' => 'required|string',
            'city' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'description' => 'required|string',
            'price' => 'required|numeric',
            'width' => 'required|numeric',
            'height' => 'required|numeric',
            'owner_email' => 'required|email',
            'price_unit' => 'required|string',
            'region' => 'required|string',
            'orientation' => 'required|string',
            'traffic_intensity' => 'required|string',
            'offer_type' => 'required|string',
            'phone' => 'nullable|string',
            'contact_preference' => 'nullable|string',
            'has_lighting' => 'boolean',
            'has_image' => 'boolean',
            'price_includes_print' => 'boolean',
            'graphic_design_help' => 'boolean',
            'has_vat_invoice' => 'boolean',
            'price_negotiable' => 'boolean',
            'available_from' => 'nullable|date',
            'images' => 'array',
            'image_url' => 'nullable|string',
        ]);

        $validated['id'] = (string) Str::uuid();

        // Set defaults if not present (though migration has defaults, explicit is good)
        $validated['status'] = $request->input('status', 'active');
        $validated['is_active'] = $request->input('is_active', true);
        $validated['views'] = 0;

        $ad = Advertisement::create($validated);

        return response()->json($ad, 201);
    }

    public function show(string $id)
    {
        return Advertisement::findOrFail($id);
    }

    public function update(Request $request, string $id)
    {
        $ad = Advertisement::findOrFail($id);
        $ad->update($request->all());
        return $ad;
    }

    public function destroy(string $id)
    {
        $ad = Advertisement::findOrFail($id);
        $ad->delete();
        return response()->noContent();
    }

    public function incrementViews(string $id)
    {
        $ad = Advertisement::findOrFail($id);
        $ad->increment('views');
        return response()->json(['message' => 'Views incremented']);
    }

    public function similar(string $id)
    {
        $ad = Advertisement::findOrFail($id);

        $similar = Advertisement::where('city', $ad->city)
            ->where('type', $ad->type)
            ->where('id', '!=', $id)
            ->limit(4)
            ->get();

        return $similar;
    }

    public function report(Request $request)
    {
        $validated = $request->validate([
            'advertisement_id' => 'required|exists:advertisements,id',
            'reason' => 'required|string',
            'details' => 'required|string',
        ]);

        \App\Models\Report::create($validated);

        return response()->json(['message' => 'Report submitted']);
    }
    public function generatePdf(string $id)
    {
        $ad = Advertisement::findOrFail($id);
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.advertisement', ['advertisement' => $ad]);
        return $pdf->download('ogloszenie-' . $ad->id . '.pdf');
    }
}

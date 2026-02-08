<?php

namespace App\Http\Controllers\Api\Shared;

use App\Http\Controllers\Controller;
use App\Domains\Shared\Models\Language;
use App\Http\Resources\LanguageResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LanguageController extends Controller
{
    /**
     * Display a listing of languages.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $languages = Language::query()
            ->when($request->search, fn($q, $search) => 
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
            )
            ->when($request->is_active !== null, fn($q) => 
                $q->where('is_active', $request->boolean('is_active'))
            )
            ->paginate($request->per_page ?? 15);

        return LanguageResource::collection($languages);
    }

    /**
     * Store a newly created language.
     */
    public function store(Request $request): LanguageResource
    {
        $validated = $request->validate([
            'code' => 'required|string|max:10|unique:languages,code',
            'name' => 'required|string|max:100',
            'native_name' => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        $language = Language::create($validated);

        return new LanguageResource($language);
    }

    /**
     * Display the specified language.
     */
    public function show(Language $language): LanguageResource
    {
        return new LanguageResource($language);
    }

    /**
     * Update the specified language.
     */
    public function update(Request $request, Language $language): LanguageResource
    {
        $validated = $request->validate([
            'code' => 'sometimes|string|max:10|unique:languages,code,' . $language->id,
            'name' => 'sometimes|string|max:100',
            'native_name' => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        $language->update($validated);

        return new LanguageResource($language);
    }

    /**
     * Remove the specified language.
     */
    public function destroy(Language $language): JsonResponse
    {
        $language->delete();

        return response()->json(['message' => 'Language deleted successfully'], 204);
    }
}

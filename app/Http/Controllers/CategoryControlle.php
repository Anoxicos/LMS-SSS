<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Vinkla\Hashids\Facades\Hashids;

class CategoryControlle extends Controller
{
    public function index()
    {
       $categories = Category::withCount('courses')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'hashid' => $category->hashid ?? $category->id,
                    'name' => $category->name,
                    'is_active' => $category->is_active,
                    'courses_count' => $category->courses_count,
                    'created_at' => $category->created_at,
                ];
            });

        return Inertia::render('categories/indexCategories', [
            'categories' => $categories,
        ]);
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ]);

        try {
            $category = Category::withTrashed()
                ->where('name', $validated['name'])
                ->first();

            if ($category) {
                if ($category->trashed()) {
                    $category->restore();

                    $category->update([
                        'is_active' => $validated['is_active'],
                    ]);

                    return redirect()
                        ->back()
                        ->with('success', 'Category has been restored successfully.');
                }

                return redirect()
                    ->back()
                    ->withErrors([
                        'name' => 'This category already exists.',
                    ]);
            }

            Category::create([
                'name' => $validated['name'],
                'is_active' => $validated['is_active'],
            ]);

            return redirect()
                ->back()
                ->with('success', 'Category has been created successfully.');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withErrors([
                    'error' => 'An error occurred while creating the category.',
                ]);
        }
    }

    public function update(Request $request, $hashid)
    {
        $id = Hashids::decode($hashid)[0] ?? null;

        if (!$id) {
            return redirect()
                ->back()
                ->withErrors([
                    'error' => 'Invalid category ID.',
                ]);
        }

        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name,' . $category->id],
            'is_active' => ['required', 'boolean'],
        ]);

        try {
            $category->update([
                'name' => $validated['name'],
                'is_active' => $validated['is_active'],
            ]);

            return redirect()
                ->back()
                ->with('success', 'Category has been updated successfully.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withErrors([
                    'error' => 'An error occurred while updating the category.',
                ]);
        }
    }

    public function destroy($hashid)
    {
        $id = Hashids::decode($hashid)[0] ?? null;

        if (!$id) {
            return redirect()
                ->back()
                ->withErrors([
                    'error' => 'Invalid category ID.',
                ]);
        }

        $category = Category::findOrFail($id);

        try {
            $category->delete();

            return redirect()
                ->back()
                ->with('success', 'Category has been deleted successfully.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withErrors([
                    'error' => 'An error occurred while deleting the category.',
                ]);
        }
    }
}

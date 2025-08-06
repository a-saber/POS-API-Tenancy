<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use App\Helpers\ImageHelper;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $search = request()->query('search'); // e.g., ?search=drill
        $perPage = request()->query('per_page', 10); // default 10 per page

        $categories = Category::when($search, function ($query, $search) {
            $query->where('name', 'like', '%' . $search . '%');
        })->paginate($perPage);

        // Update image paths
        $categories->getCollection()->transform(function ($category) {
            if ($category->image_path) {
                $category->image_path = asset('storage/' . $category->image_path);
            }
            return $category;
        });

        return response()->json($categories);
    }

    public function get_menu()
    {
        $search = request()->query('search'); // e.g., ?search=drill
        $perPage = request()->query('per_page', 10); // default 10 per page

        $categories = Category::with(['products.unit']) // eager load products and each product's unit
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', '%' . $search . '%');
            })
            ->paginate($perPage);

        $categories->getCollection()->transform(function ($category) {
            if ($category->image_path) {
                $category->image_path = asset('storage/' . $category->image_path);
            }

            // Optional: handle product images or price casting here too
            $category->products->transform(function ($product) {
                if ($product->image_path) {
                    $product->image_path = asset('storage/' . $product->image_path);
                }
                return $product;
            });

            return $category;
        });

        return response()->json($categories);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|unique:categories,name|max:255',
            'description' => 'nullable|string',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error.',
                'errors'  => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        DB::beginTransaction();

        try {
            // Store image first to get path
            if ($request->hasFile('image')) {
                $imagePath = ImageHelper::storeImage($request->file('image'), 'categories');
                $data['image_path'] = $imagePath;
            }

            $category = Category::create($data);

            DB::commit();

            $category->image_path = $category->image_path ? asset('storage/' . $category->image_path) : null;

            return response()->json([
                'message'  => 'Category created successfully.',
                'category' => $category
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            // If image was stored but DB failed, delete image to rollback
            if (isset($imagePath) && ImageHelper::imageExists($imagePath)) {
                ImageHelper::deleteImage($imagePath);
            }

            return response()->json([
                'message' => 'Something went wrong while saving the category.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        if ($category->image_path) {
            $category->image_path = asset('storage/' . $category->image_path);
        }
        return $category;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
    $validator = Validator::make($request->all(), [
        'name'        => 'nullable|string|unique:categories,name,' . $category->id . '|max:255',
        'description' => 'nullable|string',
        'image'       => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
    ]);
    if ($validator->fails()) {
        return response()->json([
            'message' => 'Validation error.',
            'errors'  => $validator->errors()
        ], 422);
    }

    $data = $validator->validated();

    DB::beginTransaction();

    try {
        $oldImagePath = $category->image_path;

        if ($request->hasFile('image')) {
            // Store new image first
            $newImagePath = ImageHelper::storeImage($request->file('image'), 'categories');
            $data['image_path'] = $newImagePath;
        }

        $category->update($data);

        // If updated successfully and there was an old image and a new image uploaded, delete the old image
        if (isset($newImagePath) && $oldImagePath && ImageHelper::imageExists($oldImagePath)) {
            ImageHelper::deleteImage($oldImagePath);
        }

        DB::commit();

        $category->refresh();
        $category->image_path = $category->image_path ? asset('storage/' . $category->image_path) : null;

        return response()->json([
            'message'  => 'Category updated successfully.',
            'category' => $category
        ]);

    } catch (\Exception $e) {
        DB::rollBack();

        // Rollback image changes
        if (isset($newImagePath) && ImageHelper::imageExists($newImagePath)) {
            ImageHelper::deleteImage($newImagePath);
        }

        return response()->json([
            'message' => 'Something went wrong while updating the category.',
            'error'   => $e->getMessage()
        ], 500);
    }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        DB::beginTransaction();
        try {
            $imagePath = $category->image_path;
            $category->delete(); // DB delete first

            if ($imagePath && ImageHelper::imageExists($imagePath)) {
                $deleted = ImageHelper::deleteImage($imagePath);
                if (!$deleted) {
                    // throw new \Exception('Failed to delete image.');
                    throw new \Exception("Failed to delete image file.");
                }
            }
            DB::commit();

            return response()->json([
                'message' => 'Category deleted successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Something went wrong while deleting the category.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Helpers\ImageHelper;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tables_with = ['unit'];
        if (request()->query('with_category') == 1) {
            $tables_with[]= 'category';
        } 
        $search = request()->query('search'); // e.g., ?search=drill
        $perPage = request()->query('per_page', 10); // default 10 per page

        $products = Product::with($tables_with)->when($search, function ($query, $search) {
            $query->where('name', 'like', '%' . $search . '%');
        })->paginate($perPage);

        $products->getCollection()->transform(function ($product) {
            if ($product->image_path) {
                $product->image_path = asset('storage/' . $product->image_path);
            }
            if($product->category) {
                $product->category->image_path = $product->category->image_path ? asset('storage/' . $product->category->image_path) : null;
            }
            return $product;    
        });
        
        return response()->json($products);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // create validator
        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|unique:products,name|max:255',
            'description' => 'nullable|string',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'unit_id'     => 'required|exists:units,id',
            'category_id' => 'nullable|exists:categories,id',
            'price'       => 'required|numeric',
            'barcode'     => 'nullable|string|unique:products,barcode',
            'brand'       => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        DB::beginTransaction();
        try{
            // store image first to get path
            if ($request->hasFile('image')) {
                $imagePath = ImageHelper::storeImage($request->file('image'), 'products');
                $data['image_path'] = $imagePath;
            }

            $product = Product::create($data);

            DB::commit();
            $product->image_path = asset('storage/' . $product->image_path);
            return response()->json([
                'message' => 'Product created successfully.',
                'product' => $product
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            // delete image if upload failed
            if (isset($imagePath) && ImageHelper::imageExists($imagePath)) {
                ImageHelper::deleteImage($imagePath);
            }
            return response()->json([
                'message' => 'Something went wrong while creating the product.',
                'error'   => $e->getMessage()
            ], 500);
        }
        
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        // return product by id
        if ($product->image_path) {
            $product->image_path = asset('storage/' . $product->image_path);
        }
        // add category and unit
        $product->load('category', 'unit');
        return $product;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        // create validator
        $validator = Validator::make($request->all(), [
            'name'        => 'nullable|string|unique:products,name|max:255',
            'description' => 'nullable|string',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'unit_id'     => 'nullable|exists:units,id',
            'category_id' => 'nullable|exists:categories,id',
            'price'       => 'nullable|numeric',
            'barcode'     => 'nullable|string|unique:products,barcode',
            'brand'       => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        DB::beginTransaction();
        try{
            $oldImagePath = $product->image_path;

            if ($request->hasFile('image')) {
                // Store new image first
                $newImagePath = ImageHelper::storeImage($request->file('image'), 'products');
                $data['image_path'] = $newImagePath;
            }

            $product->update($data);
            if(isset($newImagePath) && $oldImagePath && ImageHelper::imageExists($oldImagePath))
            {
                ImageHelper::deleteImage($oldImagePath);
            }
            DB::commit();
            $product->image_path = $product->image_path ? asset('storage/' . $product->image_path) : null;
            return response()->json([
                'message' => 'Product updated successfully.',
                'product' => $product
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            // Rollback image changes
            if (isset($newImagePath) && ImageHelper::imageExists($newImagePath)) {
                ImageHelper::deleteImage($newImagePath);
            }

            return response()->json([
                'message' => 'Something went wrong while updating the product.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        DB::beginTransaction();
        try {
            $imagePath = $product->image_path;
            $product->delete(); // DB delete first

            if ($imagePath && ImageHelper::imageExists($imagePath)) {
                $deleted = ImageHelper::deleteImage($imagePath);
                if (!$deleted) {
                    // throw new \Exception('Failed to delete image.');
                    throw new \Exception("Failed to delete image file.");
                }
            }
            DB::commit();

            return response()->json([
                'message' => 'Product deleted successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Something went wrong while deleting the product.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}

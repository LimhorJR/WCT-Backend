<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\ApiController;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class CategoryController extends ApiController
{
    public function index()
    {
        $category = Category::all();
        if ($category) {

            return response()->json([
                'message' => 'Lists of all category',
                'category' => $category,

            ], 200);
        } else {

            return response([
                'Message: ' => 'There is no product on the list',
            ], 204);
        }
    }


    public function store(Request $request)
    {
        $input =  $request->validate([
            'name' => 'required',
        ]);

        $category = Category::create($input);
        if ($category->save()) {
            return response()->json([
                'message' => 'Success!',
                'category' => $category
            ], 200);
        } else {

            return response([
                'message' => 'Category is failed to create!',
            ], 422);
        }
    }
    
    public function show($id)
    {
        // Fetch the category with its associated products and their brands
        $category = Category::with(['products.brand'])->select(['id', 'name'])->findOrFail($id);

        // Transform the response to include the brand and category names in each product
        $categoryData = [
            'id' => $category->id,
            'category' => $category->name,
            'products' => $category->products->map(function ($product) use ($category) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'category' => [
                            'id' => $category->id,
                            'name' => $category->name,
                        ],
                    'brand' => [
                            'id' => $product->brand->id,
                            'name' => $product->brand->name,
                            'logo_url' => $product->brand->logo_url,
                        ],
                    'price' => $product->price,
                    'images' => $product->images,
                    'description' => $product->description,                    
                ];
            })
        ];

        return response()->json([
            'message' => 'Category with ID ' . $id . ' has been found.',
            'categories' => $categoryData
        ], 200);
    }


    public function update(Request $request, string $id)
    {
        $category = Category::find($id);
        if ($category) {

            $input = $request->validate([
                'name' => ['required'],
            ]);

            $category->name = $input['name'];


            if ($category->save()) {
                return response()->json([
                    'message: ' => 'Category with ID ' . $id .  ' updated with success to category',
                    'category: ' => $category

                ], 200);
            } else {
                return response([
                    'message' =>  'Category with ID ' . $id . ' could not be updated.',
                ], 422);
            }
        } else {

            return response([
                'message' => 'This categort  with ID ' . $id . 'can not be found',
            ], 404);
        }
    }

    
     //Quering product By category 
     public function queryCategories()
     {
         $categoryId = request()->get('category_id');
         $brandId = request()->get('brand_id');
 
         if ($categoryId || $brandId) {
             // Fetch products by category ID and/or brand ID
             $query = Product::query();
 
             if ($categoryId) {
                 $query->where('category_id', $categoryId);
             }
 
             if ($brandId) {
                 $query->where('brand_id', $brandId);
             }
 
             // Include the related category and brand in the results
             $categorys = $query->with(['category', 'brand'])->get();
 
             // Hide created_at and updated_at fields
             $categorys->makeHidden(['created_at', 'updated_at']);
 
             return response()->json([
                 'category' => $categorys,
             ]);
         } else {
             // Handle the case where no category ID or brand ID is provided
             return response()->json([
                 'message' => 'Category ID or Brand ID is required',
             ], 422);
         }
     }

    public function queryMultipleCategories()
    {
        $categoryIds = request()->get('category_id');
        $brandIds = request()->get('brand_id');
        $priceMin = request()->get('price_min');
        $priceMax = request()->get('price_max');
        $priceCondition = request()->get('price_condition');

        $query = Product::query();

        // Filter by category IDs if provided
        if ($categoryIds) {
            $categoryIdsArray = explode(',', $categoryIds);
            $query->whereIn('category_id', $categoryIdsArray);
        }

        // Filter by brand IDs if provided
        if ($brandIds) {
            $brandIdsArray = explode(',', $brandIds);
            $query->whereIn('brand_id', $brandIdsArray);
        }

        // Filter by price range if provided
        if ($priceMin !== null) {
            $query->where('price', '>=', (float)$priceMin);
        }

        if ($priceMax !== null) {
            $query->where('price', '<=', (float)$priceMax);
        }

        // Filter by specific price condition
        if ($priceCondition) {
            if ($priceCondition == 'under_50') {
                $query->where('price', '<', 50);
            } elseif ($priceCondition == 'above_50') {
                $query->where('price', '>', 50);
            }
        }

        // Select products.* to avoid column conflicts due to join
        $query->select('products.*');

        // Include related category, brand, and discounts in the results
        $products = $query->with(['category', 'brand'])->get();

        // Hide created_at and updated_at fields
        $products->makeHidden(['created_at', 'updated_at']);

          
        return response()->json([
            'product_filter' => $products,
        ], 200);
    }

    public function destroy($id)
    {
        // Try to find the product by its ID
        $category = Category::find($id);
        // Check if the product exists
        if (!$category) {
            return response()->json([
                'error' => 'Category not found'
            ], 404);
        }
        try {
            // Delete the product
            $category->delete();
            // Return a success response
            return response()->json([
                'message' => 'Category deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            // Handle any errors that occur during the deletion
            return response()->json([
                'error' => 'An error occurred while deleting the category'
            ], 500);
        }
    }
}

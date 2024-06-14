<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\ApiController;
use App\Models\Brand;
use App\Models\Product;
use Illuminate\Http\Request;

class BrandController extends ApiController
{
    public function index()
    {
        $brand = Brand::select('id', 'name', 'logo_url')->get();
        if ($brand->isNotEmpty()) {
            return response()->json([
                'message' => 'Lists of all Brand',
                'brands' => $brand, // Use 'brands' instead of 'Brand'
            ], 200);
        } else {
            return response()->json([
                'message' => 'There is no product on the list',
            ], 200);
        }
    }


    public function store(Request $request)
    {
        $input =  $request->validate([
            'name' => 'required',
            'logo_url' => 'required',
        ]);

        $brand = Brand::create($input);
        if ($brand->save()) {
            return response()->json([
                'message' => 'Success!',
                'brand' => $brand
            ], 200);
        } else {

            return response([
                'message' => 'Brand is failed to create!',
            ], 422);
        }
    }
    public function show($id)
    {
        $brand = Brand::select(['id', 'name', 'logo_url'])->findOrFail($id);

        if ($brand) {
            return response()->json([
                'message' => 'Brand with ID ' . $id . ' has been found.',
                'brand' => $brand // Use 'brand' instead of 'Brand'
            ], 200);
        } else {
            return response()->json([
                'message' => 'We could not find the Brand with ID ' . $id,
            ], 404);
        }
    }


    public function update(Request $request, string $id)
    {
        $brand = Brand::find($id);
        if ($brand) {
            $input = $request->validate([
                'name' => ['required'],
                'logo_url' => ['required']
            ]);

            $brand->name = $input['name'];
            $brand->logo_url = $input['logo_url'];

            if ($brand->save()) {
                return response()->json([
                    'message' => 'Brand with ID ' . $id . ' updated with success.',
                    'brand' => $brand
                ], 200);
            } else {
                return response()->json([
                    'message' => 'Brand with ID ' . $id . ' could not be updated.',
                ], 422);
            }
        } else {
            return response()->json([
                'message' => 'Brand with ID ' . $id . ' could not be found.',
            ], 404);
        }
    }


    //Quering product By Brand 
    public function queryCategories()
    {
        $BrandId = request()->get('Brand_id');
        $brandId = request()->get('brand_id');

        if ($BrandId || $brandId) {
            // Fetch products by Brand ID and/or brand ID
            $query = Product::query();

            if ($BrandId) {
                $query->where('Brand_id', $BrandId);
            }

            if ($brandId) {
                $query->where('brand_id', $brandId);
            }

            // Include the related Brand and brand in the results
            $Brands = $query->with(['Brand', 'brand'])->get();

            // Hide created_at and updated_at fields
            $Brands->makeHidden(['created_at', 'updated_at']);

            return response()->json([
                'Brand' => $Brands,
            ]);
        } else {
            // Handle the case where no Brand ID or brand ID is provided
            return response()->json([
                'message' => 'Brand ID or Brand ID is required',
            ], 422);
        }
    }





    public function destroy($id)
    {
        $Brand = Brand::find($id);
        if (!$Brand) {
            return response()->json([
                'Message' => 'Brand not found',
            ], 404);
        }
        $Brand->delete();
        return response()->json([
            'Message' => 'Brand deleted successfully',
        ], 200);
    }

    // Quering product By Brand
    public function queryMultipleBrand()
    {
        $brandIds = request()->get('brand_id');
        $categoryId = request()->get('category_id'); // The category ID you want to filter by

        if ($brandIds) {
            // Fetch products by category IDs and/or brand IDs
            $query = Product::query();

            // Filter by brand IDs if provided
            if ($brandIds) {
                $brandIdsArray = explode(",", $brandIds);
                $query->whereIn('brand_id', $brandIdsArray);
            }

            // Filter by category ID
            $query->where('category_id', $categoryId);

            // Include the related category, brand, and discounts in the results
            $products = $query->with(['brand'])->get();

            // Hide created_at and updated_at fields
            $products->makeHidden(['created_at', 'updated_at']);

            return response()->json([
                'product_filter' => $products,
            ]);
        } else {
            // Handle the case where no brand ID is provided
            return response()->json([
                'message' => 'Brand ID is required',
            ], 422);
        }
    }

}

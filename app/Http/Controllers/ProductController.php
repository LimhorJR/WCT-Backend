<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\ProductCollection;
use App\Http\Resources\ProductResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\Product;


class ProductController extends ApiController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
        {
            // Fetch all products with their associated category, brand, and discounts
            $products = Product::with(['category', 'brand' ])
            ->select(['id', 'name', 'category_id', 'brand_id', 'price', 'images', 'description'])
            ->get();

            if ($products->isNotEmpty()) {
                $products = $products->map(function ($product) {
                    // Assuming a product can have only one discount                    
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'category' => [
                            'id' => $product->category->id,
                            'name' => $product->category->name,
                        ],
                        'brand' => [
                            'id' => $product->brand->id,
                            'name' => $product->brand->name,
                            'logo_url' => $product->brand->logo_url,
                        ],
                        'price' => $product->price,
                        'images' => $product->images,
                        'description' => $product->description,                        
                        ] ;                   
                });

                return response()->json([
                    'message' => 'List of all products',
                    'products' => $products,
                ], 200);
            } else {
                return response()->json([
                    'message' => 'There are no products in the list',
                ], 204);
            }
        }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->all();
        $validator = Validator::make($data, [
            'name' => 'required',
            'category_id' => 'required',
            'images' => 'required',
            'brand_id' => 'required',
            'price' => 'required',
            'description' => 'required'
        ]);
 
        if ($validator->fails()) {
            return $this->sendError(
                'Failed validation',
                [$data],
                422
            );
        }

        $product = Product::create($data);
        $res = [new ProductResource($product)];

        return $this->sendSuccess($res, 'Product is saved');
    }

    /**
     * Display the specified resource.
     */
    public function show($id, Request $request)
    {
        // Find the product by its ID and eager load the category
        $product = Product::with('category' , 'brand')->find($id);
        
        // Check if the product exists
        if ($product) {
            return response()->json([
                'status' => 'success',                            
                'products' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'category' => [
                        'id' => $product->category->id,
                        'name' => $product->category->name,
                    ],
                    'brand' => [
                        'id' => $product->brand->id,
                        'name' => $product->brand->name,
                        'logo_url' => $product->brand->logo_url,
                    ],
                    'price' => $product->price,
                    'images' => $product->images,
                    'description' => $product->description,                        
                    ]                             
            ], 200);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'No product found.'
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
            {
                $product = Product::find($id);
                if (!$product) {
                    return response()->json([
                        'error' => 'Product not found'
                    ], 404);
                }
                try {
                    // Update the product with the request data
                    $product->update($request->all());
            
                    // Return a success response with the updated product
                    return response()->json($product, 200);
                } catch (\Exception $e) {
                    // Handle any errors that occur during the update
                    return response()->json(['error' => 'An error occurred while updating the product'], 500);
                }
                
            }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
        {
            // Try to find the product by its ID
            $product = Product::find($id);

            // Check if the product exists
            if (!$product) {
                return response()->json([
                    'error' => 'Product not found'
                ], 404);
            }
            try {
                // Delete the product
                $product->delete();
                // Return a success response
                return response()->json([
                    'message' => 'Product deleted successfully'
                ], 200);
            } catch (\Exception $e) {
                // Handle any errors that occur during the deletion
                return response()->json([
                    'error' => 'An error occurred while deleting the product'
                ], 500);
            }
        }
}

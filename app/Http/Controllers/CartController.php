<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function addToCart(Request $request)
    {
        // Validate the incoming request
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1'
        ]);

        // Get the authenticated user
        $user = Auth::user();

        // Find or create a cart for the current user
        $cart = Cart::firstOrCreate(['user_id' => $user->id]);

        // Find the cart item or create a new one
        $cartItem = $cart->items()->where('product_id', $validated['product_id'])->first();

        if ($cartItem) {
            // Update the quantity by adding the new quantity
            $cartItem->quantity += $validated['quantity'];
            $cartItem->total_price = $cartItem->quantity * $cartItem->product->price;
            $cartItem->save();
        } else {
            // Create a new cart item
            $product = Product::findOrFail($validated['product_id']);
            $cartItem = $cart->items()->create([
                'product_id' => $validated['product_id'],
                'quantity' => $validated['quantity'],
                'total_price' => $validated['quantity'] * $product->price
            ]);
        }

        // Optional: Send notification to the admin
        // Notification::send(User::where('role', 'admin')->get(), new CartUpdated($cartItem));

        return response()->json($cartItem, 201);
    }

    public function viewCart()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        // Fetch the cart with items for the authenticated user, including brand and category details
        $cart = Cart::with(['items.product.brand', 'items.product.category'])->where('user_id', $user->id)->firstOrFail();

        // Calculate total price for each item
        $cart->items->each(function ($item) {
            $item->total_price = $item->quantity * $item->product->price;
        });

        // Calculate the overall total price of the cart
        $totalCartPrice = $cart->items->sum('total_price');

        return response()->json([
            'cart' => [
                'id' => $cart->id,
                'user_id' => $cart->user_id,
                'items' => $cart->items,
                'totalCartPrice' => $totalCartPrice
            ],
        ]);
    }

    public function removeCart($id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        // Try to find the cart item by its ID and user ID
        $cartItem = CartItem::whereHas('cart', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->where('id', $id)->first();

        // Check if the cart item exists and belongs to the authenticated user
        if (!$cartItem) {
            return response()->json([
                'error' => 'Cart item not found'
            ], 404);
        }

        try {
            // Delete the cart item
            $cartItem->delete();

            // Return a success response
            return response()->json([
                'message' => 'Cart item deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            // Handle any errors that occur during the deletion
            return response()->json([
                'error' => 'An error occurred while deleting the cart item'
            ], 500);
        }
    }


        public function updateQuantityByProductId(Request $request)
        {
            $validated = $request->validate([
                'product_id' => 'required|exists:products,id',
                'quantity' => 'required|integer|min:1'
            ]);

            $user = Auth::user();
            if (!$user) {
                return response()->json(['message' => 'User not authenticated'], 401);
            }   

            // Fetch the cart for the authenticated user
            $cart = Cart::where('user_id', $user->id)->first();
            if (!$cart) {
                return response()->json(['message' => 'Cart not found'], 404);
            }

            // Debugging: check if cart items are retrieved correctly
            $cartItems = $cart->items;
            if (!$cartItems) {
                return response()->json(['message' => 'No cart items found'], 404);
            }

            // Find the cart item by product ID
            $cartItem = $cartItems->where('product_id', $validated['product_id'])->first();
            if (!$cartItem) {
                return response()->json(['message' => 'Cart item not found'], 404);
            }

            // Update the quantity and total price of the cart item
            $cartItem->quantity = $validated['quantity'];
            $cartItem->total_price = $cartItem->quantity * $cartItem->product->price;
            $cartItem->save();

            // Recalculate the total price of the cart
            $totalCartPrice = $cart->items->sum('total_price');

            // Transform the updated items
            $transformedItems = $cart->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'quantity' => $item->quantity,
                    'total_price' => $item->total_price,
                    'products' => $item->product
                ];
            });

            return response()->json([
                'cart' => [
                    'id' => $cart->id,
                    'user_id' => $cart->user_id,
                    'items' => $transformedItems,
                    'totalCartPrice' => $totalCartPrice
                ],
            ]);
        }

    

}

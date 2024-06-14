<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Notification;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{

    public function getAllOrders()
    {
        $orders = Order::where('user_id', Auth::user()->id)
            ->join('products', 'orders.product_name', '=', 'products.name')
            ->select('orders.*', 'products.images as product_img')->orderByDesc("created_at") // Select all columns from orders and product_img_url from products
            ->get();
        return response()->json($orders, 200);
    }
    
    public function checkout(Request $request)
    {
        $user = Auth::user();
        $cart = Cart::with('items.product')->where('user_id', $user->id)->firstOrFail();

        if ($cart->items->isEmpty()) {
            return response()->json(['message' => 'Your cart is empty'], 400);
        }

        
        
        $orders = [];
        foreach ($cart->items as $item) {
            $order = Order::create([
                'user_id' => $user->id,
                'product_name' => $item->product->name,
                'quantity' => $item->quantity,
                'total_price' => $item->total_price,
                'status' => 'pending',
            ]);

            // Notify Admin of the new order
            Notification::create([
                'user_id' => User::where('role', 'admin')->first()->id,
                'message' => 'New order received: Order ID ' . $order->id . ' from ' . $user->name,
                'type' => 'order_placed',
                'read' => false,
            ]);

            $orders[] = $order;
        }

        return response()->json(['message' => 'Checkout successful, admin notified', 'orders' => $orders], 200);
    }

    
    
    
}

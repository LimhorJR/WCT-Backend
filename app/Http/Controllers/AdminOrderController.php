<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminOrderController extends Controller
{
    public function viewOrder(){        
        $user = Auth::user();
        $orders = Order::with('user')->get();
        return response()->json([
            'orders' => $orders
        ]);;    
    }
    public function approveOrder($id)
    {
        $user = Auth::user();

        $order = Order::find($id);
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }
        $order->status = 'approved';
        $order->save();

        // Check if the order's user has the role of "customer"
        $orderUser = User::find($order->user_id);
        if ($orderUser && $orderUser->role === 'customer') {
            // Notify User 
            Notification::create([
                'user_id' => $order->user_id,
                'message' => 'Your order has been approved ',
                'type' => 'order_approved',
                'read' => false,
            ]);
        }

        

        return response()->json(['message' => 'Order approved successfully']);
    }

    public function deleteOrder($id)
    {
        $user = Auth::user();

        $order = Order::find($id);
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }
        $order->delete();

        return response()->json(['message' => 'Order deleted successfully']);
    }
    
    public function getOrderStatistics()
    {
        // Get the total number of pending orders
        $totalPending = Order::where('status', 'pending')->count();

        // Get the total number of approved orders
        $totalApproved = Order::where('status', 'approved')->count();

        return response()->json([
            'Total_Statistics' => [
                    'total_pending' => $totalPending,
                    'total_approved' => $totalApproved,
            ]
        ]);
    }
}

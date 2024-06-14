<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function getAdminNotifications()
    {
        $user = Auth::user();

        // Ensure the user has the role of "admin"
        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $notifications = Notification::where('user_id', $user->id)
                                     ->where('read', false)
                                     ->get();

        return response()->json($notifications);
    }

    public function markAsRead(Request $request)
    {
        $request->user()->notifications()->update(['read' => true]);
        return response()->json(['message' => 'Notifications marked as read']);
    }

    public function getUserNotifications()
    {
        $user = Auth::user();

        // Ensure the user has the role of "customer"
        if ($user->role !== 'customer') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $notifications = Notification::where('user_id', $user->id)
                                     ->where('read', false)
                                     ->get();

        return response()->json($notifications);
    }

}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()->sellerNotifications()->latest()->paginate(10);

        return view('dashboard.notifications.index', [
            'notifications' => $notifications,
        ]);
    }
}

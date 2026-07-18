<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use App\Support\NotificationService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = UserNotification::where('user_id', auth()->id())
            ->latest()
            ->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    public function read(UserNotification $notification)
    {
        abort_if($notification->user_id !== auth()->id(), 403);

        $notification->markAsRead();

        return $notification->action_url
            ? redirect($notification->action_url)
            : back();
    }

    public function readAll(Request $request)
    {
        NotificationService::markAllRead($request->user());

        return back()->with('success', 'All notifications marked as read.');
    }
}

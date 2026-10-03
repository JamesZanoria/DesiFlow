<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationsController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();

        if (!$userId) {
            return response()->json(['notifications' => []], 401);
        }

        $notifications = Notification::where('user_id', $userId)
            ->orderByDesc('created_at');

        // Filter by type if requested
        if ($request->filled('type')) {
            $notifications = $notifications->where('type', $request->input('type'));
        }

        $results = $notifications->get();

        if ($request->wantsJson() || $request->query('format') === 'json') {
            return response()->json([
                'notifications' => $results->map(fn($n) => [
                    'id' => $n->id,
                    'title' => $n->title,
                    'message' => $n->message,
                    'icon' => $n->icon,
                    'level' => $n->level,
                    'action_url' => $n->action_url,
                    'created_at' => $n->created_at->diffForHumans(),
                ])->values(),
                'unread_count' => Notification::where('user_id', $userId)->whereNull('read_at')->count(),
            ]);
        }

        return view('admin.notifications.index', [
            'notifications' => $results,
            'selectedType' => $request->input('type', 'all'),
        ]);
    }

    public function markAllAsRead()
    {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        Notification::where('user_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read.',
        ]);
    }

    public function markRead(Request $request)
    {
        $userId = Auth::id();
        $notificationId = $request->input('id');

        if (!$userId || !$notificationId) {
            return response()->json(['success' => false, 'message' => 'Invalid request'], 400);
        }

        $notification = Notification::where('user_id', $userId)
            ->where('id', $notificationId)
            ->first();

        if (!$notification) {
            return response()->json(['success' => false, 'message' => 'Notification not found'], 404);
        }

        $notification->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read.',
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Support\AdminPortalNotificationSupport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminNotificationController extends Controller
{
    public function index(Request $request)
    {
        $conn = $this->schoolConnection();
        $adminId = (int) Auth::id();

        $items = AdminPortalNotificationSupport::listForAdmin($conn, $adminId, 50);
        $unread = AdminPortalNotificationSupport::unreadCount($conn, $adminId);

        return response()->json([
            'success'      => true,
            'data'         => $items,
            'unread_count' => $unread,
        ]);
    }

    public function markRead(Request $request)
    {
        $conn = $this->schoolConnection();
        $adminId = (int) Auth::id();

        $notificationId = $request->integer('id');
        if (! $notificationId && $request->isJson()) {
            $notificationId = (int) ($request->json('id') ?? 0);
        }

        AdminPortalNotificationSupport::markRead(
            $conn,
            $adminId,
            $notificationId ?: null
        );

        return response()->json([
            'success'      => true,
            'unread_count' => AdminPortalNotificationSupport::unreadCount($conn, $adminId),
        ]);
    }

    private function schoolConnection(): string
    {
        return config('database.school_connection')
            ?? (request()->is('grade-school-admin*', 'api/grade-school-admin*') ? 'mysql_gs' : 'mysql_jh');
    }
}

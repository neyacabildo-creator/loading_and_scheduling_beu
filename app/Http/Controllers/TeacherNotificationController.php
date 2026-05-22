<?php

namespace App\Http\Controllers;

use App\Support\TeacherDatabaseSupport;
use App\Support\TeacherPortalNotificationSupport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeacherNotificationController extends Controller
{
    public function index(Request $request)
    {
        $teacherId = (int) Auth::id();
        $connections = $this->connectionsForUser();

        $items = [];
        $unread = 0;
        foreach ($connections as $conn) {
            $items = array_merge($items, TeacherPortalNotificationSupport::listForTeacher($conn, $teacherId));
            $unread += TeacherPortalNotificationSupport::unreadCount($conn, $teacherId);
        }

        usort($items, fn ($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));

        return response()->json([
            'success'      => true,
            'data'         => array_slice($items, 0, 30),
            'unread_count' => $unread,
        ]);
    }

    public function markRead(Request $request)
    {
        $teacherId = (int) Auth::id();
        foreach ($this->connectionsForUser() as $conn) {
            TeacherPortalNotificationSupport::markRead(
                $conn,
                $teacherId,
                $request->integer('id') ?: null
            );
        }

        $unread = 0;
        foreach ($this->connectionsForUser() as $conn) {
            $unread += TeacherPortalNotificationSupport::unreadCount($conn, $teacherId);
        }

        return response()->json([
            'success'      => true,
            'unread_count' => $unread,
        ]);
    }

    /** @return array<int, string> */
    private function connectionsForUser(): array
    {
        $user = Auth::user();
        if ($user && ! $user->relationLoaded('role')) {
            $user->load('role');
        }
        if ($user && $user->role && $user->role->name === 'shared_teacher') {
            return ['mysql_jh', 'mysql_gs'];
        }

        return [TeacherDatabaseSupport::connectionFromContext()];
    }
}

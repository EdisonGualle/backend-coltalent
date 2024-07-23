<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;

class NotificationController extends Controller
{
    // Obtener notificaciones no leídas
    public function getUnreadNotifications(Request $request)
    {
        $user = $request->user();

        // Obtener notificaciones no leídas
        $notifications = Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->get();

        return response()->json($notifications);
    }

    // Marcar notificaciones como leídas, excepto las de tipo "Solicitud pendiente"
    public function markAsRead(Request $request)
    {
        $notificationIds = $request->input('notification_ids', []);

        Notification::whereIn('id', $notificationIds)
            ->where('type', '!=', 'Solicitud pendiente')
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'Notificaciones marcadas como leídas']);
    }
}

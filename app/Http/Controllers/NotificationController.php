<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * 사용자의 알림 목록을 표시합니다.
     */
    public function index(Request $request)
    {
        $query = Auth::user()->notifications();
        
        // 필터링 적용
        if ($request->has('filter')) {
            if ($request->filter === 'unread') {
                $query->whereNull('read_at');
            } elseif ($request->filter === 'read') {
                $query->whereNotNull('read_at');
            }
        }
        
        $notifications = $query->latest()->paginate(20);
        
        return view('notifications.index', compact('notifications'));
    }
    
    /**
     * 사용자의 읽지 않은 알림을 가져옵니다.
     */
    public function unread()
    {
        $notifications = Auth::user()->unreadNotifications;
        
        return response()->json([
            'success' => true,
            'data' => [
                'notifications' => $notifications,
                'unread_count' => $notifications->count()
            ]
        ]);
    }
    
    /**
     * 특정 알림을 읽음으로 표시합니다.
     */
    public function markAsRead($id)
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        
        if (!$notification->read_at) {
            $notification->markAsRead();
        }
        
        return response()->json([
            'success' => true,
            'message' => '알림을 읽음으로 표시했습니다.'
        ]);
    }
    
    /**
     * 모든 알림을 읽음으로 표시합니다.
     */
    public function markAllAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();
        
        return response()->json([
            'success' => true,
            'message' => '모든 알림을 읽음으로 표시했습니다.'
        ]);
    }
    
    /**
     * 특정 알림을 삭제합니다.
     */
    public function destroy($id)
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->delete();
        
        return response()->json([
            'success' => true,
            'message' => '알림이 삭제되었습니다.'
        ]);
    }
    
    /**
     * 읽은 알림을 모두 삭제합니다.
     */
    public function clearRead()
    {
        Auth::user()->notifications()
            ->whereNotNull('read_at')
            ->delete();
        
        return response()->json([
            'success' => true,
            'message' => '읽은 알림이 모두 삭제되었습니다.'
        ]);
    }
}

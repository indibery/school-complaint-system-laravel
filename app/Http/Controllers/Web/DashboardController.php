<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Complaint;
use App\Models\User;
use App\Models\Category;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Show dashboard.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // 기본 통계 데이터
        $stats = $this->getDashboardStats($user);
        
        // 최근 민원 목록
        $recentComplaints = $this->getRecentComplaints($user);
        
        // 나의 담당 민원 (교직원인 경우)
        $myComplaints = null;
        if ($user->hasRole(['admin', 'staff'])) {
            $myComplaints = $this->getMyComplaints($user);
        }
        
        return view('dashboard', compact('stats', 'recentComplaints', 'myComplaints'));
    }
    
    /**
     * Get dashboard statistics.
     */
    private function getDashboardStats($user)
    {
        $query = Complaint::query();
        
        // 관리자가 아닌 경우 접근 제어 적용
        if (!$user->hasRole('admin')) {
            $query->where('user_id', $user->id);
        }
        
        // 기본 통계
        $stats = [
            'total' => $query->count(),
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'in_progress' => (clone $query)->where('status', 'in_progress')->count(),
            'resolved' => (clone $query)->where('status', 'resolved')->count(),
            'urgent' => (clone $query)->where('priority', 'urgent')->count(),
        ];
        
        return $stats;
    }
    
    /**
     * Get recent complaints.
     */
    private function getRecentComplaints($user, $limit = 10)
    {
        $query = Complaint::with(['category', 'complainant', 'assignedTo'])
            ->orderByDesc('created_at')
            ->limit($limit);
        
        // 관리자가 아닌 경우 접근 제어 적용
        if (!$user->hasRole('admin')) {
            $query->where('user_id', $user->id);
        }
        
        return $query->get();
    }
    
    /**
     * Get my assigned complaints.
     */
    private function getMyComplaints($user, $limit = 5)
    {
        return Complaint::with(['category', 'complainant'])
            ->where('assigned_to', $user->id)
            ->whereNotIn('status', ['resolved', 'closed', 'cancelled'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }
}

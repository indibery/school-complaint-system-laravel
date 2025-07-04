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
        if ($user->hasRole(['teacher', 'staff', 'admin', 'department_head'])) {
            $myComplaints = $this->getMyComplaints($user);
        }
        
        return view('dashboard.index', compact('stats', 'recentComplaints', 'myComplaints'));
    }
    
    /**
     * Get dashboard statistics.
     */
    private function getDashboardStats($user)
    {
        $query = Complaint::query();
        
        // 사용자 역할에 따른 필터링
        if ($user->hasRole('parent')) {
            $query->where('created_by', $user->id);
        } elseif ($user->hasRole(['teacher', 'staff']) && !$user->hasRole(['admin', 'department_head'])) {
            $query->where(function($q) use ($user) {
                $q->where('assigned_to', $user->id)
                  ->orWhere('created_by', $user->id);
            });
        }
        
        $today = Carbon::today();
        $thisWeek = Carbon::now()->startOfWeek();
        $thisMonth = Carbon::now()->startOfMonth();
        
        return [
            'total' => $query->count(),
            'pending' => $query->where('status', 'pending')->count(),
            'in_progress' => $query->where('status', 'in_progress')->count(),
            'resolved' => $query->where('status', 'resolved')->count(),
            'urgent' => $query->where('priority', 'urgent')->count(),
            'today' => $query->whereDate('created_at', $today)->count(),
            'this_week' => $query->where('created_at', '>=', $thisWeek)->count(),
            'this_month' => $query->where('created_at', '>=', $thisMonth)->count(),
        ];
    }
    
    /**
     * Get recent complaints.
     */
    private function getRecentComplaints($user, $limit = 10)
    {
        $query = Complaint::with(['category', 'complainant', 'assignedTo'])
            ->orderByDesc('created_at');
        
        // 사용자 역할에 따른 필터링
        if ($user->hasRole('parent')) {
            $query->where('created_by', $user->id);
        } elseif ($user->hasRole(['teacher', 'staff']) && !$user->hasRole(['admin', 'department_head'])) {
            $query->where(function($q) use ($user) {
                $q->where('assigned_to', $user->id)
                  ->orWhere('created_by', $user->id);
            });
        }
        
        return $query->limit($limit)->get();
    }
    
    /**
     * Get my assigned complaints.
     */
    private function getMyComplaints($user, $limit = 5)
    {
        return Complaint::with(['category', 'complainant'])
            ->where('assigned_to', $user->id)
            ->whereNotIn('status', ['closed', 'cancelled'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }
    
    /**
     * Get stats for AJAX requests.
     */
    public function getStats(Request $request)
    {
        $user = $request->user();
        $stats = $this->getDashboardStats($user);
        
        // 긴급 민원 목록
        $urgentComplaints = Complaint::where('priority', 'urgent')
            ->whereNotIn('status', ['closed', 'cancelled'])
            ->with(['category', 'complainant'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(function($complaint) {
                return [
                    'id' => $complaint->id,
                    'title' => $complaint->title,
                    'complainant' => $complaint->complainant->name ?? '알 수 없음',
                    'category' => $complaint->category->name ?? '미분류',
                    'created_at' => $complaint->created_at->diffForHumans(),
                ];
            });
        
        return response()->json([
            'urgent_count' => $stats['urgent'],
            'urgent_complaints' => $urgentComplaints,
        ]);
    }
    
    /**
     * Show reports page.
     */
    public function reports(Request $request)
    {
        // 관리자/부서장만 접근 가능
        if (!$request->user()->hasRole(['admin', 'department_head'])) {
            abort(403);
        }
        
        $period = $request->input('period', '30'); // 기본 30일
        $startDate = Carbon::now()->subDays($period);
        
        $data = [
            'complaints_by_status' => $this->getComplaintsByStatus($startDate),
            'complaints_by_category' => $this->getComplaintsByCategory($startDate),
            'complaints_by_date' => $this->getComplaintsByDate($startDate),
            'resolution_time_avg' => $this->getAverageResolutionTime($startDate),
            'satisfaction_avg' => $this->getAverageSatisfaction($startDate),
        ];
        
        return view('dashboard.reports', compact('data', 'period'));
    }
    
    /**
     * Export reports.
     */
    public function export(Request $request)
    {
        // 관리자/부서장만 접근 가능
        if (!$request->user()->hasRole(['admin', 'department_head'])) {
            abort(403);
        }
        
        $period = $request->input('period', '30');
        $format = $request->input('format', 'csv');
        
        // TODO: 실제 내보내기 구현
        return response()->json(['message' => '내보내기 기능 구현 예정']);
    }
    
    /**
     * Show settings page.
     */
    public function settings(Request $request)
    {
        // 관리자만 접근 가능
        if (!$request->user()->hasRole('admin')) {
            abort(403);
        }
        
        $categories = Category::orderBy('sort_order')->get();
        $users = User::where('status', 'active')->orderBy('name')->get();
        
        return view('dashboard.settings', compact('categories', 'users'));
    }
    
    /**
     * Update system settings.
     */
    public function updateSystemSettings(Request $request)
    {
        // 관리자만 접근 가능
        if (!$request->user()->hasRole('admin')) {
            abort(403);
        }
        
        // TODO: 시스템 설정 업데이트 구현
        return back()->with('success', '시스템 설정이 업데이트되었습니다.');
    }
    
    /**
     * Show categories management.
     */
    public function categories(Request $request)
    {
        // 관리자만 접근 가능
        if (!$request->user()->hasRole('admin')) {
            abort(403);
        }
        
        $categories = Category::with('parent')->orderBy('sort_order')->get();
        
        return view('dashboard.categories', compact('categories'));
    }
    
    /**
     * Store new category.
     */
    public function storeCategory(Request $request)
    {
        // 관리자만 접근 가능
        if (!$request->user()->hasRole('admin')) {
            abort(403);
        }
        
        $request->validate([
            'name' => 'required|string|max:100|unique:categories,name',
            'description' => 'nullable|string|max:500',
            'parent_id' => 'nullable|exists:categories,id',
            'color' => 'nullable|string',
            'icon' => 'nullable|string|max:50',
        ]);
        
        $category = Category::create([
            'name' => $request->name,
            'description' => $request->description,
            'parent_id' => $request->parent_id,
            'color' => $request->color,
            'icon' => $request->icon,
            'sort_order' => Category::max('sort_order') + 1,
            'is_active' => true,
        ]);
        
        return back()->with('success', '카테고리가 생성되었습니다.');
    }
    
    /**
     * Update category.
     */
    public function updateCategory(Request $request, Category $category)
    {
        // 관리자만 접근 가능
        if (!$request->user()->hasRole('admin')) {
            abort(403);
        }
        
        $request->validate([
            'name' => 'required|string|max:100|unique:categories,name,' . $category->id,
            'description' => 'nullable|string|max:500',
            'parent_id' => 'nullable|exists:categories,id',
            'color' => 'nullable|string',
            'icon' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);
        
        // 순환 참조 방지
        if ($request->parent_id == $category->id) {
            return back()->withErrors(['parent_id' => '자기 자신을 부모로 설정할 수 없습니다.']);
        }
        
        $category->update($request->only(['name', 'description', 'parent_id', 'color', 'icon', 'is_active']));
        
        return back()->with('success', '카테고리가 수정되었습니다.');
    }
    
    /**
     * Delete category.
     */
    public function destroyCategory(Category $category)
    {
        // 관리자만 접근 가능
        if (!auth()->user()->hasRole('admin')) {
            abort(403);
        }
        
        // 하위 카테고리 확인
        if ($category->children()->exists()) {
            return back()->withErrors(['error' => '하위 카테고리가 있는 카테고리는 삭제할 수 없습니다.']);
        }
        
        // 관련 민원 확인
        if ($category->complaints()->exists()) {
            return back()->withErrors(['error' => '관련 민원이 있는 카테고리는 삭제할 수 없습니다.']);
        }
        
        $category->delete();
        
        return back()->with('success', '카테고리가 삭제되었습니다.');
    }
    
    // 통계 관련 private 메소드들
    private function getComplaintsByStatus($startDate)
    {
        return Complaint::where('created_at', '>=', $startDate)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }
    
    private function getComplaintsByCategory($startDate)
    {
        return Complaint::join('categories', 'complaints.category_id', '=', 'categories.id')
            ->where('complaints.created_at', '>=', $startDate)
            ->selectRaw('categories.name, COUNT(*) as count')
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('count')
            ->pluck('count', 'name')
            ->toArray();
    }
    
    private function getComplaintsByDate($startDate)
    {
        return Complaint::where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();
    }
    
    private function getAverageResolutionTime($startDate)
    {
        $resolvedComplaints = Complaint::where('created_at', '>=', $startDate)
            ->whereNotNull('resolved_at')
            ->get();
        
        if ($resolvedComplaints->isEmpty()) {
            return null;
        }
        
        $totalHours = $resolvedComplaints->sum(function($complaint) {
            return $complaint->created_at->diffInHours($complaint->resolved_at);
        });
        
        return round($totalHours / $resolvedComplaints->count(), 1);
    }
    
    private function getAverageSatisfaction($startDate)
    {
        return Complaint::where('created_at', '>=', $startDate)
            ->whereNotNull('satisfaction_rating')
            ->avg('satisfaction_rating');
    }
}

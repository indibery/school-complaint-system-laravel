<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Api\Category\CategoryStoreRequest;
use App\Http\Requests\Api\Category\CategoryUpdateRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoryController extends BaseApiController
{
    /**
     * Display a listing of categories.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Category::query();

            // 활성/비활성 필터
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            // 부모 카테고리 필터
            if ($request->has('parent_id')) {
                $query->where('parent_id', $request->input('parent_id'));
            }

            // 최상위 카테고리만 조회
            if ($request->boolean('top_level_only')) {
                $query->whereNull('parent_id');
            }

            // 하위 카테고리 포함 여부
            if ($request->boolean('with_children')) {
                $query->with('children');
            }

            // 검색
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // 정렬
            $sortBy = $request->input('sort_by', 'sort_order');
            $sortOrder = $request->input('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            // 페이지네이션 또는 전체 조회
            if ($request->boolean('paginate', true)) {
                $perPage = min($request->input('per_page', 20), 100);
                $categories = $query->paginate($perPage);
                
                return $this->paginatedResourceResponse(
                    CategoryResource::collection($categories),
                    '카테고리 목록을 조회했습니다.'
                );
            } else {
                $categories = $query->get();
                
                return $this->successResponse(
                    CategoryResource::collection($categories),
                    '카테고리 목록을 조회했습니다.'
                );
            }

        } catch (\Exception $e) {
            return $this->errorResponse(
                '카테고리 목록 조회 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Store a newly created category.
     */
    public function store(CategoryStoreRequest $request): JsonResponse
    {
        try {
            // 권한 체크
            if (!$request->user()->hasRole(['admin', 'super_admin'])) {
                return $this->errorResponse(
                    '카테고리를 생성할 권한이 없습니다.',
                    403
                );
            }

            DB::beginTransaction();

            $data = $request->validated();
            
            // 정렬 순서 자동 설정
            if (!isset($data['sort_order'])) {
                $data['sort_order'] = Category::max('sort_order') + 1;
            }

            $category = Category::create($data);

            DB::commit();

            return $this->createdResponse(
                new CategoryResource($category),
                '카테고리가 성공적으로 생성되었습니다.'
            );

        } catch (\Exception $e) {
            DB::rollBack();

            return $this->errorResponse(
                '카테고리 생성 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Display the specified category.
     */
    public function show(Request $request, Category $category): JsonResponse
    {
        try {
            // 하위 카테고리 포함 여부
            if ($request->boolean('with_children')) {
                $category->load('children');
            }

            // 상위 카테고리 포함 여부
            if ($request->boolean('with_parent')) {
                $category->load('parent');
            }

            // 민원 수 포함 여부
            if ($request->boolean('with_complaints_count')) {
                $category->loadCount('complaints');
            }

            return $this->successResponse(
                new CategoryResource($category),
                '카테고리를 조회했습니다.'
            );

        } catch (\Exception $e) {
            return $this->errorResponse(
                '카테고리 조회 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Update the specified category.
     */
    public function update(CategoryUpdateRequest $request, Category $category): JsonResponse
    {
        try {
            // 권한 체크
            if (!$request->user()->hasRole(['admin', 'super_admin'])) {
                return $this->errorResponse(
                    '카테고리를 수정할 권한이 없습니다.',
                    403
                );
            }

            DB::beginTransaction();

            $data = $request->validated();
            
            // 자기 자신을 부모로 설정하는 것 방지
            if (isset($data['parent_id']) && $data['parent_id'] == $category->id) {
                return $this->errorResponse(
                    '자기 자신을 부모 카테고리로 설정할 수 없습니다.',
                    422
                );
            }

            // 순환 참조 방지
            if (isset($data['parent_id']) && $this->wouldCreateCircularReference($category, $data['parent_id'])) {
                return $this->errorResponse(
                    '순환 참조가 발생할 수 있는 부모 카테고리입니다.',
                    422
                );
            }

            $category->update($data);

            DB::commit();

            return $this->updatedResponse(
                new CategoryResource($category),
                '카테고리가 성공적으로 수정되었습니다.'
            );

        } catch (\Exception $e) {
            DB::rollBack();

            return $this->errorResponse(
                '카테고리 수정 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Remove the specified category.
     */
    public function destroy(Request $request, Category $category): JsonResponse
    {
        try {
            // 권한 체크
            if (!$request->user()->hasRole(['admin', 'super_admin'])) {
                return $this->errorResponse(
                    '카테고리를 삭제할 권한이 없습니다.',
                    403
                );
            }

            // 하위 카테고리 존재 확인
            if ($category->children()->exists()) {
                return $this->errorResponse(
                    '하위 카테고리가 있는 카테고리는 삭제할 수 없습니다.',
                    422
                );
            }

            // 관련 민원 존재 확인
            if ($category->complaints()->exists()) {
                return $this->errorResponse(
                    '관련 민원이 있��� 카테고리는 삭제할 수 없습니다.',
                    422
                );
            }

            DB::beginTransaction();

            $category->delete();

            DB::commit();

            return $this->deletedResponse('카테고리가 성공적으로 삭제되었습니다.');

        } catch (\Exception $e) {
            DB::rollBack();

            return $this->errorResponse(
                '카테고리 삭제 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Toggle category active status.
     */
    public function toggleStatus(Request $request, Category $category): JsonResponse
    {
        try {
            // 권한 체크
            if (!$request->user()->hasRole(['admin', 'super_admin'])) {
                return $this->errorResponse(
                    '카테고리 상태를 변경할 권한이 없습니다.',
                    403
                );
            }

            DB::beginTransaction();

            $category->update(['is_active' => !$category->is_active]);

            DB::commit();

            $status = $category->is_active ? '활성화' : '비활성화';

            return $this->updatedResponse(
                new CategoryResource($category),
                "카테고리가 성공적으로 {$status}되었습니다."
            );

        } catch (\Exception $e) {
            DB::rollBack();

            return $this->errorResponse(
                '카테고리 상태 변경 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Bulk update category sort orders.
     */
    public function bulkUpdateSortOrder(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'categories' => 'required|array|min:1',
                'categories.*.id' => 'required|integer|exists:categories,id',
                'categories.*.sort_order' => 'required|integer|min:0',
            ]);

            // 권한 체크
            if (!$request->user()->hasRole(['admin', 'super_admin'])) {
                return $this->errorResponse(
                    '카테고리 정렬을 변경할 권한이 없습니다.',
                    403
                );
            }

            DB::beginTransaction();

            $categories = $request->input('categories');
            $updatedCount = 0;

            foreach ($categories as $categoryData) {
                $category = Category::find($categoryData['id']);
                if ($category) {
                    $category->update(['sort_order' => $categoryData['sort_order']]);
                    $updatedCount++;
                }
            }

            DB::commit();

            return $this->successResponse(
                ['updated_count' => $updatedCount],
                "{$updatedCount}개의 카테고리 정렬이 성공적으로 변경되었습니다."
            );

        } catch (\Exception $e) {
            DB::rollBack();

            return $this->errorResponse(
                '카테고리 정렬 변경 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Get category tree structure.
     */
    public function tree(Request $request): JsonResponse
    {
        try {
            $query = Category::whereNull('parent_id')
                ->with(['children' => function ($query) {
                    $query->orderBy('sort_order');
                }])
                ->orderBy('sort_order');

            // 활성 카테고리만 조회
            if ($request->boolean('active_only')) {
                $query->where('is_active', true);
            }

            $categories = $query->get();

            return $this->successResponse(
                CategoryResource::collection($categories),
                '카테고리 트리를 조회했습니다.'
            );

        } catch (\Exception $e) {
            return $this->errorResponse(
                '카테고리 트리 조회 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Get category statistics.
     */
    public function getStatistics(Request $request): JsonResponse
    {
        try {
            // 권한 체크
            if (!$request->user()->hasRole(['admin', 'super_admin'])) {
                return $this->errorResponse(
                    '카테고리 통계를 조회할 권한이 없습니다.',
                    403
                );
            }

            $stats = [
                'total_categories' => Category::count(),
                'active_categories' => Category::where('is_active', true)->count(),
                'inactive_categories' => Category::where('is_active', false)->count(),
                'top_level_categories' => Category::whereNull('parent_id')->count(),
                'sub_categories' => Category::whereNotNull('parent_id')->count(),
                'categories_with_complaints' => Category::has('complaints')->count(),
                'categories_without_complaints' => Category::doesntHave('complaints')->count(),
                'most_used_categories' => Category::withCount('complaints')
                    ->orderByDesc('complaints_count')
                    ->limit(10)
                    ->get()
                    ->map(function ($category) {
                        return [
                            'id' => $category->id,
                            'name' => $category->name,
                            'complaints_count' => $category->complaints_count,
                        ];
                    }),
                'category_usage' => Category::select('categories.name', DB::raw('COUNT(complaints.id) as complaints_count'))
                    ->leftJoin('complaints', 'categories.id', '=', 'complaints.category_id')
                    ->groupBy('categories.id', 'categories.name')
                    ->orderByDesc('complaints_count')
                    ->get(),
            ];

            return $this->successResponse($stats, '카테고리 통계를 조회했습니다.');

        } catch (\Exception $e) {
            return $this->errorResponse(
                '카테고리 통계 조회 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Search categories.
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'query' => 'required|string|min:2|max:100',
            ]);

            $searchQuery = $request->input('query');

            $categories = Category::where('name', 'like', "%{$searchQuery}%")
                ->orWhere('description', 'like', "%{$searchQuery}%")
                ->where('is_active', true)
                ->orderBy('name')
                ->limit(20)
                ->get();

            return $this->successResponse(
                CategoryResource::collection($categories),
                '카테고리 검색을 완료했습니다.'
            );

        } catch (\Exception $e) {
            return $this->errorResponse(
                '카테고리 검색 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Get categories for select dropdown.
     */
    public function getSelectOptions(Request $request): JsonResponse
    {
        try {
            $categories = Category::where('is_active', true)
                ->orderBy('sort_order')
                ->get()
                ->map(function ($category) {
                    return [
                        'value' => $category->id,
                        'label' => $category->name,
                        'description' => $category->description,
                        'parent_id' => $category->parent_id,
                    ];
                });

            return $this->successResponse(
                $categories,
                '카테고리 선택 옵션을 조회했습니다.'
            );

        } catch (\Exception $e) {
            return $this->errorResponse(
                '카테고리 선택 옵션 조회 중 오류가 발생했습니다.',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Check if setting parent would create circular reference.
     */
    private function wouldCreateCircularReference(Category $category, int $parentId): bool
    {
        $parent = Category::find($parentId);
        
        while ($parent) {
            if ($parent->id === $category->id) {
                return true;
            }
            $parent = $parent->parent;
        }
        
        return false;
    }
}

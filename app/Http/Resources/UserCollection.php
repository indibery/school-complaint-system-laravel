<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class UserCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection->map(function ($user) {
                return UserResource::summary($user);
            }),
            'meta' => [
                'total' => $this->collection->count(),
                'active_count' => $this->collection->where('is_active', true)->count(),
                'inactive_count' => $this->collection->where('is_active', false)->count(),
                'role_counts' => $this->getRoleCounts(),
            ],
        ];
    }

    /**
     * 역할별 사용자 수 계산
     */
    protected function getRoleCounts(): array
    {
        $counts = [];
        $roles = ['admin', 'teacher', 'parent', 'security_staff', 'ops_staff'];
        
        foreach ($roles as $role) {
            $counts[$role] = $this->collection->where('role', $role)->count();
        }
        
        return $counts;
    }
}

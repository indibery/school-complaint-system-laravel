<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * 부서에 속한 사용자들
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * 부서에 할당된 민원들
     */
    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class);
    }

    /**
     * 활성 부서만 필터링
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'student_id',
        'name',
        'grade',
        'class',
        'class_number',
        'student_number',
        'gender',
        'parent_id',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array<string>
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * 부모 관계
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    /**
     * 학생과 관련된 민원들
     */
    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class, 'student_id');
    }

    /**
     * 활성 학생 여부
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * 전체 이름 (학년반번호 포함)
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->grade}학년 {$this->class}반 {$this->student_number}번 {$this->name}";
    }

    /**
     * 학급 정보
     */
    public function getClassInfoAttribute(): string
    {
        return "{$this->grade}학년 {$this->class}반";
    }

    /**
     * 활성 학생 스코프
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * 학년별 스코프
     */
    public function scopeByGrade($query, $grade)
    {
        return $query->where('grade', $grade);
    }

    /**
     * 반별 스코프
     */
    public function scopeByClass($query, $grade, $classNumber)
    {
        return $query->where('grade', $grade)->where('class', $classNumber);
    }

    /**
     * 학생 검색 스코프
     */
    public function scopeSearch($query, $keyword)
    {
        return $query->where(function($q) use ($keyword) {
            $q->where('name', 'like', "%{$keyword}%")
              ->orWhere('student_number', 'like', "%{$keyword}%");
        });
    }
}

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
        'class_number',
        'student_number',
        'gender',
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
     * 담임교사 (학년/반 정보로 찾기)
     */
    public function homeroomTeacher()
    {
        return User::where('grade', $this->grade)
                   ->where('class_number', $this->class_number)
                   ->where('role', 'teacher')
                   ->first();
    }

    /**
     * 학부모들 (다대다 관계)
     */
    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'parent_student_relationships', 'student_id', 'parent_id')
            ->withPivot(['relationship_type', 'is_primary'])
            ->withTimestamps();
    }

    /**
     * 주 학부모 (대표 학부모)
     */
    public function primaryParent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'primary_parent_id');
    }

    /**
     * 학생과 관련된 민원들 (학부모가 제기한 민원)
     */
    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class, 'related_student_id');
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
        return "{$this->grade}학년 {$this->class_number}반 {$this->student_number}번 {$this->name}";
    }

    /**
     * 학급 정보
     */
    public function getClassInfoAttribute(): string
    {
        return "{$this->grade}학년 {$this->class_number}반";
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
        return $query->where('grade', $grade)->where('class_number', $classNumber);
    }

    /**
     * 담임교사별 스코프 (학년/반으로 찾기)
     */
    public function scopeByHomeroom($query, $grade, $classNumber)
    {
        return $query->where('grade', $grade)->where('class_number', $classNumber);
    }

    /**
     * 학생 검색 스코프
     */
    public function scopeSearch($query, $keyword)
    {
        return $query->where(function($q) use ($keyword) {
            $q->where('name', 'like', "%{$keyword}%")
              ->orWhere('student_id', 'like', "%{$keyword}%");
        });
    }

    /**
     * 학생 생성 시 유효성 검증 규칙
     */
    public static function getValidationRules($isUpdate = false): array
    {
        return [
            'student_id' => 'required|string|max:20|unique:students,student_id',
            'name' => 'required|string|max:255|regex:/^[가-힣a-zA-Z\s]+$/',
            'grade' => 'required|integer|min:1|max:6',
            'class_number' => 'required|integer|min:1|max:20',
            'student_number' => 'required|integer|min:1|max:50',
            'gender' => 'required|in:male,female',
        ];
    }

    /**
     * 학생 생성 시 유효성 검증 메시지
     */
    public static function getValidationMessages(): array
    {
        return [
            'student_id.required' => '학번은 필수입니다.',
            'student_id.unique' => '이미 사용 중인 학번입니다.',
            'name.required' => '학생 이름은 필수입니다.',
            'name.regex' => '이름은 한글, 영문, 공백만 입력 가능합니다.',
            'grade.required' => '학년은 필수입니다.',
            'grade.min' => '학년은 1 이상이어야 합니다.',
            'grade.max' => '학년은 6 이하여야 합니다.',
            'class_number.required' => '반은 필수입니다.',
            'student_number.required' => '번호는 필수입니다.',
            'gender.required' => '성별은 필수입니다.',
        ];
    }
}
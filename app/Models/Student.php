<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use HasFactory, SoftDeletes;

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
        'homeroom_teacher_id',
        'birth_date',
        'gender',
        'phone',
        'address',
        'emergency_contact_name',
        'emergency_contact_phone',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'birth_date' => 'date',
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
        'birth_date',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * 담임교사
     */
    public function homeroomTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'homeroom_teacher_id');
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
     * 나이 계산
     */
    public function getAgeAttribute(): int
    {
        return $this->birth_date ? $this->birth_date->age : 0;
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
     * 담임교사별 스코프
     */
    public function scopeByHomeroom($query, $teacherId)
    {
        return $query->where('homeroom_teacher_id', $teacherId);
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
            'homeroom_teacher_id' => 'nullable|exists:users,id',
            'birth_date' => 'required|date|before:today',
            'gender' => 'required|in:male,female',
            'phone' => 'nullable|string|regex:/^01[016789]-?[0-9]{3,4}-?[0-9]{4}$/',
            'address' => 'nullable|string|max:500',
            'emergency_contact_name' => 'required|string|max:255',
            'emergency_contact_phone' => 'required|string|regex:/^01[016789]-?[0-9]{3,4}-?[0-9]{4}$/',
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
            'homeroom_teacher_id.exists' => '존재하지 않는 교사입니다.',
            'birth_date.required' => '생년월일은 필수입니다.',
            'birth_date.before' => '생년월일은 오늘 이전이어야 합니다.',
            'gender.required' => '성별은 필수입니다.',
            'phone.regex' => '올바른 휴대폰 번호 형식을 입력해주세요.',
            'emergency_contact_name.required' => '비상연락처 이름은 필수입니다.',
            'emergency_contact_phone.required' => '비상연락처 전화번호는 필수입니다.',
            'emergency_contact_phone.regex' => '올바른 전화번호 형식을 입력해주세요.',
        ];
    }
}

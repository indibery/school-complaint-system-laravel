<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'grade',           // 교사가 담당하는 학년
        'class_number',    // 교사가 담당하는 반
        'subject',         // 교사 담당 과목
        'department',      // 부서 (운영팀, 보안팀 등)
        'phone',           // 연락처
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * 역할별 상수
     */
    const ROLE_ADMIN = 'admin';
    const ROLE_TEACHER = 'teacher';
    const ROLE_PARENT = 'parent';
    const ROLE_SECURITY_STAFF = 'security_staff';  // 학교지킴이
    const ROLE_OPS_STAFF = 'ops_staff';            // 운영팀 사원

    /**
     * 접근 채널별 상수
     */
    const CHANNEL_PARENT_APP = 'parent_app';
    const CHANNEL_TEACHER_WEB = 'teacher_web';
    const CHANNEL_SECURITY_APP = 'security_app';
    const CHANNEL_OPS_WEB = 'ops_web';
    const CHANNEL_ADMIN_WEB = 'admin_web';

    /**
     * 담당 학생들 (교사인 경우)
     */
    public function homeroomStudents(): HasMany
    {
        return $this->hasMany(Student::class, null, null)
                    ->where('grade', $this->grade)
                    ->where('class_number', $this->class_number);
    }

    /**
     * 학부모-학생 관계 (학부모인 경우)
     */
    public function children(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'parent_student_relationships', 'parent_id', 'student_id')
            ->withPivot(['relationship_type', 'is_primary'])
            ->withTimestamps();
    }

    /**
     * 제기한 민원들 (학부모인 경우)
     */
    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class, 'complainant_id');
    }

    /**
     * 처리 담당 민원들 (교사/관리자/직원인 경우)
     */
    public function assignedComplaints(): HasMany
    {
        return $this->hasMany(Complaint::class, 'assigned_to');
    }

    /**
     * 활성 사용자 여부
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * 관리자 여부
     */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * 교사 여부
     */
    public function isTeacher(): bool
    {
        return $this->role === self::ROLE_TEACHER;
    }

    /**
     * 학부모 여부
     */
    public function isParent(): bool
    {
        return $this->role === self::ROLE_PARENT;
    }

    /**
     * 학교지킴이 여부
     */
    public function isSecurityStaff(): bool
    {
        return $this->role === self::ROLE_SECURITY_STAFF;
    }

    /**
     * 운영팀 사원 여부
     */
    public function isOpsStaff(): bool
    {
        return $this->role === self::ROLE_OPS_STAFF;
    }

    /**
     * 직원 여부 (교사 제외한 학교 직원)
     */
    public function isStaff(): bool
    {
        return in_array($this->role, [self::ROLE_SECURITY_STAFF, self::ROLE_OPS_STAFF]);
    }

    /**
     * 민원 처리 권한이 있는 사용자 여부
     */
    public function canHandleComplaints(): bool
    {
        return in_array($this->role, [
            self::ROLE_ADMIN, 
            self::ROLE_TEACHER, 
            self::ROLE_SECURITY_STAFF, 
            self::ROLE_OPS_STAFF
        ]);
    }

    /**
     * 접근 가능한 채널 반환
     */
    public function getAccessChannelAttribute(): string
    {
        return match($this->role) {
            self::ROLE_PARENT => self::CHANNEL_PARENT_APP,
            self::ROLE_TEACHER => self::CHANNEL_TEACHER_WEB,
            self::ROLE_SECURITY_STAFF => self::CHANNEL_SECURITY_APP,
            self::ROLE_OPS_STAFF => self::CHANNEL_OPS_WEB,
            self::ROLE_ADMIN => self::CHANNEL_ADMIN_WEB,
            default => '',
        };
    }

    /**
     * 담임교사 정보 (학년반 포함)
     */
    public function getHomeroomInfoAttribute(): ?string
    {
        if ($this->isTeacher() && $this->grade && $this->class_number) {
            return "{$this->grade}학년 {$this->class_number}반 담임";
        }
        return null;
    }

    /**
     * 사용자 표시명 (역할 포함)
     */
    public function getDisplayNameAttribute(): string
    {
        $roleNames = [
            self::ROLE_ADMIN => '관리자',
            self::ROLE_TEACHER => '교사',
            self::ROLE_PARENT => '학부모',
            self::ROLE_SECURITY_STAFF => '학교지킴이',
            self::ROLE_OPS_STAFF => '운영팀',
        ];

        $roleName = $roleNames[$this->role] ?? '';
        
        if ($this->isTeacher() && $this->homeroomInfo) {
            return "{$this->name} ({$this->homeroomInfo})";
        }

        return "{$this->name} ({$roleName})";
    }

    /**
     * 활성 사용자 스코프
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * 역할별 스코프
     */
    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    /**
     * 교사 스코프
     */
    public function scopeTeachers($query)
    {
        return $query->where('role', self::ROLE_TEACHER);
    }

    /**
     * 학부모 스코프
     */
    public function scopeParents($query)
    {
        return $query->where('role', self::ROLE_PARENT);
    }

    /**
     * 직원 스코프 (보안팀, 운영팀)
     */
    public function scopeStaff($query)
    {
        return $query->whereIn('role', [self::ROLE_SECURITY_STAFF, self::ROLE_OPS_STAFF]);
    }

    /**
     * 민원 처리 가능한 사용자 스코프
     */
    public function scopeComplaintHandlers($query)
    {
        return $query->whereIn('role', [
            self::ROLE_ADMIN, 
            self::ROLE_TEACHER, 
            self::ROLE_SECURITY_STAFF, 
            self::ROLE_OPS_STAFF
        ]);
    }

    /**
     * 담임교사 스코프 (학년반 정보가 있는 교사)
     */
    public function scopeHomeroomTeachers($query)
    {
        return $query->where('role', self::ROLE_TEACHER)
                    ->whereNotNull('grade')
                    ->whereNotNull('class_number');
    }

    /**
     * 특정 학년반 담임교사 스코프
     */
    public function scopeByHomeroom($query, $grade, $classNumber)
    {
        return $query->where('role', self::ROLE_TEACHER)
                    ->where('grade', $grade)
                    ->where('class_number', $classNumber);
    }

    /**
     * 부서별 스코프
     */
    public function scopeByDepartment($query, $department)
    {
        return $query->where('department', $department);
    }

    /**
     * 사용자 검색 스코프
     */
    public function scopeSearch($query, $keyword)
    {
        return $query->where(function($q) use ($keyword) {
            $q->where('name', 'like', "%{$keyword}%")
              ->orWhere('email', 'like', "%{$keyword}%")
              ->orWhere('department', 'like', "%{$keyword}%");
        });
    }

    /**
     * 사용자 생성 시 유효성 검증 규칙
     */
    public static function getValidationRules($isUpdate = false): array
    {
        return [
            'name' => 'required|string|max:255|regex:/^[가-힣a-zA-Z\s]+$/',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => $isUpdate ? 'nullable|string|min:8' : 'required|string|min:8',
            'role' => 'required|in:admin,teacher,parent,security_staff,ops_staff',
            'grade' => 'nullable|integer|min:1|max:6',
            'class_number' => 'nullable|integer|min:1|max:20',
            'subject' => 'nullable|string|max:100',
            'department' => 'nullable|string|max:100',
            'phone' => 'nullable|string|regex:/^01[016789]-?[0-9]{3,4}-?[0-9]{4}$/',
        ];
    }

    /**
     * 사용자 생성 시 유효성 검증 메시지
     */
    public static function getValidationMessages(): array
    {
        return [
            'name.required' => '이름은 필수입니다.',
            'name.regex' => '이름은 한글, 영문, 공백만 입력 가능합니다.',
            'email.required' => '이메일은 필수입니다.',
            'email.email' => '올바른 이메일 형식을 입력해주세요.',
            'email.unique' => '이미 사용 중인 이메일입니다.',
            'password.required' => '비밀번호는 필수입니다.',
            'password.min' => '비밀번호는 최소 8자 이상이어야 합니다.',
            'role.required' => '역할은 필수입니다.',
            'role.in' => '올바른 역할을 선택해주세요.',
            'grade.min' => '학년은 1 이상이어야 합니다.',
            'grade.max' => '학년은 6 이하여야 합니다.',
            'class_number.min' => '반은 1 이상이어야 합니다.',
            'class_number.max' => '반은 20 이하여야 합니다.',
            'phone.regex' => '올바른 휴대폰 번호 형식을 입력해주세요.',
        ];
    }
}    /**
     * Check if user has specific role(s).
     */
    public function hasRole($roles)
    {
        if (is_string($roles)) {
            return $this->role === $roles;
        }
        
        if (is_array($roles)) {
            return in_array($this->role, $roles);
        }
        
        return false;
    }

    /**
     * Check if user is admin.
     */
    public function isAdmin()
    {
        return $this->hasRole(['admin', 'super_admin']);
    }

    /**
     * Check if user is teacher.
     */
    public function isTeacher()
    {
        return $this->hasRole('teacher');
    }

    /**
     * Check if user is parent.
     */
    public function isParent()
    {
        return $this->hasRole('parent');
    }

    /**
     * Check if user is staff.
     */
    public function isStaff()
    {
        return $this->hasRole(['staff', 'security_staff', 'ops_staff']);
    }

    /**
     * Get user role label.
     */
    public function getRoleLabelAttribute()
    {
        $roles = [
            'admin' => '관리자',
            'super_admin' => '최고관리자',
            'department_head' => '부서장',
            'teacher' => '교사',
            'parent' => '학부모',
            'staff' => '교직원',
            'security_staff' => '보안직원',
            'ops_staff' => '운영직원',
        ];

        return $roles[$this->role] ?? '알 수 없음';
    }

    /**
     * Get user status label.
     */
    public function getStatusLabelAttribute()
    {
        return $this->status === 'active' ? '활성' : '비활성';
    }

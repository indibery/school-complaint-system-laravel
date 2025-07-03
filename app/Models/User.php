<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * 기본적으로 로드할 관계들
     *
     * @var array<string>
     */
    protected $with = ['department'];

    /**
     * 관계 로딩 시 카운트할 관계들
     *
     * @var array<string>
     */
    protected $withCount = ['complaints', 'assignedComplaints'];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'student_id',
        'employee_id',
        'department_id',
        'phone',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * 사용자가 속한 부서
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * 사용자가 작성한 민원들
     */
    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class);
    }

    /**
     * 사용자가 담당하는 민원들
     */
    public function assignedComplaints(): HasMany
    {
        return $this->hasMany(Complaint::class, 'assigned_to');
    }

    /**
     * 사용자가 작성한 댓글들
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * 사용자가 업로드한 첨부파일들
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class, 'uploaded_by');
    }

    /**
     * 사용자가 작성한 상태 로그들
     */
    public function statusLogs(): HasMany
    {
        return $this->hasMany(ComplaintStatusLog::class);
    }

    /**
     * 관리하는 부서 (부서장인 경우)
     */
    public function managedDepartment(): HasMany
    {
        return $this->hasMany(Department::class, 'manager_id');
    }

    /**
     * 사용자가 관련된 모든 민원 (작성자 + 담당자)
     */
    public function relatedComplaints(): HasMany
    {
        return $this->hasMany(Complaint::class, 'user_id')
            ->union($this->hasMany(Complaint::class, 'assigned_to'));
    }

    /**
     * 사용자가 처리한 상태 변경 로그들
     */
    public function processedStatusLogs(): HasMany
    {
        return $this->hasMany(ComplaintStatusLog::class, 'user_id');
    }

    /**
     * 관리자 권한 여부
     */
    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    /**
     * 교직원 권한 여부 (관리자 포함)
     */
    public function isStaff(): bool
    {
        return $this->role->isStaff();
    }

    /**
     * 학생 권한 여부
     */
    public function isStudent(): bool
    {
        return $this->role === UserRole::STUDENT;
    }

    /**
     * 활성 사용자 여부
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * 전체 이름 반환 (역할 포함)
     */
    public function getFullNameAttribute(): string
    {
        return $this->name . ' (' . $this->role->label() . ')';
    }

    /**
     * 학번 또는 사번 반환
     */
    public function getIdentifierAttribute(): string
    {
        return $this->student_id ?? $this->employee_id ?? '';
    }

    /**
     * 활성 사용자 스코프
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * 역할별 사용자 스코프
     */
    public function scopeByRole($query, UserRole $role)
    {
        return $query->where('role', $role);
    }

    /**
     * 부서별 사용자 스코프
     */
    public function scopeByDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    /**
     * 사용자 생성 시 유효성 검증 규칙
     */
    public static function getValidationRules($isUpdate = false): array
    {
        $rules = [
            'name' => 'required|string|max:255|regex:/^[가-힣a-zA-Z\s]+$/',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:' . implode(',', UserRole::getValues()),
            'department_id' => 'nullable|exists:departments,id',
            'phone' => 'nullable|string|regex:/^01[016789]-?[0-9]{3,4}-?[0-9]{4}$/',
            'student_id' => 'nullable|string|max:20|unique:users,student_id',
            'employee_id' => 'nullable|string|max:20|unique:users,employee_id',
        ];

        if ($isUpdate) {
            $rules['password'] = 'nullable|string|min:8|confirmed';
        }

        return $rules;
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
            'password.confirmed' => '비밀번호 확인이 일치하지 않습니다.',
            'role.required' => '사용자 역할은 필수입니다.',
            'role.in' => '올바른 사용자 역할을 선택해주세요.',
            'department_id.exists' => '존재하지 않는 부서입니다.',
            'phone.regex' => '올바른 휴대폰 번호 형식을 입력해주세요. (예: 010-1234-5678)',
            'student_id.unique' => '이미 사용 중인 학번입니다.',
            'employee_id.unique' => '이미 사용 중인 사번입니다.',
        ];
    }
}
}

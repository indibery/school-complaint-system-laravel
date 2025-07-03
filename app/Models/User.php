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
}
}

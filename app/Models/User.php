<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'department',
        'position',
        'role',
        'user_type',
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
            'is_active' => 'boolean',
        ];
    }
    
    /**
     * 사용자가 작성한 민원들
     */
    public function complaints()
    {
        return $this->hasMany(Complaint::class, 'complainant_id');
    }
    
    /**
     * 사용자에게 할당된 민원들
     */
    public function assignedComplaints()
    {
        return $this->hasMany(Complaint::class, 'assigned_to');
    }
    
    /**
     * 사용자가 작성한 댓글들
     */
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
    
    /**
     * 학부모의 자녀들 (학생 정보)
     */
    public function children()
    {
        return $this->hasMany(Student::class, 'parent_id');
    }
    
    /**
     * 부서 정보
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }
    
    /**
     * 알림을 받을 이메일 주소
     */
    public function routeNotificationForMail()
    {
        return $this->email;
    }
    
    /**
     * 활성 사용자만 필터링
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    /**
     * 담당자로 지정 가능한 사용자만 필터링 (관리자 또는 직원)
     */
    public function scopeAssignable($query)
    {
        return $query->active()->whereHas('roles', function ($q) {
            $q->whereIn('name', ['admin', 'staff']);
        });
    }
}

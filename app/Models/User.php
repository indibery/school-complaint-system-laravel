<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

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
        'access_channel',
        'student_id',
        'employee_id',
        'phone',
        'is_active',
        'status',
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
     * Get complaints submitted by this user (for parents)
     */
    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class, 'user_id');
    }

    /**
     * Get complaints assigned to this user (for staff)
     */
    public function assignedComplaints(): HasMany
    {
        return $this->hasMany(Complaint::class, 'assigned_to');
    }

    /**
     * Get students related to this user (for parents)
     */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'parent_id');
    }

    /**
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
        return $this->hasRole('admin');
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
        return $this->hasRole(['security_staff', 'ops_staff']);
    }

    /**
     * Get user role label.
     */
    public function getRoleLabelAttribute()
    {
        $roles = [
            'admin' => '관리자',
            'teacher' => '교사',
            'parent' => '학부모',
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
        return $this->is_active ? '활성' : '비활성';
    }
}

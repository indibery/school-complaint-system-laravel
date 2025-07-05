<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 역할 (관리자, 교사, 학부모, 학교지킴이, 운영팀)
            $table->enum('role', ['admin', 'teacher', 'parent', 'security_staff', 'ops_staff'])
                ->default('parent')
                ->after('email_verified_at')
                ->comment('사용자 역할: admin(관리자), teacher(교사), parent(학부모), security_staff(학교지킴이), ops_staff(운영팀)');
            
            // 접근 채널
            $table->enum('access_channel', ['admin_web', 'teacher_web', 'parent_app', 'security_app', 'ops_web'])
                ->nullable()
                ->after('role')
                ->comment('접근 채널: admin_web, teacher_web, parent_app, security_app, ops_web');
            
            // 학번 (학생 관련 - 학부모용)
            $table->string('student_id', 20)
                ->nullable()
                ->after('access_channel')
                ->comment('관련 학번 (학부모의 경우 자녀 학번)');
            
            // 사번 (교직원용)
            $table->string('employee_id', 20)
                ->nullable()
                ->after('student_id')
                ->comment('사번 (교사, 학교지킴이, 운영팀, 관리자용)');
            
            // 전화번호
            $table->string('phone', 20)
                ->nullable()
                ->after('employee_id')
                ->comment('전화번호');
            
            // 상태 (활성/비활성)
            $table->boolean('is_active')
                ->default(true)
                ->after('phone')
                ->comment('계정 활성 상태');
            
            // 인덱스 추가
            $table->index('role');
            $table->index('access_channel');
            $table->unique('student_id');
            $table->unique('employee_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 인덱스 제거
            $table->dropIndex(['role']);
            $table->dropIndex(['access_channel']);
            $table->dropUnique(['student_id']);
            $table->dropUnique(['employee_id']);
            $table->dropIndex(['is_active']);
            
            // 컬럼 제거
            $table->dropColumn([
                'role',
                'access_channel',
                'student_id',
                'employee_id',
                'phone',
                'is_active'
            ]);
        });
    }
};

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
        Schema::create('complaints', function (Blueprint $table) {
            $table->id()->comment('민원 ID');
            
            // 민원 제기자 (학부모)
            $table->unsignedBigInteger('user_id')
                ->comment('민원 제기자 ID (학부모)');
            
            // 관련 학생 (학부모의 자녀)
            $table->unsignedBigInteger('student_id')
                ->comment('관련 학생 ID');
            
            // 민원 카테고리
            $table->unsignedBigInteger('category_id')
                ->comment('민원 카테고리 ID');
            
            // 민원 제목
            $table->string('title', 200)
                ->comment('민원 제목');
            
            // 민원 내용
            $table->text('content')
                ->comment('민원 내용');
            
            // 민원 상태
            $table->enum('status', [
                'submitted',    // 접수됨
                'in_review',    // 검토중
                'in_progress',  // 처리중
                'resolved',     // 해결됨
                'closed',       // 종료됨
                'rejected'      // 반려됨
            ])->default('submitted')
                ->comment('민원 상태');
            
            // 우선순위
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])
                ->default('normal')
                ->comment('우선순위');
            
            // 담당자 (배정된 처리자)
            $table->unsignedBigInteger('assigned_to')
                ->nullable()
                ->comment('담당자 ID');
            
            // 예상 완료일
            $table->datetime('expected_completion_at')
                ->nullable()
                ->comment('예상 완료일');
            
            // 실제 완료일
            $table->datetime('completed_at')
                ->nullable()
                ->comment('실제 완료일');
            
            // 공개 여부 (다른 사용자들이 볼 수 있는지)
            $table->boolean('is_public')
                ->default(false)
                ->comment('공개 여부');
            
            // 만족도 평가 (1-5점)
            $table->tinyInteger('satisfaction_rating')
                ->nullable()
                ->comment('만족도 평가 (1-5점)');
            
            // 만족도 평가 코멘트
            $table->text('satisfaction_comment')
                ->nullable()
                ->comment('만족도 평가 코멘트');
            
            $table->timestamps();
            
            // 외래키 제약조건
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
            
            $table->foreign('student_id')
                ->references('id')
                ->on('students')
                ->onDelete('cascade');
            
            $table->foreign('category_id')
                ->references('id')
                ->on('categories')
                ->onDelete('restrict');
            
            $table->foreign('assigned_to')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
            
            // 인덱스
            $table->index('user_id');
            $table->index('student_id');
            $table->index('category_id');
            $table->index('status');
            $table->index('priority');
            $table->index('assigned_to');
            $table->index('is_public');
            $table->index('created_at');
            $table->index(['status', 'priority']); // 복합 인덱스
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};

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
        Schema::create('students', function (Blueprint $table) {
            $table->id()->comment('학생 ID');
            
            // 학부모 ID
            $table->unsignedBigInteger('parent_id')
                ->comment('학부모 ID (users 테이블 참조)');
            
            // 학생 이름
            $table->string('name', 50)
                ->comment('학생 이름');
            
            // 학번
            $table->string('student_number', 20)
                ->unique()
                ->comment('학번');
            
            // 학년
            $table->tinyInteger('grade')
                ->comment('학년 (1-6)');
            
            // 반
            $table->tinyInteger('class')
                ->comment('반');
            
            // 활성 상태 (졸업, 전학 등으로 비활성화 가능)
            $table->boolean('is_active')
                ->default(true)
                ->comment('활성 상태');
            
            $table->timestamps();
            
            // 외래키 제약조건
            $table->foreign('parent_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
            
            // 인덱스
            $table->index('parent_id');
            $table->index(['grade', 'class']); // 학년반 검색용
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};

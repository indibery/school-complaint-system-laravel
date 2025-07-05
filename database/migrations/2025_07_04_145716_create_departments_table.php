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
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique()->comment('부서명');
            $table->string('code', 20)->unique()->comment('부서 코드');
            $table->text('description')->nullable()->comment('부서 설명');
            $table->unsignedBigInteger('head_id')->nullable()->comment('부서장 ID');
            $table->enum('status', ['active', 'inactive'])->default('active')->comment('부서 상태');
            $table->string('contact_email')->nullable()->comment('연락처 이메일');
            $table->string('contact_phone', 20)->nullable()->comment('연락처 전화번호');
            $table->string('location')->nullable()->comment('위치');
            $table->decimal('budget', 15, 2)->nullable()->comment('예산');
            $table->date('established_date')->nullable()->comment('설립일');
            $table->json('metadata')->nullable()->comment('메타데이터');
            $table->timestamps();
            $table->softDeletes();
            
            // 외래키 제약 조건
            $table->foreign('head_id')->references('id')->on('users')->onDelete('set null');
            
            // 인덱스
            $table->index('name');
            $table->index('code');
            $table->index('status');
            $table->index('head_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};

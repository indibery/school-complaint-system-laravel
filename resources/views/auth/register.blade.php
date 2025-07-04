@extends('layouts.app')

@section('title', '회원가입 - 학교 민원 관리 시스템')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center align-items-center min-vh-100 py-4">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-lg border-0">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <i class="bi bi-person-plus text-primary" style="font-size: 3rem;"></i>
                        <h2 class="mt-3 mb-2">회원가입</h2>
                        <p class="text-muted">새 계정을 생성하세요</p>
                    </div>
                    
                    <form method="POST" action="{{ route('register.submit') }}" id="registerForm">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">이름 <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">
                                    <i class="bi bi-person"></i>
                                </span>
                                <input type="text" 
                                       class="form-control @error('name') is-invalid @enderror" 
                                       id="name" 
                                       name="name" 
                                       value="{{ old('name') }}" 
                                       placeholder="이름을 입력하세요"
                                       required 
                                       autofocus>
                            </div>
                            @error('name')
                                <div class="invalid-feedback d-block">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">이메일 <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">
                                    <i class="bi bi-envelope"></i>
                                </span>
                                <input type="email" 
                                       class="form-control @error('email') is-invalid @enderror" 
                                       id="email" 
                                       name="email" 
                                       value="{{ old('email') }}" 
                                       placeholder="이메일을 입력하세요"
                                       required>
                            </div>
                            @error('email')
                                <div class="invalid-feedback d-block">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label">연락처</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">
                                    <i class="bi bi-telephone"></i>
                                </span>
                                <input type="tel" 
                                       class="form-control @error('phone') is-invalid @enderror" 
                                       id="phone" 
                                       name="phone" 
                                       value="{{ old('phone') }}" 
                                       placeholder="연락처를 입력하세요 (선택사항)">
                            </div>
                            @error('phone')
                                <div class="invalid-feedback d-block">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="role" class="form-label">역할 <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">
                                    <i class="bi bi-person-badge"></i>
                                </span>
                                <select class="form-select @error('role') is-invalid @enderror" 
                                        id="role" 
                                        name="role" 
                                        required>
                                    <option value="">역할을 선택하세요</option>
                                    <option value="parent" {{ old('role') == 'parent' ? 'selected' : '' }}>학부모</option>
                                    <option value="teacher" {{ old('role') == 'teacher' ? 'selected' : '' }}>교사</option>
                                    <option value="staff" {{ old('role') == 'staff' ? 'selected' : '' }}>교직원</option>
                                </select>
                            </div>
                            @error('role')
                                <div class="invalid-feedback d-block">
                                    {{ $message }}
                                </div>
                            @enderror
                            <div class="form-text">
                                <small class="text-muted">
                                    <i class="bi bi-info-circle me-1"></i>
                                    관리자 계정은 시스템 관리자가 별도로 생성합니다.
                                </small>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">비밀번호 <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">
                                    <i class="bi bi-lock"></i>
                                </span>
                                <input type="password" 
                                       class="form-control @error('password') is-invalid @enderror" 
                                       id="password" 
                                       name="password" 
                                       placeholder="비밀번호를 입력하세요 (최소 8자)"
                                       required>
                                <button type="button" class="btn btn-outline-secondary" id="togglePassword">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            @error('password')
                                <div class="invalid-feedback d-block">
                                    {{ $message }}
                                </div>
                            @enderror
                            <div class="form-text">
                                <small class="text-muted">최소 8자 이상, 영문/숫자/특수문자 조합 권장</small>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="password_confirmation" class="form-label">비밀번호 확인 <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">
                                    <i class="bi bi-lock-fill"></i>
                                </span>
                                <input type="password" 
                                       class="form-control @error('password_confirmation') is-invalid @enderror" 
                                       id="password_confirmation" 
                                       name="password_confirmation" 
                                       placeholder="비밀번호를 다시 입력하세요"
                                       required>
                                <button type="button" class="btn btn-outline-secondary" id="togglePasswordConfirm">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            @error('password_confirmation')
                                <div class="invalid-feedback d-block">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        
                        <div class="d-grid mb-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-person-plus me-2"></i>
                                계정 생성
                            </button>
                        </div>
                        
                        <div class="text-center">
                            <p class="mb-0">
                                이미 계정이 있으신가요? 
                                <a href="{{ route('login') }}" class="text-decoration-none">로그인</a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- 시스템 정보 -->
            <div class="text-center mt-4">
                <small class="text-muted">
                    © {{ date('Y') }} 학교 민원 관리 시스템. All rights reserved.
                </small>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    body {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
    }
    
    .card {
        border-radius: 20px;
        backdrop-filter: blur(10px);
        background: rgba(255, 255, 255, 0.95);
    }
    
    .input-group-text {
        border-right: none;
        background-color: #f8f9fa !important;
        border-radius: 10px 0 0 10px;
    }
    
    .form-control, .form-select {
        border-left: none;
        border-radius: 0 10px 10px 0;
        padding-left: 0;
    }
    
    .form-control:focus, .form-select:focus {
        box-shadow: none;
        border-color: #ced4da;
    }
    
    .input-group:focus-within .input-group-text {
        border-color: var(--primary-color);
    }
    
    .input-group:focus-within .form-control,
    .input-group:focus-within .form-select {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.2rem rgba(44, 90, 160, 0.25);
    }
    
    .btn-primary {
        background: linear-gradient(135deg, var(--primary-color), #1e3d72);
        border: none;
        border-radius: 10px;
        padding: 12px;
        font-weight: 600;
    }
    
    .btn-primary:hover {
        background: linear-gradient(135deg, #1e3d72, var(--primary-color));
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(44, 90, 160, 0.3);
    }
    
    .btn-outline-secondary {
        border-left: none;
        border-radius: 0 10px 10px 0;
    }
    
    .password-strength {
        height: 5px;
        border-radius: 3px;
        margin-top: 5px;
        transition: all 0.3s ease;
    }
    
    .strength-weak { background-color: #dc3545; width: 25%; }
    .strength-fair { background-color: #ffc107; width: 50%; }
    .strength-good { background-color: #17a2b8; width: 75%; }
    .strength-strong { background-color: #28a745; width: 100%; }
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    // 비밀번호 표시/숨기기
    $('#togglePassword').click(function() {
        const passwordField = $('#password');
        const icon = $(this).find('i');
        
        if (passwordField.attr('type') === 'password') {
            passwordField.attr('type', 'text');
            icon.removeClass('bi-eye').addClass('bi-eye-slash');
        } else {
            passwordField.attr('type', 'password');
            icon.removeClass('bi-eye-slash').addClass('bi-eye');
        }
    });
    
    $('#togglePasswordConfirm').click(function() {
        const passwordField = $('#password_confirmation');
        const icon = $(this).find('i');
        
        if (passwordField.attr('type') === 'password') {
            passwordField.attr('type', 'text');
            icon.removeClass('bi-eye').addClass('bi-eye-slash');
        } else {
            passwordField.attr('type', 'password');
            icon.removeClass('bi-eye-slash').addClass('bi-eye');
        }
    });
    
    // 비밀번호 강도 체크
    $('#password').on('input', function() {
        const password = $(this).val();
        const strength = checkPasswordStrength(password);
        updatePasswordStrengthIndicator(strength);
    });
    
    // 비밀번호 확인 체크
    $('#password_confirmation').on('input', function() {
        const password = $('#password').val();
        const confirmation = $(this).val();
        
        if (confirmation && password !== confirmation) {
            $(this).addClass('is-invalid');
            if (!$(this).siblings('.invalid-feedback').length) {
                $(this).after('<div class="invalid-feedback">비밀번호가 일치하지 않습니다.</div>');
            }
        } else {
            $(this).removeClass('is-invalid');
            $(this).siblings('.invalid-feedback').remove();
        }
    });
    
    // 폼 제출 시 버튼 비활성화
    $('#registerForm').submit(function() {
        $(this).find('button[type="submit"]').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>계정 생성 중...');
    });
    
    function checkPasswordStrength(password) {
        let score = 0;
        
        if (password.length >= 8) score++;
        if (/[a-z]/.test(password)) score++;
        if (/[A-Z]/.test(password)) score++;
        if (/[0-9]/.test(password)) score++;
        if (/[^A-Za-z0-9]/.test(password)) score++;
        
        return score;
    }
    
    function updatePasswordStrengthIndicator(strength) {
        let strengthBar = $('#password').siblings('.password-strength');
        
        if (!strengthBar.length) {
            $('#password').after('<div class="password-strength"></div>');
            strengthBar = $('#password').siblings('.password-strength');
        }
        
        strengthBar.removeClass('strength-weak strength-fair strength-good strength-strong');
        
        if (strength <= 1) {
            strengthBar.addClass('strength-weak');
        } else if (strength <= 2) {
            strengthBar.addClass('strength-fair');
        } else if (strength <= 3) {
            strengthBar.addClass('strength-good');
        } else {
            strengthBar.addClass('strength-strong');
        }
    }
});
</script>
@endpush

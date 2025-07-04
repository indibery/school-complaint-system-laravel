@extends('layouts.app')

@section('title', '로그인 - 학교 민원 관리 시스템')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center align-items-center min-vh-100">
        <div class="col-md-5 col-lg-4">
            <div class="card shadow-lg border-0">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <i class="bi bi-shield-check text-primary" style="font-size: 3rem;"></i>
                        <h2 class="mt-3 mb-2">학교 민원 관리</h2>
                        <p class="text-muted">시스템에 로그인하세요</p>
                    </div>
                    
                    <form method="POST" action="{{ route('login.submit') }}" id="loginForm">
                        @csrf
                        
                        <div class="mb-4">
                            <label for="email" class="form-label">이메일</label>
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
                                       required 
                                       autofocus>
                            </div>
                            @error('email')
                                <div class="invalid-feedback d-block">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        
                        <div class="mb-4">
                            <label for="password" class="form-label">비밀번호</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">
                                    <i class="bi bi-lock"></i>
                                </span>
                                <input type="password" 
                                       class="form-control @error('password') is-invalid @enderror" 
                                       id="password" 
                                       name="password" 
                                       placeholder="비밀번호를 입력하세요"
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
                        </div>
                        
                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
                                <label class="form-check-label" for="remember">
                                    로그인 상태 유지
                                </label>
                            </div>
                        </div>
                        
                        <div class="d-grid mb-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-box-arrow-in-right me-2"></i>
                                로그인
                            </button>
                        </div>
                        
                        <div class="text-center">
                            <p class="mb-0">
                                계정이 없으신가요? 
                                <a href="{{ route('register') }}" class="text-decoration-none">회원가입</a>
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
    
    .form-control {
        border-left: none;
        border-radius: 0 10px 10px 0;
        padding-left: 0;
    }
    
    .form-control:focus {
        box-shadow: none;
        border-color: #ced4da;
    }
    
    .input-group:focus-within .input-group-text {
        border-color: var(--primary-color);
    }
    
    .input-group:focus-within .form-control {
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
    
    // 폼 제출 시 버튼 비활성화
    $('#loginForm').submit(function() {
        $(this).find('button[type="submit"]').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>로그인 중...');
    });
    
    // 엔터키로 로그인
    $('.form-control').keypress(function(e) {
        if (e.which === 13) {
            $('#loginForm').submit();
        }
    });
});
</script>
@endpush

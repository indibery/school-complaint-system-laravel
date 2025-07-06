/**
 * 공통 JavaScript 함수 모음
 * 전체 프로젝트에서 재사용 가능한 유틸리티 함수들
 */

// 알림 표시 함수
function showNotification(message, type = 'info') {
    const alertClass = type === 'success' ? 'alert-success' : 
                      type === 'error' ? 'alert-danger' : 
                      type === 'warning' ? 'alert-warning' : 'alert-info';
    
    const alertHTML = `
        <div class="alert ${alertClass} alert-dismissible fade show notification-alert" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    // 기존 알림 제거
    document.querySelectorAll('.notification-alert').forEach(alert => alert.remove());
    
    // 새 알림 추가
    document.body.insertAdjacentHTML('beforeend', alertHTML);
    
    // 5초 후 자동 제거
    setTimeout(() => {
        const alert = document.querySelector('.notification-alert');
        if (alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }
    }, 5000);
}

// 디바운스 함수
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// 쓰로틀 함수
function throttle(func, limit) {
    let inThrottle;
    return function(...args) {
        if (!inThrottle) {
            func.apply(this, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// CSRF 토큰 가져오기
function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

// API 요청 래퍼
async function apiRequest(url, options = {}) {
    const defaultOptions = {
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': getCsrfToken()
        }
    };
    
    const mergedOptions = {
        ...defaultOptions,
        ...options,
        headers: {
            ...defaultOptions.headers,
            ...options.headers
        }
    };
    
    try {
        const response = await fetch(url, mergedOptions);
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.message || '요청 처리 중 오류가 발생했습니다.');
        }
        
        return data;
    } catch (error) {
        console.error('API Request Error:', error);
        throw error;
    }
}

// 날짜 포맷 함수
function formatDate(dateString, format = 'default') {
    const date = new Date(dateString);
    
    switch (format) {
        case 'short':
            return date.toLocaleDateString('ko-KR');
        case 'long':
            return date.toLocaleDateString('ko-KR', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                weekday: 'long'
            });
        case 'datetime':
            return date.toLocaleString('ko-KR');
        case 'relative':
            return getRelativeTime(date);
        default:
            return date.toLocaleDateString('ko-KR', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit'
            });
    }
}

// 상대 시간 표시
function getRelativeTime(date) {
    const now = new Date();
    const diff = now - date;
    const seconds = Math.floor(diff / 1000);
    const minutes = Math.floor(seconds / 60);
    const hours = Math.floor(minutes / 60);
    const days = Math.floor(hours / 24);
    
    if (seconds < 60) return '방금 전';
    if (minutes < 60) return `${minutes}분 전`;
    if (hours < 24) return `${hours}시간 전`;
    if (days < 7) return `${days}일 전`;
    if (days < 30) return `${Math.floor(days / 7)}주 전`;
    if (days < 365) return `${Math.floor(days / 30)}개월 전`;
    return `${Math.floor(days / 365)}년 전`;
}

// 파일 크기 포맷
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// URL 파라미터 가져오기
function getUrlParams() {
    const params = new URLSearchParams(window.location.search);
    const result = {};
    
    for (const [key, value] of params) {
        result[key] = value;
    }
    
    return result;
}

// URL 파라미터 설정
function setUrlParams(params) {
    const url = new URL(window.location);
    
    Object.keys(params).forEach(key => {
        if (params[key]) {
            url.searchParams.set(key, params[key]);
        } else {
            url.searchParams.delete(key);
        }
    });
    
    window.history.pushState({}, '', url);
}

// 로딩 오버레이 표시
function showLoading(target = null) {
    const loadingHTML = `
        <div class="loading-overlay">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;
    
    if (target) {
        target.style.position = 'relative';
        target.insertAdjacentHTML('beforeend', loadingHTML);
    } else {
        document.body.insertAdjacentHTML('beforeend', loadingHTML);
    }
}

// 로딩 오버레이 숨기기
function hideLoading(target = null) {
    const selector = target ? target.querySelector('.loading-overlay') : document.querySelector('.loading-overlay');
    if (selector) {
        selector.remove();
    }
}

// 확인 다이얼로그
function confirmDialog(message, title = '확인') {
    return new Promise((resolve) => {
        const modalHTML = `
            <div class="modal fade" id="confirmModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">${title}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p>${message}</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                            <button type="button" class="btn btn-primary" id="confirmBtn">확인</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        
        const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
        const confirmBtn = document.getElementById('confirmBtn');
        const modalElement = document.getElementById('confirmModal');
        
        confirmBtn.addEventListener('click', () => {
            modal.hide();
            resolve(true);
        });
        
        modalElement.addEventListener('hidden.bs.modal', () => {
            modalElement.remove();
            resolve(false);
        });
        
        modal.show();
    });
}

// 페이지네이션 생성
function createPagination(currentPage, totalPages, onPageChange) {
    const pagination = document.createElement('nav');
    pagination.innerHTML = `<ul class="pagination justify-content-center"></ul>`;
    const paginationList = pagination.querySelector('ul');
    
    // 이전 버튼
    const prevItem = document.createElement('li');
    prevItem.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
    prevItem.innerHTML = `<a class="page-link" href="#" data-page="${currentPage - 1}">이전</a>`;
    paginationList.appendChild(prevItem);
    
    // 페이지 번호
    const maxVisible = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
    let endPage = Math.min(totalPages, startPage + maxVisible - 1);
    
    if (endPage - startPage < maxVisible - 1) {
        startPage = Math.max(1, endPage - maxVisible + 1);
    }
    
    if (startPage > 1) {
        const firstItem = document.createElement('li');
        firstItem.className = 'page-item';
        firstItem.innerHTML = `<a class="page-link" href="#" data-page="1">1</a>`;
        paginationList.appendChild(firstItem);
        
        if (startPage > 2) {
            const dots = document.createElement('li');
            dots.className = 'page-item disabled';
            dots.innerHTML = `<span class="page-link">...</span>`;
            paginationList.appendChild(dots);
        }
    }
    
    for (let i = startPage; i <= endPage; i++) {
        const pageItem = document.createElement('li');
        pageItem.className = `page-item ${i === currentPage ? 'active' : ''}`;
        pageItem.innerHTML = `<a class="page-link" href="#" data-page="${i}">${i}</a>`;
        paginationList.appendChild(pageItem);
    }
    
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            const dots = document.createElement('li');
            dots.className = 'page-item disabled';
            dots.innerHTML = `<span class="page-link">...</span>`;
            paginationList.appendChild(dots);
        }
        
        const lastItem = document.createElement('li');
        lastItem.className = 'page-item';
        lastItem.innerHTML = `<a class="page-link" href="#" data-page="${totalPages}">${totalPages}</a>`;
        paginationList.appendChild(lastItem);
    }
    
    // 다음 버튼
    const nextItem = document.createElement('li');
    nextItem.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
    nextItem.innerHTML = `<a class="page-link" href="#" data-page="${currentPage + 1}">다음</a>`;
    paginationList.appendChild(nextItem);
    
    // 클릭 이벤트
    pagination.addEventListener('click', (e) => {
        e.preventDefault();
        if (e.target.classList.contains('page-link') && !e.target.parentElement.classList.contains('disabled')) {
            const page = parseInt(e.target.dataset.page);
            if (page && onPageChange) {
                onPageChange(page);
            }
        }
    });
    
    return pagination;
}

// 폼 유효성 검사
function validateForm(form) {
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        } else {
            field.classList.remove('is-invalid');
        }
    });
    
    return isValid;
}

// 텍스트 잘라내기
function truncateText(text, maxLength = 100) {
    if (text.length <= maxLength) return text;
    return text.substring(0, maxLength) + '...';
}

// 배열 청크 분할
function chunkArray(array, size) {
    const chunks = [];
    for (let i = 0; i < array.length; i += size) {
        chunks.push(array.slice(i, i + size));
    }
    return chunks;
}

// 로컬 스토리지 래퍼
const storage = {
    get(key, defaultValue = null) {
        try {
            const item = localStorage.getItem(key);
            return item ? JSON.parse(item) : defaultValue;
        } catch (error) {
            console.error('Storage get error:', error);
            return defaultValue;
        }
    },
    
    set(key, value) {
        try {
            localStorage.setItem(key, JSON.stringify(value));
            return true;
        } catch (error) {
            console.error('Storage set error:', error);
            return false;
        }
    },
    
    remove(key) {
        try {
            localStorage.removeItem(key);
            return true;
        } catch (error) {
            console.error('Storage remove error:', error);
            return false;
        }
    },
    
    clear() {
        try {
            localStorage.clear();
            return true;
        } catch (error) {
            console.error('Storage clear error:', error);
            return false;
        }
    }
};

// Export 함수들 (window 객체에 추가)
window.showNotification = showNotification;
window.debounce = debounce;
window.throttle = throttle;
window.getCsrfToken = getCsrfToken;
window.apiRequest = apiRequest;
window.formatDate = formatDate;
window.getRelativeTime = getRelativeTime;
window.formatFileSize = formatFileSize;
window.getUrlParams = getUrlParams;
window.setUrlParams = setUrlParams;
window.showLoading = showLoading;
window.hideLoading = hideLoading;
window.confirmDialog = confirmDialog;
window.createPagination = createPagination;
window.validateForm = validateForm;
window.truncateText = truncateText;
window.chunkArray = chunkArray;
window.storage = storage;

/**
 * 알림 시스템 관리 JavaScript
 * 실시간 알림 업데이트, 알림 UI 관리
 */

class NotificationManager {
    constructor(options = {}) {
        this.options = {
            updateInterval: 30000, // 30초
            soundEnabled: true,
            desktopNotificationEnabled: false,
            apiEndpoint: '/api/v1/notifications',
            ...options
        };
        
        this.unreadCount = 0;
        this.notifications = [];
        this.updateInterval = null;
        
        this.init();
    }
    
    init() {
        this.checkDesktopNotificationPermission();
        this.loadNotifications();
        this.startRealTimeUpdate();
        this.initializeEventListeners();
    }
    
    // 데스크톱 알림 권한 확인
    checkDesktopNotificationPermission() {
        if ('Notification' in window && this.options.desktopNotificationEnabled) {
            if (Notification.permission === 'default') {
                Notification.requestPermission();
            }
        }
    }
    
    // 알림 로드
    async loadNotifications() {
        try {
            const response = await apiRequest(`${this.options.apiEndpoint}?unread=true&per_page=10`);
            
            if (response.success) {
                this.notifications = response.data.data;
                this.unreadCount = response.unread_count;
                this.updateUI();
            }
        } catch (error) {
            console.error('알림 로드 실패:', error);
        }
    }
    
    // 실시간 업데이트 시작
    startRealTimeUpdate() {
        this.updateInterval = setInterval(() => {
            this.checkNewNotifications();
        }, this.options.updateInterval);
    }
    
    // 새 알림 확인
    async checkNewNotifications() {
        try {
            const response = await apiRequest(`${this.options.apiEndpoint}/unread-count`);
            
            if (response.success) {
                const newCount = response.data.count;
                
                if (newCount > this.unreadCount) {
                    // 새 알림이 있을 때
                    this.playNotificationSound();
                    this.loadNotifications();
                    
                    if (this.options.desktopNotificationEnabled) {
                        this.showDesktopNotification('새로운 알림이 있습니다.');
                    }
                }
                
                this.unreadCount = newCount;
                this.updateBadge();
            }
        } catch (error) {
            console.error('알림 확인 실패:', error);
        }
    }
    
    // UI 업데이트
    updateUI() {
        this.updateBadge();
        this.updateDropdown();
    }
    
    // 배지 업데이트
    updateBadge() {
        const badge = document.getElementById('notificationBadge');
        if (badge) {
            if (this.unreadCount > 0) {
                badge.textContent = this.unreadCount > 99 ? '99+' : this.unreadCount;
                badge.style.display = 'inline-block';
            } else {
                badge.style.display = 'none';
            }
        }
    }
    
    // 드롭다운 업데이트
    updateDropdown() {
        const container = document.getElementById('notificationDropdown');
        if (!container) return;
        
        if (this.notifications.length === 0) {
            container.innerHTML = `
                <div class="notification-empty">
                    <i class="bi bi-bell-slash"></i>
                    <p>새로운 알림이 없습니다.</p>
                </div>
            `;
            return;
        }
        
        const notificationHTML = this.notifications.map(notification => {
            const data = notification.data;
            return `
                <div class="notification-item ${!notification.read_at ? 'unread' : ''}" 
                     data-id="${notification.id}"
                     onclick="notificationManager.handleNotificationClick('${notification.id}')">
                    <div class="notification-icon">
                        ${this.getNotificationIcon(data.type)}
                    </div>
                    <div class="notification-content">
                        <p class="notification-message">${data.message}</p>
                        <small class="notification-time">${formatDate(notification.created_at, 'relative')}</small>
                    </div>
                    ${!notification.read_at ? '<span class="notification-unread-dot"></span>' : ''}
                </div>
            `;
        }).join('');
        
        container.innerHTML = notificationHTML;
    }
    
    // 알림 아이콘 가져오기
    getNotificationIcon(type) {
        const icons = {
            'complaint_created': '<i class="bi bi-file-earmark-plus text-primary"></i>',
            'complaint_assigned': '<i class="bi bi-person-check text-info"></i>',
            'complaint_commented': '<i class="bi bi-chat-dots text-success"></i>',
            'complaint_status_changed': '<i class="bi bi-arrow-repeat text-warning"></i>',
            'complaint_deadline': '<i class="bi bi-clock-history text-danger"></i>'
        };
        
        return icons[type] || '<i class="bi bi-bell"></i>';
    }
    
    // 알림 클릭 처리
    async handleNotificationClick(notificationId) {
        try {
            // 읽음 처리
            await this.markAsRead(notificationId);
            
            // 해당 페이지로 이동
            const notification = this.notifications.find(n => n.id === notificationId);
            if (notification && notification.data.url) {
                window.location.href = notification.data.url;
            }
        } catch (error) {
            console.error('알림 클릭 처리 실패:', error);
        }
    }
    
    // 알림 읽음 처리
    async markAsRead(notificationId) {
        try {
            const response = await apiRequest(`${this.options.apiEndpoint}/${notificationId}/read`, {
                method: 'PUT'
            });
            
            if (response.success) {
                // UI 업데이트
                const notification = this.notifications.find(n => n.id === notificationId);
                if (notification) {
                    notification.read_at = new Date().toISOString();
                }
                
                this.unreadCount = Math.max(0, this.unreadCount - 1);
                this.updateUI();
            }
        } catch (error) {
            console.error('읽음 처리 실패:', error);
        }
    }
    
    // 모든 알림 읽음 처리
    async markAllAsRead() {
        try {
            const response = await apiRequest(`${this.options.apiEndpoint}/read-all`, {
                method: 'PUT'
            });
            
            if (response.success) {
                this.notifications.forEach(n => {
                    n.read_at = new Date().toISOString();
                });
                
                this.unreadCount = 0;
                this.updateUI();
                
                showNotification('모든 알림을 읽음으로 표시했습니다.', 'success');
            }
        } catch (error) {
            showNotification('읽음 처리에 실패했습니다.', 'error');
        }
    }
    
    // 알림 삭제
    async deleteNotification(notificationId) {
        if (!await confirmDialog('이 알림을 삭제하시겠습니까?')) {
            return;
        }
        
        try {
            const response = await apiRequest(`${this.options.apiEndpoint}/${notificationId}`, {
                method: 'DELETE'
            });
            
            if (response.success) {
                this.notifications = this.notifications.filter(n => n.id !== notificationId);
                this.updateUI();
                
                showNotification('알림이 삭제되었습니다.', 'success');
            }
        } catch (error) {
            showNotification('알림 삭제에 실패했습니다.', 'error');
        }
    }
    
    // 읽은 알림 모두 삭제
    async clearReadNotifications() {
        if (!await confirmDialog('읽은 알림을 모두 삭제하시겠습니까?')) {
            return;
        }
        
        try {
            const response = await apiRequest(`${this.options.apiEndpoint}/clear-read`, {
                method: 'DELETE'
            });
            
            if (response.success) {
                this.notifications = this.notifications.filter(n => !n.read_at);
                this.updateUI();
                
                showNotification('읽은 알림이 모두 삭제되었습니다.', 'success');
            }
        } catch (error) {
            showNotification('알림 삭제에 실패했습니다.', 'error');
        }
    }
    
    // 알림음 재생
    playNotificationSound() {
        if (this.options.soundEnabled) {
            const audio = new Audio('/sounds/notification.mp3');
            audio.volume = 0.5;
            audio.play().catch(e => console.log('알림음 재생 실패:', e));
        }
    }
    
    // 데스크톱 알림 표시
    showDesktopNotification(message, options = {}) {
        if ('Notification' in window && 
            Notification.permission === 'granted' && 
            this.options.desktopNotificationEnabled) {
            
            const notification = new Notification('학교 민원 시스템', {
                body: message,
                icon: '/images/notification-icon.png',
                badge: '/images/notification-badge.png',
                ...options
            });
            
            notification.onclick = () => {
                window.focus();
                notification.close();
            };
            
            // 5초 후 자동 닫기
            setTimeout(() => notification.close(), 5000);
        }
    }
    
    // 이벤트 리스너 초기화
    initializeEventListeners() {
        // 알림 버튼 클릭
        const notificationBtn = document.getElementById('notificationBtn');
        if (notificationBtn) {
            notificationBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.toggleDropdown();
            });
        }
        
        // 모두 읽음 버튼
        const markAllBtn = document.getElementById('markAllNotificationsRead');
        if (markAllBtn) {
            markAllBtn.addEventListener('click', () => this.markAllAsRead());
        }
        
        // 설정 버튼
        const settingsBtn = document.getElementById('notificationSettings');
        if (settingsBtn) {
            settingsBtn.addEventListener('click', () => this.showSettings());
        }
        
        // 외부 클릭시 드롭다운 닫기
        document.addEventListener('click', (e) => {
            const dropdown = document.querySelector('.notification-dropdown');
            if (dropdown && !dropdown.contains(e.target)) {
                dropdown.classList.remove('show');
            }
        });
    }
    
    // 드롭다운 토글
    toggleDropdown() {
        const dropdown = document.querySelector('.notification-dropdown');
        if (dropdown) {
            dropdown.classList.toggle('show');
            
            // 드롭다운이 열릴 때 알림 새로고침
            if (dropdown.classList.contains('show')) {
                this.loadNotifications();
            }
        }
    }
    
    // 설정 표시
    async showSettings() {
        try {
            const response = await apiRequest(`${this.options.apiEndpoint}/settings`);
            
            if (response.success) {
                // 설정 모달 표시 (별도 구현 필요)
                this.showSettingsModal(response.data);
            }
        } catch (error) {
            showNotification('설정을 불러올 수 없습니다.', 'error');
        }
    }
    
    // 설정 모달 표시 (구현 예시)
    showSettingsModal(settings) {
        // 모달 HTML 생성 및 표시
        const modalHTML = `
            <div class="modal fade" id="notificationSettingsModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">알림 설정</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="notificationSettingsForm">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="emailNotifications" 
                                           ${settings.email_notifications ? 'checked' : ''}>
                                    <label class="form-check-label" for="emailNotifications">
                                        이메일 알림 받기
                                    </label>
                                </div>
                                
                                <h6 class="mb-3">알림 유형별 설정</h6>
                                
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="complaintCreated" 
                                           ${settings.complaint_created ? 'checked' : ''}>
                                    <label class="form-check-label" for="complaintCreated">
                                        새 민원 접수 알림
                                    </label>
                                </div>
                                
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="complaintAssigned" 
                                           ${settings.complaint_assigned ? 'checked' : ''}>
                                    <label class="form-check-label" for="complaintAssigned">
                                        민원 배정 알림
                                    </label>
                                </div>
                                
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="complaintCommented" 
                                           ${settings.complaint_commented ? 'checked' : ''}>
                                    <label class="form-check-label" for="complaintCommented">
                                        댓글 알림
                                    </label>
                                </div>
                                
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="complaintStatusChanged" 
                                           ${settings.complaint_status_changed ? 'checked' : ''}>
                                    <label class="form-check-label" for="complaintStatusChanged">
                                        상태 변경 알림
                                    </label>
                                </div>
                                
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="complaintDeadlineReminder" 
                                           ${settings.complaint_deadline_reminder ? 'checked' : ''}>
                                    <label class="form-check-label" for="complaintDeadlineReminder">
                                        기한 임박 알림
                                    </label>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                            <button type="button" class="btn btn-primary" onclick="notificationManager.saveSettings()">저장</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // 기존 모달 제거
        const existingModal = document.getElementById('notificationSettingsModal');
        if (existingModal) {
            existingModal.remove();
        }
        
        // 새 모달 추가
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        
        // 모달 표시
        const modal = new bootstrap.Modal(document.getElementById('notificationSettingsModal'));
        modal.show();
    }
    
    // 설정 저장
    async saveSettings() {
        const form = document.getElementById('notificationSettingsForm');
        if (!form) return;
        
        const settings = {
            email_notifications: form.querySelector('#emailNotifications').checked,
            complaint_created: form.querySelector('#complaintCreated').checked,
            complaint_assigned: form.querySelector('#complaintAssigned').checked,
            complaint_commented: form.querySelector('#complaintCommented').checked,
            complaint_status_changed: form.querySelector('#complaintStatusChanged').checked,
            complaint_deadline_reminder: form.querySelector('#complaintDeadlineReminder').checked
        };
        
        try {
            const response = await apiRequest(`${this.options.apiEndpoint}/settings`, {
                method: 'PUT',
                body: JSON.stringify(settings)
            });
            
            if (response.success) {
                showNotification('알림 설정이 저장되었습니다.', 'success');
                bootstrap.Modal.getInstance(document.getElementById('notificationSettingsModal')).hide();
            }
        } catch (error) {
            showNotification('설정 저장에 실패했습니다.', 'error');
        }
    }
    
    // 정리
    destroy() {
        if (this.updateInterval) {
            clearInterval(this.updateInterval);
        }
    }
}

// 전역 사용을 위해 window 객체에 추가
window.NotificationManager = NotificationManager;

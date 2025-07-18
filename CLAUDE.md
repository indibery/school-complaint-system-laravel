# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel 11-based school complaint management system with a React Native mobile app. The system manages complaints from parents and school guardians, with role-based access control and comprehensive notification systems.

## Development Commands

### Laravel Backend

```bash
# Start development server with all services
composer run dev

# Individual commands
php artisan serve                    # Web server
php artisan queue:listen --tries=1   # Queue worker
php artisan pail --timeout=0        # Log monitoring
npm run dev                         # Frontend assets

# Testing
composer run test                   # Run all tests
php artisan test                    # Alternative test command

# Database
php artisan migrate                 # Run migrations
php artisan db:seed                 # Seed database
php artisan migrate:fresh --seed    # Fresh migration with seed data

# Code quality
./vendor/bin/pint                   # Code formatting (Laravel Pint)
php artisan config:clear            # Clear config cache
php artisan cache:clear             # Clear application cache
```

### React Native Mobile App

```bash
# Navigate to mobile app directory
cd mobile-app/SchoolComplaintApp

# Start development
npm start                           # Expo development server
npm run start-safe                  # Safe start with port 8082
npm run start-clean                 # Clean start (clears cache)

# Platform-specific
npm run android                     # Android development
npm run ios                         # iOS development
npm run web                         # Web development

# Utilities
npm run clean                       # Clean cache and build files
npm run kill-ports                  # Kill conflicting ports
npm run test-auth                   # Test authentication system
```

## Architecture Overview

### Backend Architecture (Laravel)

**Service Layer Pattern**: Business logic is encapsulated in dedicated service classes with interfaces
- `ComplaintService`: Core complaint management
- `ComplaintStatusService`: Status transitions and validation
- `ComplaintAssignmentService`: Assignment and delegation logic
- `ComplaintFileService`: File attachment handling
- `ComplaintNotificationService`: Notification dispatching
- `ComplaintStatisticsService`: Analytics and reporting

**Action Classes**: Single-responsibility actions for complex operations
- `CreateComplaintAction`: Handles complaint creation workflow
- `UpdateComplaintStatusAction`: Manages status transitions
- `AssignComplaintAction`: Handles complaint assignment

**Repository Pattern**: Data access abstraction with interfaces
- `ComplaintRepository`: Database operations for complaints
- `ComplaintStatisticsRepository`: Statistical queries

**API Structure**:
- `Api/` controllers for mobile app communication
- `Web/` controllers for web interface
- `BaseApiController`: Common API functionality and response formatting

### Frontend Architecture (React Native)

**Navigation Structure**:
- `AppNavigator`: Root navigation with authentication flow
- `MainTabNavigator`: Bottom tab navigation for authenticated users
- Stack navigators for individual feature flows

**State Management**:
- `AuthContext`: User authentication and session management
- AsyncStorage for token persistence
- React Context API for global state

**API Integration**:
- Axios instance with automatic token injection
- Request/response interceptors for authentication
- Centralized error handling with automatic logout on 401

## Key Features & Business Logic

### User Roles and Permissions
- **Admin**: Full system access, user management
- **Staff**: Complaint processing, assignment, responses
- **Parent**: Complaint creation, tracking own complaints
- **Guardian**: Facility/safety complaint reporting with emergency features

### Complaint Status Flow
1. **pending**: Initial state after creation
2. **in_progress**: Assigned to staff member
3. **resolved**: Completed with response
4. **closed**: Finalized (no further action)

### File Management
- Complaints support multiple file attachments
- Files stored in `storage/app/complaints/`
- Automatic file validation and size limits
- Secure file serving with access control

### Notification System
- Email notifications for status changes
- Real-time in-app notifications
- Push notifications for mobile app
- Configurable notification preferences

## Database Design

### Core Tables
- `users`: Authentication and user profiles
- `complaints`: Main complaint records
- `categories`: Complaint categorization
- `comments`: Complaint discussions
- `attachments`: File uploads
- `complaint_status_logs`: Status change audit trail
- `notifications`: In-app notification management

### Important Relationships
- Users can have multiple complaints (one-to-many)
- Complaints belong to categories (many-to-one)
- Complaints can have multiple attachments (one-to-many)
- Complaints can have multiple comments (one-to-many)
- Each status change is logged (audit trail)

## Configuration & Environment

### Laravel Environment Variables
```env
APP_NAME="학교 민원 시스템"
APP_LOCALE=ko
COMPLAINT_AUTO_ASSIGN=true
COMPLAINT_EMAIL_NOTIFICATION=true
COMPLAINT_FILE_MAX_SIZE=10240
COMPLAINT_ALLOWED_EXTENSIONS=jpg,jpeg,png,pdf,doc,docx
COMPLAINT_DEFAULT_PRIORITY=medium
COMPLAINT_RESPONSE_DEADLINE_DAYS=7
```

### Mobile App Configuration
- Base URL: `http://localhost:8000/api`
- Authentication: Bearer token
- Timeout: 30 seconds
- Automatic token refresh on 401 errors

## Common Development Patterns

### Laravel Patterns
- Form Request classes for validation (e.g., `ComplaintStoreRequest`)
- Resource classes for API responses (e.g., `ComplaintResource`)
- Event-driven architecture for notifications
- Policy classes for authorization (e.g., `ComplaintPolicy`)
- Enum classes for constants (e.g., `ComplaintStatus`, `UserRole`)

### React Native Patterns
- Functional components with hooks
- Context API for global state management
- Paper UI components with Material Design 3
- Async/await for API calls
- Error boundaries for graceful error handling

## Testing Strategy

### Laravel Testing
- Feature tests for API endpoints
- Unit tests for service classes
- Database factories for test data
- Policy tests for authorization rules

### Mobile App Testing
- Authentication flow testing
- API integration testing
- UI component testing
- Navigation testing

## Development Workflow

1. **Backend Development**:
   - Create/modify migrations for database changes
   - Implement service classes with interfaces
   - Add validation through Form Request classes
   - Create API resources for consistent responses
   - Write tests for new functionality

2. **Frontend Development**:
   - Update API service methods
   - Implement UI components with React Native Paper
   - Add navigation routes as needed
   - Update authentication context if needed
   - Test on both iOS and Android

3. **Integration Testing**:
   - Test API endpoints with mobile app
   - Verify authentication flow
   - Test file upload functionality
   - Validate notification system

## Security Considerations

- All API endpoints require authentication except public routes
- CSRF protection enabled for web routes
- File upload validation and sanitization
- SQL injection prevention through Eloquent ORM
- XSS protection through output escaping
- Rate limiting on API endpoints
- Secure token storage in mobile app

## Performance Optimization

- Database query optimization with eager loading
- API response caching where appropriate
- Image optimization for mobile app
- Lazy loading for large datasets
- Pagination for list views
- Background queue processing for notifications
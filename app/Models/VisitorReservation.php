<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitorReservation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'visitor_name',
        'visitor_phone',
        'visitor_id_number',
        'visit_purpose',
        'visit_date',
        'visit_time_from',
        'visit_time_to',
        'visitee_name',
        'visitee_department',
        'visitee_phone',
        'status',
        'reservation_method',
        'checked_in_at',
        'checked_out_at',
        'managed_by',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'visit_date' => 'date',
        'visit_time_from' => 'datetime:H:i',
        'visit_time_to' => 'datetime:H:i',
        'checked_in_at' => 'datetime',
        'checked_out_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 방문 상태 상수
     */
    const STATUS_RESERVED = 'reserved';       // 예약됨
    const STATUS_CONFIRMED = 'confirmed';     // 확인됨
    const STATUS_CHECKED_IN = 'checked_in';   // 입장
    const STATUS_CHECKED_OUT = 'checked_out'; // 퇴장
    const STATUS_NO_SHOW = 'no_show';         // 미출석
    const STATUS_CANCELLED = 'cancelled';     // 취소됨

    /**
     * 예약 방법 상수
     */
    const METHOD_ONLINE = 'online';           // 온라인 예약
    const METHOD_PHONE = 'phone';             // 전화 예약
    const METHOD_WALK_IN = 'walk_in';         // 당일 방문

    /**
     * 방문 관리자 (학교지킴이)
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'managed_by');
    }

    /**
     * 방문 상태 한글 라벨
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_RESERVED => '예약됨',
            self::STATUS_CONFIRMED => '확인됨',
            self::STATUS_CHECKED_IN => '입장',
            self::STATUS_CHECKED_OUT => '퇴장',
            self::STATUS_NO_SHOW => '미출석',
            self::STATUS_CANCELLED => '취소됨',
            default => '알 수 없음',
        };
    }

    /**
     * 예약 방법 한글 라벨
     */
    public function getMethodLabelAttribute(): string
    {
        return match($this->reservation_method) {
            self::METHOD_ONLINE => '온라인 예약',
            self::METHOD_PHONE => '전화 예약',
            self::METHOD_WALK_IN => '당일 방문',
            default => '기타',
        };
    }

    /**
     * 방문 시간 범위 반환
     */
    public function getVisitTimeRangeAttribute(): string
    {
        return $this->visit_time_from->format('H:i') . ' - ' . $this->visit_time_to->format('H:i');
    }

    /**
     * 체크인 가능 여부
     */
    public function canCheckIn(): bool
    {
        return in_array($this->status, [self::STATUS_RESERVED, self::STATUS_CONFIRMED]);
    }

    /**
     * 체크아웃 가능 여부
     */
    public function canCheckOut(): bool
    {
        return $this->status === self::STATUS_CHECKED_IN;
    }

    /**
     * 오늘 방문 예약 스코프
     */
    public function scopeToday($query)
    {
        return $query->whereDate('visit_date', today());
    }

    /**
     * 특정 날짜 방문 예약 스코프
     */
    public function scopeByDate($query, $date)
    {
        return $query->whereDate('visit_date', $date);
    }

    /**
     * 상태별 스코프
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * 활성 방문자 스코프 (입장 상태)
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_CHECKED_IN);
    }

    /**
     * 예약 방법별 스코프
     */
    public function scopeByMethod($query, $method)
    {
        return $query->where('reservation_method', $method);
    }

    /**
     * 방문자 이름 검색 스코프
     */
    public function scopeSearchByName($query, $name)
    {
        return $query->where('visitor_name', 'like', "%{$name}%");
    }

    /**
     * 방문자 연락처 검색 스코프
     */
    public function scopeSearchByPhone($query, $phone)
    {
        return $query->where('visitor_phone', 'like', "%{$phone}%");
    }

    /**
     * 방문 예약 유효성 검증 규칙
     */
    public static function getValidationRules($isUpdate = false): array
    {
        return [
            'visitor_name' => 'required|string|max:255|regex:/^[가-힣a-zA-Z\s]+$/',
            'visitor_phone' => 'required|string|regex:/^01[016789]-?[0-9]{3,4}-?[0-9]{4}$/',
            'visitor_id_number' => 'nullable|string|max:20',
            'visit_purpose' => 'required|string|max:500',
            'visit_date' => 'required|date|after_or_equal:today',
            'visit_time_from' => 'required|date_format:H:i',
            'visit_time_to' => 'required|date_format:H:i|after:visit_time_from',
            'visitee_name' => 'required|string|max:255',
            'visitee_department' => 'nullable|string|max:255',
            'visitee_phone' => 'nullable|string|regex:/^0[0-9]{1,2}-?[0-9]{3,4}-?[0-9]{4}$/',
            'reservation_method' => 'required|in:online,phone,walk_in',
        ];
    }

    /**
     * 방문 예약 유효성 검증 메시지
     */
    public static function getValidationMessages(): array
    {
        return [
            'visitor_name.required' => '방문자 이름은 필수입니다.',
            'visitor_name.regex' => '방문자 이름은 한글, 영문, 공백만 입력 가능합니다.',
            'visitor_phone.required' => '방문자 연락처는 필수입니다.',
            'visitor_phone.regex' => '올바른 휴대폰 번호 형식을 입력해주세요.',
            'visit_purpose.required' => '방문 목적은 필수입니다.',
            'visit_date.required' => '방문 날짜는 필수입니다.',
            'visit_date.after_or_equal' => '방문 날짜는 오늘 이후여야 합니다.',
            'visit_time_from.required' => '방문 시작 시간은 필수입니다.',
            'visit_time_to.required' => '방문 종료 시간은 필수입니다.',
            'visit_time_to.after' => '종료 시간은 시작 시간보다 늦어야 합니다.',
            'visitee_name.required' => '면담자 이름은 필수입니다.',
            'reservation_method.required' => '예약 방법은 필수입니다.',
        ];
    }
}

    /**
     * 처리 부서
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * 관련 학생 (학부모가 자녀 관련 민원 제기시)
     */
    public function relatedStudent(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'related_student_id');
    }

    /**
     * 관련 방문자 예약 (방문 관련 민원인 경우)
     */
    public function visitorReservation(): BelongsTo
    {
        return $this->belongsTo(VisitorReservation::class, 'visitor_reservation_id');
    }
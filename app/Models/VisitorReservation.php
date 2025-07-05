    /**
     * 방문 관리자 (학교지킴이)
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'managed_by');
    }

    /**
     * 이 방문과 관련된 민원들
     */
    public function relatedComplaints(): HasMany
    {
        return $this->hasMany(Complaint::class, 'visitor_reservation_id');
    }
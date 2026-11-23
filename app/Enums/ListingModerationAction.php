<?php

namespace App\Enums;

/**
 * Aksi moderasi listing oleh admin, disimpan di listing_moderations.action (PBI-06).
 */
enum ListingModerationAction: string
{
    case Deactivate = 'DEACTIVATE';
    case Restore = 'RESTORE';

    public function label(): string
    {
        return __('listing-moderation.action.'.$this->value);
    }

    /**
     * Status moderasi listing setelah aksi ini berhasil.
     */
    public function targetStatus(): ListingModerationStatus
    {
        return match ($this) {
            self::Deactivate => ListingModerationStatus::Hidden,
            self::Restore => ListingModerationStatus::Visible,
        };
    }

    /**
     * Nilai audit_logs.action untuk aksi ini (FR-021).
     */
    public function auditAction(): string
    {
        return match ($this) {
            self::Deactivate => 'LISTING_DEACTIVATED',
            self::Restore => 'LISTING_RESTORED',
        };
    }
}

<?php

namespace App\Enums;

enum PlanStatus: string
{
    case Pending = 'pending';
    case Draft = 'draft';
    case Published = 'published';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu',
            self::Draft => 'Draf',
            self::Published => 'Terbit',
        };
    }

    public function isPublished(): bool
    {
        return $this === self::Published;
    }
}

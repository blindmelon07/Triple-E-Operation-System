<?php

namespace App\Enums;

enum DeliveryStatus: string
{
    case Pending = 'pending';
    case Assigned = 'assigned';
    case PickedUp = 'picked_up';
    case InTransit = 'in_transit';
    case Delivered = 'delivered';
    case Failed = 'failed';
    case Returned = 'returned';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Assigned => 'Assigned',
            self::PickedUp => 'Picked Up',
            self::InTransit => 'In Transit',
            self::Delivered => 'Delivered',
            self::Failed => 'Failed',
            self::Returned => 'Returned',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Assigned => 'info',
            self::PickedUp => 'warning',
            self::InTransit => 'primary',
            self::Delivered => 'success',
            self::Failed => 'danger',
            self::Returned => 'warning',
        };
    }
}

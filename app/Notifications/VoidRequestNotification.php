<?php

namespace App\Notifications;

use App\Models\VoidRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class VoidRequestNotification extends Notification
{
    use Queueable;

    public function __construct(public VoidRequest $voidRequest) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $sale = $this->voidRequest->sale;
        $requester = $this->voidRequest->requestedBy;

        return [
            'void_request_id' => $this->voidRequest->id,
            'sale_id'         => $sale->id,
            'sale_total'      => $sale->total,
            'void_reason'     => $this->voidRequest->void_reason,
            'requested_by'    => $requester?->name ?? 'Unknown',
            'message'         => "Void request for Receipt #" . str_pad($sale->id, 6, '0', STR_PAD_LEFT) . " by {$requester?->name}",
        ];
    }
}

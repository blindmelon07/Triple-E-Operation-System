<?php

namespace App\Observers;

use App\Mail\QuotationApprovalRequestMail;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class QuotationObserver
{
    /**
     * Handle the Quotation "creating" event.
     */
    public function creating(Quotation $quotation): void
    {
        // Set created_by to current authenticated user if not already set
        if (auth()->check() && !$quotation->created_by) {
            $quotation->created_by = auth()->id();
        }
    }

    /**
     * Handle the Quotation "created" event.
     */
    public function created(Quotation $quotation): void
    {
        // Load relationships for email
        $quotation->load(['customer', 'creator']);

        // Send email to all admins with approval permission
        $admins = User::where(function ($query) {
            $query->whereHas('roles', function ($q) {
                $q->where('name', 'super_admin');
            })->orWhereHas('permissions', function ($q) {
                $q->where('name', 'approve_quotation');
            });
        })->get();

        foreach ($admins as $admin) {
            if ($admin->email) {
                Mail::to($admin->email)->send(new QuotationApprovalRequestMail($quotation));
            }
        }
    }

    /**
     * Handle the Quotation "saved" event.
     */
    public function saved(Quotation $quotation): void
    {
        // Recalculate total after save
        $total = $quotation->quotation_items()->sum('price');

        if ($quotation->total != $total) {
            $quotation->updateQuietly(['total' => $total]);
        }
    }
}

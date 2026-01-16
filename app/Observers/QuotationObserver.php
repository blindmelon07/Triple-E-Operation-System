<?php

namespace App\Observers;

use App\Models\Quotation;

class QuotationObserver
{
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

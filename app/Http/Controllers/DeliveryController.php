<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use Illuminate\Contracts\View\View;

class DeliveryController extends Controller
{
    public function printReceipt(Delivery $delivery): View
    {
        $delivery->load(['sale.customer', 'sale.sale_items.product', 'driver']);

        return view('deliveries.receipt-print', compact('delivery'));
    }
}

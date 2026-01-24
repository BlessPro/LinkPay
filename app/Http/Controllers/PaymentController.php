<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $payments = $request->user()->payments()->latest()->paginate(10);

        return view('dashboard.payments.index', [
            'payments' => $payments,
            'currency' => config('services.paystack.currency', 'GHS'),
        ]);
    }
}

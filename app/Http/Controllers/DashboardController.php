<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $totalReceived = (string) $user->payments()
            ->where('status', Payment::STATUS_SUCCESS)
            ->sum('amount');

        $invoiceCount = $user->invoices()->count();
        $productCount = $user->products()->count();

        $recentPayments = $user->payments()
            ->latest()
            ->take(5)
            ->get();

        $recentInvoices = $user->invoices()
            ->latest()
            ->take(5)
            ->get();

        return view('dashboard.index', [
            'totalReceived' => $totalReceived,
            'invoiceCount' => $invoiceCount,
            'productCount' => $productCount,
            'recentPayments' => $recentPayments,
            'recentInvoices' => $recentInvoices,
            'currency' => config('services.paystack.currency', 'GHS'),
        ]);
    }
}

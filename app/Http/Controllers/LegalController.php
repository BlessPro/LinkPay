<?php

namespace App\Http\Controllers;

use App\Models\DataDeletionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LegalController extends Controller
{
    public function privacy(): View
    {
        return view('legal.privacy');
    }

    public function terms(): View
    {
        return view('legal.terms');
    }

    public function storeDataDeletionRequest(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = $request->user();
        DataDeletionRequest::query()->create([
            'user_id' => $user?->id,
            'email' => (string) $user?->email,
            'status' => DataDeletionRequest::STATUS_PENDING,
            'note' => $validated['note'] ?? null,
            'requested_at' => now(),
        ]);

        return back()->with('status', 'data-deletion-requested');
    }
}


<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PinSetupController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->pin_hash) {
            return redirect()->route('dashboard');
        }

        return view('auth.pin-setup');
    }

    public function store(Request $request): RedirectResponse
    {
        $pinLength = max(4, min(8, (int) config('auth_phone.pin.length', 4)));
        $data = $request->validate([
            'pin' => ['required', 'digits:'.$pinLength, 'confirmed'],
        ]);

        if ($this->isWeakPin((string) $data['pin'])) {
            throw ValidationException::withMessages([
                'pin' => 'Choose a less predictable PIN.',
            ]);
        }

        $user = $request->user();
        $user->pin_hash = Hash::make((string) $data['pin']);
        $user->save();

        return redirect()
            ->route('dashboard')
            ->with('status', 'PIN set successfully.');
    }

    private function isWeakPin(string $pin): bool
    {
        if (! (bool) config('auth_phone.pin.enforce_weak_denylist', true)) {
            return false;
        }

        $weakValues = config('auth_phone.pin.weak_values', []);

        return in_array($pin, $weakValues, true);
    }
}


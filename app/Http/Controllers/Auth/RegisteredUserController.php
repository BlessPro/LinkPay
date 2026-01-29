<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SellerProfile;
use App\Models\User;
use App\Support\Phone;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'phone_country' => ['nullable', 'string', Rule::in(['+233'])],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $validator->after(function ($validator) use ($request) {
            if (! $request->filled('email') && ! $request->filled('phone_number')) {
                $validator->errors()->add('email', 'Email or WhatsApp number is required.');
                $validator->errors()->add('phone_number', 'WhatsApp number or email is required.');
            }

            if ($request->filled('phone_number')) {
                $normalized = Phone::normalize($request->input('phone_number'), $request->input('phone_country', '+233'));
                if (! $normalized || ! Phone::isValidGh($request->input('phone_number'))) {
                    $validator->errors()->add('phone_number', 'Enter a valid WhatsApp number.');
                } elseif (User::where('phone', $normalized)->exists()) {
                    $validator->errors()->add('phone_number', 'This WhatsApp number is already in use.');
                }
            }
        });

        $validator->validate();

        $normalizedPhone = $request->filled('phone_number')
            ? Phone::normalize($request->input('phone_number'), $request->input('phone_country', '+233'))
            : null;

        $user = User::create([
            'name' => $request->name,
            'email' => $request->input('email') ?: null,
            'phone' => $normalizedPhone,
            'password' => Hash::make($request->password),
        ]);

        SellerProfile::create([
            'user_id' => $user->id,
            'business_name' => $user->name,
            'phone' => $normalizedPhone,
            'public_slug' => SellerProfile::generateUniqueSlug($user->name),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\GeneraleSetting;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserDeactivateController extends Controller
{
    /**
     * Display the account deactivation form.
     */
    public function show()
    {
        $setting = GeneraleSetting::first();
        return view('delete-account', compact('setting'));
    }

    /**
     * Handle the account deactivation request.
     */
    public function deactivate(Request $request)
    {
        $request->validate([
            'contact' => ['required', 'string'],
            'password' => ['required', 'string'],
            'confirm' => ['required', 'accepted'],
            'reason' => ['nullable', 'string', 'max:500'],
        ], [
            'contact.required' => 'Please enter your email or phone number.',
            'password.required' => 'Please enter your password.',
            'confirm.accepted' => 'You must check the box to confirm you understand the deactivation process.',
        ]);

        // Find the user by contact details (phone or email)
        $user = UserRepository::findByContact($request->contact);

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return back()
                ->withInput($request->only('contact', 'reason'))
                ->withErrors([
                    'contact' => 'The provided credentials do not match our records.',
                ]);
        }

        if (! $user->is_active) {
            return back()
                ->withInput($request->only('contact', 'reason'))
                ->withErrors([
                    'contact' => 'This account is already deactivated.',
                ]);
        }

        // Deactivate the user
        $user->is_active = false;
        $user->save();

        // Revoke the user's personal access tokens if any exist
        $user->tokens()->delete();

        // Redirect back with a success status flashed to the session
        return back()->with('success', 'Your account has been successfully deactivated.');
    }
}

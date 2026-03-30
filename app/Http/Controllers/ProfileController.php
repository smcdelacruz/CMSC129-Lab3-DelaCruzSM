<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('layouts.profile');
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'email' => 'required|email|unique:users,email,' . $user->id,
            'name' => 'required|string|max:255',
        ]);

        $user->update([
            'email' => $request->input('email'),
            'name' => $request->input('name'),
        ]);

        return back()->with('success', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|current_password',
            'new_password' => ['required', 'confirmed', Password::defaults()],
        ]);

        Auth::user()->update([
            'password' => Hash::make($request->input('new_password')),
        ]);

        return back()->with('success', 'Password updated successfully.');
    }
}

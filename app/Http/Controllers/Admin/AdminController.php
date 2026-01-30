<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    /**
     * Handle admin login
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $admin = Admin::where('email', $request->email)->first();

        if (!$admin) {
            throw ValidationException::withMessages([
                'email' => ['No admin account found with this email address.'],
            ]);
        }

        if (!Hash::check($request->password, $admin->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Log in the admin
        Auth::guard('admin')->login($admin, $request->boolean('remember'));

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'userType' => 'admin',
            'user' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
            ],
        ]);
    }

    /**
     * Get current authenticated admin
     */
    public function user(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        if ($admin) {
            return response()->json([
                'authenticated' => true,
                'userType' => 'admin',
                'user' => [
                    'id' => $admin->id,
                    'name' => $admin->name,
                    'email' => $admin->email,
                ],
            ]);
        }

        return response()->json([
            'authenticated' => false,
            'userType' => null,
            'user' => null,
        ]);
    }

    /**
     * Handle admin logout
     */
    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'success' => true,
            'message' => 'Logout successful',
        ]);
    }
}

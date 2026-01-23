<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Landlord;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Handle login for both landlords and tenants
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'userType' => 'required|in:landlord,tenant',
        ]);

        if ($request->userType === 'landlord') {
            return $this->loginLandlord($request);
        } else {
            return $this->loginTenant($request);
        }
    }

    /**
     * Handle landlord login
     */
    private function loginLandlord(Request $request)
    {
        $landlord = Landlord::where('email', $request->email)->first();

        if (!$landlord) {
            throw ValidationException::withMessages([
                'email' => ['No landlord account found with this email address.'],
            ]);
        }

        if (!Hash::check($request->password, $landlord->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Log in the landlord
        Auth::guard('landlord')->login($landlord, $request->boolean('remember'));

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'userType' => 'landlord',
            'user' => [
                'id' => $landlord->id,
                'name' => $landlord->name,
                'email' => $landlord->email,
            ],
        ]);
    }

    /**
     * Handle tenant login
     */
    private function loginTenant(Request $request)
    {
        $tenant = Tenant::where('email', $request->email)->first();

        if (!$tenant) {
            throw ValidationException::withMessages([
                'email' => ['No tenant account found with this email address.'],
            ]);
        }

        if (!Hash::check($request->password, $tenant->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Log in the tenant
        Auth::guard('tenant')->login($tenant, $request->boolean('remember'));

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'userType' => 'tenant',
            'user' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'email' => $tenant->email,
            ],
        ]);
    }

    /**
     * Get current authenticated user
     */
    public function user(Request $request)
    {
        $landlord = Auth::guard('landlord')->user();
        $tenant = Auth::guard('tenant')->user();

        if ($landlord) {
            return response()->json([
                'authenticated' => true,
                'userType' => 'landlord',
                'user' => [
                    'id' => $landlord->id,
                    'name' => $landlord->name,
                    'email' => $landlord->email,
                ],
            ]);
        }

        if ($tenant) {
            return response()->json([
                'authenticated' => true,
                'userType' => 'tenant',
                'user' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'email' => $tenant->email,
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
     * Handle logout
     */
    public function logout(Request $request)
    {
        // Logout from both guards
        Auth::guard('landlord')->logout();
        Auth::guard('tenant')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'success' => true,
            'message' => 'Logout successful',
        ]);
    }
}

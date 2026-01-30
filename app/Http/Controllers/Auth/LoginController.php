<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Landlord;
use App\Models\Tenant;
use App\Models\Admin;
use App\Models\PropertyManager;
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
            'userType' => 'required|in:landlord,tenant,property_manager',
        ]);

        if ($request->userType === 'landlord') {
            return $this->loginLandlord($request);
        } elseif ($request->userType === 'property_manager') {
            return $this->loginPropertyManager($request);
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
     * Handle property manager login
     */
    private function loginPropertyManager(Request $request)
    {
        $manager = PropertyManager::where('email', $request->email)->first();

        if (!$manager) {
            throw ValidationException::withMessages([
                'email' => ['No property manager account found with this email address.'],
            ]);
        }

        if (!Hash::check($request->password, $manager->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Log in the property manager
        Auth::guard('property_manager')->login($manager, $request->boolean('remember'));

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'userType' => 'property_manager',
            'user' => [
                'id' => $manager->id,
                'name' => $manager->name,
                'email' => $manager->email,
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
        $admin = Auth::guard('admin')->user();
        $landlord = Auth::guard('landlord')->user();
        $tenant = Auth::guard('tenant')->user();

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

        $propertyManager = Auth::guard('property_manager')->user();

        if ($propertyManager) {
            return response()->json([
                'authenticated' => true,
                'userType' => 'property_manager',
                'user' => [
                    'id' => $propertyManager->id,
                    'name' => $propertyManager->name,
                    'email' => $propertyManager->email,
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
        // Logout from all guards
        Auth::guard('admin')->logout();
        Auth::guard('landlord')->logout();
        Auth::guard('tenant')->logout();
        Auth::guard('property_manager')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'success' => true,
            'message' => 'Logout successful',
        ]);
    }
}

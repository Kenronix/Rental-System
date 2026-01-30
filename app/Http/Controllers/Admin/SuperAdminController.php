<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SuperAdminController extends Controller
{
    public function dashboard()
    {
        return response()->json([
            'stats' => [
                'total_landlords' => \App\Models\Landlord::count(),
                'total_tenants' => \App\Models\Tenant::count(),
                'total_properties' => \App\Models\Property::count(),
                'active_users' => \App\Models\User::count(), // Assuming User model represents active general users
                'pending_verifications' => 0, // Placeholder for now
            ],
            'recent_activity' => [
                // Placeholder activities based on the design
                [
                    'type' => 'property',
                    'user' => 'Sarah Landlord',
                    'action' => 'added a new property',
                    'target' => 'Sunset Apartments',
                    'time' => '2 hours ago',
                    'initial' => 'S'
                ],
                [
                    'type' => 'payment',
                    'user' => 'Tom Tenant',
                    'action' => 'paid rent for',
                    'target' => 'Unit 402',
                    'time' => '4 hours ago',
                    'initial' => 'T'
                ],
                [
                    'type' => 'system',
                    'user' => 'System',
                    'action' => 'generated monthly reports',
                    'target' => 'October 2023',
                    'time' => '1 day ago',
                    'initial' => 'S'
                ],
                [
                    'type' => 'admin',
                    'user' => 'Mike Admin',
                    'action' => 'approved maintenance request',
                    'target' => '#REQ-2023-001',
                    'time' => '1 day ago',
                    'initial' => 'M'
                ],
            ]
        ]);
    }
}

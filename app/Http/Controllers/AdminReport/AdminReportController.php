<?php

namespace App\Http\Controllers\AdminReport;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserKyc\UserKycTrack;
use Illuminate\Http\Request;

class AdminReportController extends Controller
{
    public function adminReports(Request $request)
    {
        $user = $request->user();

        if (!$user || $user->role !== 'admin') {
            return response()->json([
                'status' => 403,
                'message' => 'Unauthorized access',
            ], 403);
        }

        $date = $request->input('date'); // optional date filter

        // Employee count
        $employeeQuery = User::where('role', 'employee');
        if ($date) {
            $employeeQuery->whereDate('created_at', $date);
        }
        $totalEmployees = $employeeQuery->count();

        // Customers (users)
        $customerQuery = User::where('role', 'user');
        if ($date) {
            $customerQuery->whereDate('created_at', $date);
        }
        $totalCustomers = $customerQuery->count();

        // Track KYC progress across all users
        $trackQuery = UserKycTrack::whereHas('user', function ($query) use ($date) {
            if ($date) {
                $query->whereDate('created_at', $date);
            }
        });

        $completedCustomers = (clone $trackQuery)->where('user_kyc_status', 'completed')->count();
        $pendingCustomers = (clone $trackQuery)->where('user_kyc_status', 'pending')->count();
        $processingCustomers = (clone $trackQuery)->where('user_kyc_status', 'processing')->count();

        return response()->json([
            'status' => 200,
            'message' => 'Admin Reports Accessed',
            'user' => $user,
            'date_filter' => $date,
            'total_employees' => $totalEmployees,
            'total_customers' => $totalCustomers,
            'completed_customers' => $completedCustomers,
            'pending_customers' => $pendingCustomers,
            'processing_customers' => $processingCustomers,
        ]);
    }

    public function searchUsersAdmin(Request $request)
    {
        try {
            $role = 'user';
            $name = $request->query('name');
            $status = $request->input('status', 'all');
            $authUser = $request->user();

            if ($authUser->role !== 'admin') {
                return response()->json([
                    'status' => 403,
                    'message' => 'Unauthorized role access',
                ]);
            }

            // ✅ Base query: only user accounts
            $query = User::where('role', $role)
                ->with('kycTrack'); // eager load kycTrack for visibility

            // ✅ Apply KYC status filter (admin can use it too)
            if ($status !== 'all') {
                $query->whereHas('kycTrack', function ($q) use ($status) {
                    $q->where('user_kyc_status', $status);
                });
            }

            // ✅ Name or phone search (case-insensitive)
            if ($name) {
                $query->where(function ($q) use ($name) {
                    $q->where('name', 'like', '%' . $name . '%')
                    ->orWhere('phone', 'like', '%' . $name . '%');
                });
            }

            // ✅ Final results
            $users = $query->get();

            if ($users->isEmpty()) {
                return response()->json([
                    'status' => 404,
                    'message' => 'No users found',
                ]);
            }

            // ✅ Optional: add employee name (if you want like earlier example)
            $users->map(function ($u) {
                $u->employee_name = optional($u->employee)->name ?? null;
                return $u;
            });

            return response()->json([
                'status' => 200,
                'message' => 'User list retrieved successfully',
                'filter_status' => $status,
                'data' => $users,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Server error - searchUsersAdmin',
                'error' => $e->getMessage(),
            ]);
        }
    }


}

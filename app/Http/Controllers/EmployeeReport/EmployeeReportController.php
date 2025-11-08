<?php

namespace App\Http\Controllers\EmployeeReport;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserKyc\UserKycTrack;

class EmployeeReportController extends Controller
{
    public function employeeReports(Request $request)
    {
        $user = $request->user();

        if (!$user || $user->role !== 'employee') {
            return response()->json([
                'status' => 403,
                'message' => 'Unauthorized access',
            ], 403);
        }

        $date = $request->input('date'); // optional date filter

        $userQuery = User::where('role', 'user')->where('employee_id', $user->id);
        if ($date) {
            $userQuery->whereDate('created_at', $date);
        }
        $totalClients = $userQuery->count();

        $trackQuery = UserKycTrack::whereHas('user', function ($query) use ($user, $date) {
            $query->where('employee_id', $user->id);
            if ($date) {
                $query->whereDate('created_at', $date);
            }
        });

        $totalPendingClients = (clone $trackQuery)->where('user_kyc_status', 'pending')->count();
        $totalCompletedClients = (clone $trackQuery)->where('user_kyc_status', 'completed')->count();
        $totalProcessingClients = (clone $trackQuery)->where('user_kyc_status', 'processing')->count();

        return response()->json([
            'status' => 200,
            'message' => 'Employee Reports Accessed',
            'user' => $user,
            'date_filter' => $date,
            'total_clients' => $totalClients,
            'total_pending_clients' => $totalPendingClients,
            'total_completed_clients' => $totalCompletedClients,
            'active_clients' => $totalProcessingClients,
        ]);
    }

}

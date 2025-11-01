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
                'message' => 'Unauthorized access'
            ], 403);
        }

        $totalClients = User::where('role', 'user')->where('employee_id', $user->id)->count();
        
        $totalPendingClients = UserKycTrack::whereHas('user', function ($query) use ($user) {
            $query->where('employee_id', $user->id);
        })->where('user_kyc_status', 'pending')->count();

        $totalCompletedClients = UserKycTrack::whereHas('user', function ($query) use ($user) {
            $query->where('employee_id', $user->id);
        })->where('user_kyc_status', 'completed')->count();

        return response()->json([
            'status' => 200,
            'message' => 'Employee Reports Accessed',
            'user' => $user,
            'total_clients' => $totalClients,
            'total_pending_clients' => $totalPendingClients,
            'total_completed_clients' => $totalCompletedClients,
        ]);
    }

}

<?php

namespace App\Http\Controllers\KycController;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserKyc\UserBankInfo;
use App\Models\UserKyc\UserDocuments;
use App\Models\UserKyc\UserExtraInfo;
use App\Models\UserKyc\UserPersonalInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class KycReportController extends Controller
{
    public function test(Request $request)
    {
        $to = $request->input('to'); // e.g., '2025-09-28'
        $startOfMonth = Carbon::now()->startOfMonth()->toDateString();
        $endDate = $to ?? Carbon::now()->toDateString();

        $personalInfo = UserPersonalInfo::whereBetween('created_at', [$startOfMonth, $endDate])->get();
        $userBankInfo = UserBankInfo::whereBetween('created_at', [$startOfMonth, $endDate])->get();
        $userExtraInfo = UserExtraInfo::whereBetween('created_at', [$startOfMonth, $endDate])->get();
        $userDocuments = UserDocuments::whereBetween('created_at', [$startOfMonth, $endDate])->get();

        return response()->json([
            'status' => 200,
            'data' => [
                'personalInfo' => $personalInfo,
                'bankInfo'     => $userBankInfo,
                'extraInfo'    => $userExtraInfo,
                'documents'    => $userDocuments,
            ]
        ]);
    }

}

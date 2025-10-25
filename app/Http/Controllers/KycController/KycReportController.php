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
    public function userDashboardReport(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['status' => 401, 'message' => 'Unauthorized']);
            }

            $from = $request->input('from');
            $to = $request->input('to');

            $dataModels = [
                'personalInfo' => UserPersonalInfo::class,
                'bankInfo'     => UserBankInfo::class,
                'extraInfo'    => UserExtraInfo::class,
                'documents'    => UserDocuments::class,
            ];

            $data = [];
            foreach ($dataModels as $key => $model) {
                $query = $model::where('user_id', $user->id);
                if ($from && $to) {
                    $query->whereBetween('created_at', [$from, $to]);
                }
                $data[$key] = $query->get();
            }

            return response()->json(['status' => 200, 'data' => $data]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Server error - userDashboardReport'
            ]);
        }
    }




}

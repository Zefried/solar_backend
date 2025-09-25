<?php

namespace App\Http\Controllers\KycController;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserKyc\UserBankInfo;
use App\Models\UserKyc\UserDocuments;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FetchKycDetailController extends Controller
{
    public function fetchDocs(Request $request)
    {
  
        $userId = null;
        $employeeId = null;

        try {
            $user = $request->user();
            // The code below runs only for employees; customers will skip it
            if ($user->role !== 'user') {

                $userId = $request->input('userId');

                if (!$userId) {
                    return response()->json([
                        'status'  => 422,
                        'message' => 'Please select a user you want to fetch bank info for.'
                    ]);
                }

                // Just Check if user exists in DB
                $targetUser = User::find($userId);

                if (!$targetUser) {
                    return response()->json([
                        'status'  => 404,
                        'message' => 'Selected user not found in the system.'
                    ]);
                }

                // if exist store employee id for tracking
                $employeeId = $user->id;    

        } else {
            $userId = $user->id;
            $employeeId = null;
        }

        $docData = UserDocuments::where('user_id', $userId)->first();

        return response()->json([
                'status'  => 200,
                'message' => 'Fetched document info successfully',
                'data'    => $docData   
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'message' => 'An error occurred: fetchBankInfo'
            ]);
        }
    }

    public function fetchBankInfo(Request $request)
    {
  
        $userId = null;
        $employeeId = null;

        try {
            $user = $request->user();
            // The code below runs only for employees; customers will skip it
            if ($user->role !== 'user') {

                $userId = $request->input('userId');

                if (!$userId) {
                    return response()->json([
                        'status'  => 422,
                        'message' => 'Please select a user you want to fetch bank info for.'
                    ]);
                }

                // Just Check if user exists in DB
                $targetUser = User::find($userId);

                if (!$targetUser) {
                    return response()->json([
                        'status'  => 404,
                        'message' => 'Selected user not found in the system.'
                    ]);
                }

                // if exist store employee id for tracking
                $employeeId = $user->id;    

        } else {
            $userId = $user->id;
            $employeeId = null;
        }

        $bankData = UserBankInfo::where('user_id', $userId)->first();

        return response()->json([
                'status'  => 200,
                'message' => 'Fetched bank info successfully',
                'data'    => $bankData
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'message' => 'An error occurred: fetchBankInfo'
            ]);
        }
    }



}

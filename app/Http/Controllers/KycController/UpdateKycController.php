<?php

namespace App\Http\Controllers\KycController;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserKyc\UserBankInfo;
use Illuminate\Http\Request;

class UpdateKycController extends Controller
{
    public function updateBankInfo(Request $request)
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
                        'message' => 'Please select a user you want to upload documents on behalf of. If user does not exist, add the user first.'
                    ]);
                }

                // Just Check if user exists in DB
                $targetUser = User::find($userId);

                if (!$targetUser) {
                    return response()->json([
                        'status'  => 404,
                        'message' => 'Selected user not found in the system. please register user first!'
                    ]);
                }

                // if exist store employee id for tracking
                $employeeId = $user->id;    

            } else {
                $userId = $user->id;
                $employeeId = null;
            }

            // Update or create bank info
            $bankInfo = UserBankInfo::updateOrCreate(
                ['user_id' => $userId],
                [
                    'account_holder_name' => $request->account_holder_name,
                    'account_number'      => $request->account_number,
                    'bank_name'           => $request->bank_name,
                    'ifsc_code'           => $request->ifsc_code,
                    'branch_name'         => $request->branch_name,
                ]
            );

            return response()->json([
                'status'  => 200,
                'message' => 'Bank info updated successfully',
                'data'    => $bankInfo
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'message' => 'An error occurred: updateBankInfo'
            ]);
        }
    }

}

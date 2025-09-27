<?php

namespace App\Http\Controllers\KycController;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserKyc\UserBankInfo;
use App\Models\UserKyc\UserDocuments;
use App\Models\UserKyc\UserExtraInfo;
use App\Models\UserKyc\UserPersonalInfo;
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

    public function updatePersonalInfo(Request $request)
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

             // Update or create personal info
            $personalInfo = UserPersonalInfo::updateOrCreate(
                ['user_id' => $userId],
                [
                    'first_name'        => $request->first_name,
                    'middle_name'       => $request->middle_name,
                    'last_name'         => $request->last_name,
                    'gender'            => $request->gender,
                    'dob'               => $request->dob,
                    'address'           => $request->address,
                    'city'              => $request->city,
                    'state'             => $request->state,
                    'pincode'           => $request->pincode,
                    'phone'             => $request->phone,
                    'alternative_phone' => $request->alternative_phone,
                ]
            );


            return response()->json([
                'status'  => 200,
                'message' => 'Personal info updated successfully',
                'data'    => $personalInfo
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'message' => 'An error occurred: updatePersonalInfo'
            ]);
        }
    }

    public function updateDocuments(Request $request)
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

            // Fetch existing or create new
            $documentData = UserDocuments::firstOrNew(['user_id' => $userId]);

            // File/Text fields to update only if present
            $fields = [
                'id_proof_front', 'id_proof_back', 'id_proof_number',
                'pan_card', 'pan_number', 'cancelled_cheque',
                'electricity_bill', 'consumer_number'
            ];

           foreach ($fields as $field) {
                if ($request->hasFile($field)) {
                    $file = $request->file($field);
                    $filename = time().'_'.$file->getClientOriginalName();
                    $file->move(public_path('userDocs'), $filename);
                    $documentData->$field = 'userDocs/'.$filename;
                } elseif ($request->has($field)) {
                    $documentData->$field = $request->$field;
                }
            }

            $documentData->save();

            return response()->json([
                'status'  => 200,
                'message' => 'Document info updated successfully',
                'data'    => $documentData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'message' => 'An error occurred: updateDocuments',
                'error'   => $e->getMessage()
            ]);
        }
    }

    public function updateExtraInfo(Request $request)
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
                        'message' => 'Please select a user you want to update extra information on behalf of. If user does not exist, add the user first.'
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
            
            $ExtraInfo = UserExtraInfo::updateOrCreate(
                ['user_id' => $userId],
                [
                    'installation_address' => $request->installation_address, // boolean true/false
                    'village'              => $request->village,
                    'landmark'             => $request->landmark,
                    'district'             => $request->district,
                    'pincode'              => $request->pincode,
                    'state'                => $request->state,
                    'proposed_capacity'    => $request->proposed_capacity,   // string
                    'plot_type'            => $request->plot_type,           // enum: residential, commercial
                ]
            );

            return response()->json([
                'status'  => 200,
                'message' => 'Extra info updated successfully',
                'data'    => $ExtraInfo
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'message' => 'An error occurred: updateDocuments',
                'error'   => $e->getMessage()
            ]);
        }
    }

}

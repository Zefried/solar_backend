<?php

// @This is the high-level workflow without getting into code details.

// Determines whether the current user is an employee or a regular user.
// Gets the correct userId for whom KYC documents will be uploaded.
// Optionally validates uploaded files and input fields.
// Checks for existing documents for that user.
// Saves or updates uploaded files and input data in the UserDocuments table.
// Moves uploaded files to a folder, replacing old files if needed.
// Checks if all required KYC fields are filled.
// Updates the UserKycTrack table to mark KYC as complete, linking to the employee if applicable.
// Returns a JSON response with the saved document data.

// @ ends

// @@@ imp note remove all user_id hardcoded after testing


namespace App\Http\Controllers\KycController;

use \Log;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserKyc\UserBankInfo;
use App\Models\UserKyc\UserDocuments;
use App\Models\UserKyc\UserExtraInfo;
use App\Models\UserKyc\UserKycTrack;
use App\Models\UserKyc\UserPersonalInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;



class KycController extends Controller
{

    // handling documents starts here
    public function createOrUpdateDocs(Request $request)
    {
        $userId = null;
        $employeeId = null;

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

        try {
            DB::beginTransaction();

            $data = $this->runDocCreateOrUpdate($request, $userId);

            // Optionally update KYC tracking right after
            $kycUpdate = $this->updateDocumentsKycStatus($userId, $employeeId);

            DB::commit();

            return response()->json([
                'status' => 200,
                'data' => $data,
                'kycUpdate' => $kycUpdate,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 500,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    private function runDocCreateOrUpdate($request, $userId)
    {
        $validator = Validator::make($request->all(), [
            'idProofFront'    => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'idProofBack'     => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'panCard'         => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'cancelledCheque' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'electricityBill' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'idProofNumber'   => 'nullable|string|max:255',
            'panNumber'       => 'nullable|string|max:255',
            'consumerNumber'  => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->errors()
            ]);
        }

        $fileFields = [
            'idProofFront' => 'id_proof_front',
            'idProofBack' => 'id_proof_back',
            'panCard' => 'pan_card',
            'cancelledCheque' => 'cancelled_cheque',
            'electricityBill' => 'electricity_bill',
        ];

        $inputFields = [
            'idProofNumber' => 'id_proof_number',
            'panNumber' => 'pan_number',
            'consumerNumber' => 'consumer_number',
        ];

        $data = [];
        $existing = UserDocuments::where('user_id', $userId)->first();

        foreach ($fileFields as $reqKey => $dbKey) {
            if ($request->hasFile($reqKey)) {
                $file = $request->file($reqKey);
                if ($file->getMimeType() === 'application/pdf' || str_contains($file->getMimeType(), 'image')) {
                    $data[$dbKey] = $this->moveFileWithTimestamp($file, $existing->$dbKey ?? null);
                } else {
                    return response()->json([
                        'status' => 422,
                        'errors' => [$reqKey => 'Invalid file type.']
                    ]);
                }
            } else {
                $data[$dbKey] = $existing->$dbKey ?? null;
            }
        }

        foreach ($inputFields as $reqKey => $dbKey) {
            $data[$dbKey] = $request->input($reqKey, $existing->$dbKey ?? null);
        }

        $userData = UserDocuments::updateOrCreate(
            ['user_id' => $userId],
            $data
        );

        return $userData;
    }

    private function moveFileWithTimestamp($file, $oldFilePath = null)
    {
        if ($oldFilePath) {
            $oldFullPath = public_path(trim($oldFilePath, '/'));
            if (file_exists($oldFullPath)) unlink($oldFullPath);
        }

        $safeName = preg_replace('/[^A-Za-z0-9_\.-]/', '_', $file->getClientOriginalName());
        $fileName = time() . '_' . uniqid() . '_' . $safeName;

        $file->move(public_path('user_docs'), $fileName);

        return 'user_docs/' . $fileName;
    }

    // handling document ends here



    // handling personal info starts here
    public function createOrUpdatePersonalInfo(Request $request)
    {
        $userId = null;
        $employeeId = null;

        $user = $request->user();
    
        // The code below runs only for employees; customers will skip it
        if ($user->role !== 'user') {

            $userId = $request->input('userId');
            // $userId = 3; // temp for testing

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

        $userData = UserPersonalInfo::updateOrCreate(
            ['user_id' => $userId],
            [
                'employee_id'       => $employeeId,
                'first_name'        => $request->input('first_name'),
                'middle_name'       => $request->input('middle_name'),
                'last_name'         => $request->input('last_name'),
                'gender'            => $request->input('gender'),
                'dob'               => $request->input('dob'),
                'address'           => $request->input('address'),
                'city'              => $request->input('city'),
                'state'             => $request->input('state'),
                'pincode'           => $request->input('pincode'),
                'phone'             => $request->input('phone'),
                'alternative_phone' => $request->input('alternative_phone'),
            ]
        );

        $this->updatePersonalInfoKycStatus($userId, $employeeId);

        return response()->json([
            'status' => 200,
            'data' => $userData,
        ]);

    }
    // ends here



    // handling bank info starts here
    public function createOrUpdateBankInfo(Request $request)
    {
        
            $userId = null;
            $employeeId = null;
              
            $user = $request->user();

            // The code below runs only for employees; customers will skip it
            if ($user->role !== 'user') {
                   
                $userId = $request->input('userId');
                // $userId = 3; // temp for testing

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

            $userData = UserBankInfo::updateOrCreate(
                ['user_id' => $userId],
                [
                    'account_holder_name' => $request->input('account_holder_name'),
                    'bank_name'           => $request->input('bank_name'),
                    'branch_name'         => $request->input('branch_name'),
                    'account_number'      => $request->input('account_number'),
                    'ifsc_code'           => $request->input('ifsc_code'),
                ]
            );

            $this->updateBankInfoKycStatus($userId, $employeeId);

            return response()->json([
                'status' => 200,
                'data' => $userData,
            ]);

    }
    // ends here



    // handling extra info starts here
    public function createOrupdateExtraInfo(Request $request)
    {
            $userId = null;
            $employeeId = null;

            $user = $request->user();
            // The code below runs only for employees; customers will skip it
            if ($user->role !== 'user') {

                $userId = $request->input('userId');
                // $userId = 3; // temp for testing

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

            $userData = UserExtraInfo::updateOrCreate(
                ['user_id' => $userId],
                [
                    'installation_address' => $request->boolean('installation_address'),
                    'village'              => $request->input('village'),
                    'landmark'             => $request->input('landmark'),
                    'district'             => $request->input('district'),
                    'pincode'              => $request->input('pincode'),
                    'state'                => $request->input('state'),
                    'proposed_capacity'    => $request->input('proposed_capacity'),
                    'plot_type'            => $request->input('plot_type'),
                ]
            );


            $this->updateExtraInfoKycStatus($userId, $employeeId);

            return response()->json([
                'status' => 200,
                'data' => $userData,
            ]);

    }
    // ends here






    // kyc status checking starts here
    private function updateDocumentsKycStatus($userId, $employeeId = null)
    {
      
        $docs = UserDocuments::where('user_id', $userId)->first();
        if (!$docs) return;

  
        $fieldsToCheck = [
            'id_proof_front',
            'id_proof_back',
            'id_proof_number',
            'pan_card',
            'pan_number',
            'cancelled_cheque',
            'electricity_bill',
            'consumer_number',
        ];

        $allFilled = collect($fieldsToCheck)->every(fn($field) => !empty($docs->$field));

        // Note: This works only when all user columns are filled
        if ($allFilled) {
            UserKycTrack::updateOrCreate(
                ['user_id' => $userId],
                
                [
                    'user_doc_status' => true, 
                    'employee_id' => $employeeId
                ]
            );

            $kyc = UserKycTrack::where('user_id', $userId)->first();
            if (!$kyc) return;

            // Collect all boolean status columns
            $statusColumns = collect($kyc->getFillable())
                ->reject(fn($col) => in_array($col, ['id', 'user_id', 'employee_id', 'user_kyc_status']));

            // Determine current status dynamically
            $allSubmitted = $statusColumns->every(fn($col) => $kyc->$col === 1);

            // Always update to reflect reality
            $kyc->update([
                'user_kyc_status' => $allSubmitted ? 'completed' : 'pending',
                'employee_id' => $employeeId
            ]);


        }
    }

    private function updatePersonalInfoKycStatus($userId, $employeeId = null)
    {

        $personalInfo = UserPersonalInfo::where('user_id', $userId)->first();
        if (!$personalInfo) return;

        $fieldsToCheck = [
            'first_name',
            'last_name',
            'gender',
            'dob',
            'address',
            'city',
            'state',
            'pincode',
            'phone',
        ];

        $allFilled = collect($fieldsToCheck)->every(fn($field) => !empty($personalInfo->$field));

        if ($allFilled) {
           
            UserKycTrack::updateOrCreate(
                ['user_id' => $userId],
                [
                    'user_profile_status' => true,
                    'employee_id' => $employeeId
                ]
            );

            $kyc = UserKycTrack::where('user_id', $userId)->first();
            if (!$kyc) return;

            $statusColumns = collect($kyc->getFillable())
                ->reject(fn($col) => in_array($col, ['id','user_id','employee_id','user_kyc_status']));

            $allSubmitted = $statusColumns->every(fn($col) => $kyc->$col === 1);

            $kyc->update([
                'user_kyc_status' => $allSubmitted ? 'completed' : 'pending',
                'employee_id' => $employeeId
            ]);
        }
    }

    private function updateBankInfoKycStatus($userId, $employeeId = null)
    {
       
        $bankInfo = UserBankInfo::where('user_id', $userId)->first();
        if (!$bankInfo) return;

        $fieldsToCheck = [
            'account_holder_name',
            'bank_name',
            'branch_name',
            'account_number',
            'ifsc_code',
        ];


        $allFilled = collect($fieldsToCheck)->every(fn($field) => !empty($bankInfo->$field));

        if ($allFilled) {
           
            UserKycTrack::updateOrCreate(
                ['user_id' => $userId],
                [
                    'user_bank_status' => true,
                    'employee_id' => $employeeId
                ]
            );

            $kyc = UserKycTrack::where('user_id', $userId)->first();
            if (!$kyc) return;

            $statusColumns = collect($kyc->getFillable())
                ->reject(fn($col) => in_array($col, ['id','user_id','employee_id','user_kyc_status']));

            $allSubmitted = $statusColumns->every(fn($col) => $kyc->$col === 1);

            $kyc->update([
                'user_kyc_status' => $allSubmitted ? 'completed' : 'pending',
                'employee_id' => $employeeId
            ]);
        }
    }

    private function updateExtraInfoKycStatus($userId, $employeeId = null)
    {
        $extraInfo = UserExtraInfo::where('user_id', $userId)->first();
        if (!$extraInfo) return;

        $fieldsToCheck = [
            'installation_address',
            'village',
            'landmark',
            'district',
            'pincode',
            'state',
            'proposed_capacity',
            'plot_type',
        ];

        $allFilled = collect($fieldsToCheck)->every(function ($field) use ($extraInfo) {
                        return isset($extraInfo->$field) && ($extraInfo->$field !== '' && 
                        $extraInfo->$field !== null);
                    });

        if ($allFilled) {
           
            UserKycTrack::updateOrCreate(
                ['user_id' => $userId],
                [
                    'user_extra_status' => true,
                    'employee_id' => $employeeId
                ]
            );

            $kyc = UserKycTrack::where('user_id', $userId)->first();
            if (!$kyc) return;

            $statusColumns = collect($kyc->getFillable())
                ->reject(fn($col) => in_array($col, ['id','user_id','employee_id','user_kyc_status']));

            $allSubmitted = $statusColumns->every(fn($col) => $kyc->$col === 1);

            $kyc->update([
                'user_kyc_status' => $allSubmitted ? 'completed' : 'pending',
                'employee_id' => $employeeId
            ]);
        }
    }



}

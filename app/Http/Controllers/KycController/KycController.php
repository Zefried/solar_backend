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



namespace App\Http\Controllers\KycController;

use \Log;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserKyc\UserDocuments;
use App\Models\UserKyc\UserKycTrack;
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

            // Check if user exists in DB
            $targetUser = User::find($userId);

            if (!$targetUser) {
                return response()->json([
                    'status'  => 404,
                    'message' => 'Selected user not found in the system. please register user first!'
                ]);
            }

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
            'idProofFront'    => 'nullable|file|mimes:jpg,jpeg,png,pdf',
            'idProofBack'     => 'nullable|file|mimes:jpg,jpeg,png,pdf',
            'panCard'         => 'nullable|file|mimes:jpg,jpeg,png,pdf',
            'cancelledCheque' => 'nullable|file|mimes:jpg,jpeg,png,pdf',
            'electricityBill' => 'nullable|file|mimes:jpg,jpeg,png,pdf',
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
        // This is where we use userId to fetch all existing information
        $existing = UserDocuments::where('user_id', $userId)->first();

        foreach ($fileFields as $reqKey => $dbKey) {
          $data[$dbKey] = $request->hasFile($reqKey)
            ? $this->moveFileWithTimestamp($request->file($reqKey), $existing->$dbKey ?? null)
            : ($existing->$dbKey ?? null);
        } // 4 edge case

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
        try {
            if ($oldFilePath) {
                $oldFullPath = public_path(trim($oldFilePath, '/'));
                if (file_exists($oldFullPath)) {
                    unlink($oldFullPath);
                }
            }

            $safeName = preg_replace('/[^A-Za-z0-9_\.-]/', '_', $file->getClientOriginalName());
            $fileName = time() . '_' . uniqid() . '_' . $safeName;

            $file->move(public_path('user_docs'), $fileName);

            return 'user_docs/' . $fileName;
        } catch (\Exception $e) {
            // Optional: log error
            return response()->json($e->getMessage());
    
        }
    }
    // handling document ends here






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





}

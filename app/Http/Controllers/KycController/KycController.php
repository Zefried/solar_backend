<?php

namespace App\Http\Controllers\KycController;

use \Log;
use App\Http\Controllers\Controller;
use App\Models\UserKyc\UserDocuments;
use App\Models\UserKyc\UserKycTrack;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class KycController extends Controller
{

    // handling documents starts here
    public function createOrUpdateDocs(Request $request)
    {
        // $user = $request->user();

        // if ($user->role !== 'user') {
        //     $userId = $request->input('userId');
        //     if (!$userId) {
        //         return response()->json([
        //             'status'  => 422,
        //             'message' => 'Please select a user you want to upload documents on behalf of. If user does not exist, add the user first.'
        //         ]);
        //     }
        //     $employeeId = $user->id;
        // } else {
        //     $userId = $user->id;
        //     $employeeId = null;
        // }

        // For testing, keep hardcoded IDs
        $userId = 3;
        $employeeId = 4;

        $data = $this->runDocCreateOrUpdate($request, $userId);

        // Optionally update KYC tracking right after
        // $this->updateDocumentsKycStatus($userId, $employeeId);

        return response()->json([
            'status' => 200,
            'data' => $data
        ]);
    }

    private function runDocCreateOrUpdate($request)
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

        $existing = UserDocuments::where('user_id', 3)->first();

        foreach ($fileFields as $reqKey => $dbKey) {
          $data[$dbKey] = $request->hasFile($reqKey)
            ? $this->moveFileWithTimestamp($request->file($reqKey), $existing->$dbKey ?? null)
            : ($existing->$dbKey ?? null);
        } // 4 edge case

        foreach ($inputFields as $reqKey => $dbKey) {
            $data[$dbKey] = $request->input($reqKey, $existing->$dbKey ?? null);
        }

        $userData = UserDocuments::updateOrCreate(
            ['user_id' => 3],
            $data
        );

        return response()->json([
            'status' => 200,
            'message' => 'Documents saved successfully',
            'data' => $userData
        ]);
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














    public function updateKycTracking()
    {
        try {
            $type = 'documents';

            if ($type === 'documents') {
                // Testing IDs
                $this->updateDocumentsKycStatus(3, 4);
            }

            // Extend for other types like 'bank', 'personal', 'extra'

        } catch (\Exception $e) {
            return response()->json($e->getMessage());
        }
    }

    private function updateDocumentsKycStatus($userId, $employeeId)
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

        if ($allFilled) {
            UserKycTrack::updateOrCreate(
                ['user_id' => $userId],
                
                [
                    'user_doc_status' => true, 
                    'employee_id' => $employeeId
                ]
            );
        }
    }





}

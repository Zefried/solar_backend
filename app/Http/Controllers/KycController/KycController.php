<?php

namespace App\Http\Controllers\KycController;

use App\Http\Controllers\Controller;
use App\Models\UserKyc\UserDocuments;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class KycController extends Controller
{
  
    // evening task 
    public function uploadDoc(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'idProofFront'    => 'nullable|file|mimes:jpg,jpeg,png,pdf',
            'idProofBack'     => 'nullable|file|mimes:jpg,jpeg,png,pdf',
            'idProofNumber'   => 'nullable|string|max:255',
            'panCard'         => 'nullable|file|mimes:jpg,jpeg,png,pdf',
            'panNumber'       => 'nullable|string|max:255',
            'cancelledCheque' => 'nullable|file|mimes:jpg,jpeg,png,pdf',
            'electricityBill' => 'nullable|file|mimes:jpg,jpeg,png,pdf',
            'consumerNumber'  => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ]);
        }

        $files = ['idProofFront','idProofBack','panCard','cancelledCheque','electricityBill'];
        $data = [];

        // Only include text fields if present
        if ($request->filled('idProofNumber')) $data['idProofNumber'] = $request->idProofNumber;
        if ($request->filled('panNumber')) $data['panNumber'] = $request->panNumber;
        if ($request->filled('consumerNumber')) $data['consumerNumber'] = $request->consumerNumber;

        // Handle file uploads with timestamp to avoid collisions
        foreach ($files as $file) {
            if ($request->hasFile($file)) {
                $originalName = $request->file($file)->getClientOriginalName();
                $fileName = time().'_'.$originalName;
                $request->file($file)->move(public_path('user_docs'), $fileName);
                $data[$file] = 'user_docs/'.$fileName;
            }
        }

        $userId = $request->user()->id;

        // Create new or update existing record
        $userData = UserDocuments::updateOrCreate(
            ['user_id' => $request->user()->userId],
            $data
        );

        return response()->json([
            'status' => 200,
            'message' => 'Documents uploaded successfully',
            'data' => $userData
        ]);
    }


}

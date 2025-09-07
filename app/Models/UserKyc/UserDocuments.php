<?php

namespace App\Models\UserKyc;

use App\Models\UserKyc\UserKycTrack;
use Illuminate\Database\Eloquent\Model;

class UserDocuments extends Model
{
    
     protected $fillable = [
        'user_id',
        'id_proof_front',
        'id_proof_back',
        'id_proof_number',
        'pan_card',
        'pan_number',
        'cancelled_cheque',
        'electricity_bill',
        'consumer_number',
    ];

    public function kycTrack() { return $this->belongsTo(UserKycTrack::class, 'user_id', 'user_id'); }

}

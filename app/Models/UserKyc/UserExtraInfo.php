<?php

namespace App\Models\UserKyc;

use App\Models\UserKyc\UserKycTrack;
use Illuminate\Database\Eloquent\Model;

class UserExtraInfo extends Model
{
    
    protected $fillable = [
        'user_id',
        'installation_address', // boolean true/false
        'village',
        'landmark',
        'district',
        'pincode',
        'state',
        'proposed_capacity',   // string
        'plot_type',           // enum: residential, commercial
    ];

    public function kycTrack() { return $this->belongsTo(UserKycTrack::class, 'user_id', 'user_id'); }

}

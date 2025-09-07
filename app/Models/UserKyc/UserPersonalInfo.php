<?php

namespace App\Models\UserKyc;

use App\Models\UserKyc\UserKycTrack;
use Illuminate\Database\Eloquent\Model;

class UserPersonalInfo extends Model
{
    protected $fillable = [
        'user_id',
        'first_name',
        'middle_name',
        'last_name',
        'gender',
        'dob',
        'address',
        'city',
        'state',
        'pincode',
        'phone',
        'alternative_phone',
    ];
    
    public function kycTrack() { return $this->belongsTo(UserKycTrack::class, 'user_id', 'user_id'); }

}

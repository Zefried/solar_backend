<?php

namespace App\Models\UserKyc;

use App\Models\UserKyc\UserBankInfo;
use App\Models\UserKyc\UserDocuments;
use App\Models\UserKyc\UserExtraInfo;
use App\Models\UserKyc\UserPersonalInfo;
use Illuminate\Database\Eloquent\Model;

class UserKycTrack extends Model
{
    protected $fillable = [
        'user_id',
        'employee_id',
        'user_doc_status',      // boolean: true/false
        'user_profile_status',  // boolean: true/false
        'user_bank_status',     // boolean: true/false
        'user_extra_status',    // boolean: true/false
        'user_kyc_status',      // enum or string: pending/completed
    ];

    // In UserKycTrack
    public function documents() { return $this->hasOne(UserDocuments::class, 'user_id', 'user_id'); }
    public function personalInfo() { return $this->hasOne(UserPersonalInfo::class, 'user_id', 'user_id'); }
    public function bankInfo() { return $this->hasOne(UserBankInfo::class, 'user_id', 'user_id'); }
    public function extraInfo() { return $this->hasOne(UserExtraInfo::class, 'user_id', 'user_id'); }

}

<?php

namespace App\Models\UserKyc;

use App\Models\UserKyc\UserKycTrack;
use Illuminate\Database\Eloquent\Model;

class UserBankInfo extends Model
{
    protected $fillable = [
        'user_id',
        'account_holder_name',
        'account_number',
        'bank_name',
        'ifsc_code',
        'branch_name',
    ];

    public function kycTrack() { return $this->belongsTo(UserKycTrack::class, 'user_id', 'user_id'); }

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contribution extends Model
{
    use HasFactory;

    protected $fillable = [
        'household_id',
        'amount',
        'photo',
        'status',
        'admin_verified',
        'reference_number',
        'created_at',
        'updated_at'
    ];

    public function household()
    {
        return $this->belongsTo(Household::class);
    }
}

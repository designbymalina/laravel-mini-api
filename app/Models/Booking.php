<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'customer_name',
        'customer_email',
        'slot_start',
        'slot_end',
        'status'
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'slot_start' => 'datetime',
        'slot_end' => 'datetime'
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    protected $fillable = [
        'external_id',
        'created_at_source',
        'first_name',
        'last_name',
        'phone',
        'email',
        'city',
        'source',
        'utm_campaign',
        'product',
        'budget_uah',
        'status',
        'manager',
        'comment',
        'next_contact_at',
    ];

    protected $casts = [
        'created_at_source' => 'datetime',
        'next_contact_at'   => 'datetime',
        'budget_uah'        => 'decimal:2',
    ];
}

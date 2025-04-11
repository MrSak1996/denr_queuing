<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class QueueLogs extends Model
{
    use HasFactory;

    protected $table = 'queue_logs';

    protected $fillable = [
        'id', 'queue_id', 'served_by', 'served_at', 'remarks'
    ];
}

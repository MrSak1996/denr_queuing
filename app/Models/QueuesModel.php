<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QueuesModel extends Model
{
    use HasFactory;

    protected $table = 'queues';

    protected $fillable = [
        'client_id',
        'priority_level_id',
        'service_id',
        'counter_id',
        'queue_number',
        'status',
        'queued_at',
        'called_at',
    ];

    // Relationship to Client
    public function client()
    {
        return $this->belongsTo(Clients::class);
    }

    // Relationship to Service
    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    // Relationship to ServiceCounter
    public function serviceCounter()
    {
        return $this->belongsTo(ServiceCounter::class);
    }
}

<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceCounter extends Model
{
    use HasFactory;

    protected $table = 'service_counters';

    protected $fillable = [ 
        'counter_name',
        'assigned_service_id',
    ];

    // Relationship to Service
    public function service()
    {
        return $this->belongsTo(Service::class, 'assigned_service_id');
    }

    // Relationship to Queue
    public function queues()
    {
        return $this->hasMany(QueuesModel::class);
    }
}

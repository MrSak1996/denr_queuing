<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Clients extends Model
{
    use HasFactory;

    protected $table = 'clients';

    protected $fillable = [
        'id',
        'full_name',
        'contact_number',
    ];

    // Relationship to PriorityLevel
    public function priorityLevel()
    {
        return $this->belongsTo(PriorityLevel::class);
    }

    // Relationship to Queue
    public function queues()
    {
        return $this->hasMany(QueuesModel::class);
    }
}

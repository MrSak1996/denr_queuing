<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $table = 'services';

    protected $fillable = [
        'name',
        'description',
    ];

    // Relationship to Queue
    public function queues()
    {
        return $this->hasMany(QueuesModel::class);
    }
}

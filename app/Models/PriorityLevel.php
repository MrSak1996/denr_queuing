<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class PriorityLevel extends Model
{
    use HasFactory;

    protected $table = 'priority_levels';

    protected $fillable = [
        'level_name',
    ];

    // Relationship to Client
    public function clients()
    {
        return $this->hasMany(Client::class);
    }
}

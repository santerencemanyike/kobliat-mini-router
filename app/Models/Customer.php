<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = ['external_id', 'name'];

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeeklyAssignment extends Model
{
    use HasFactory;

    protected $fillable = ['week_start_date', 'person_id', 'room_id'];

    public function person()
    {
        return $this->belongsTo(Person::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}

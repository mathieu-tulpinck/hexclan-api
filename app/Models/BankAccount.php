<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    use HasFactory;

    // Required because primary key is uuid.
    //public $incrementing = false;

    protected $guarded = [];

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function getManagers()
    {
        $managers = collect();
        foreach($this->events as $event) {
            $managers->push($event->user);
        }
        return $managers;
    }

    // Multiple managers might manage a bank acccount.
    public function isManagedBy($userId) {
        $managers = $this->getManagers();
        if (isset($managers)) {
            $managers->pluck('id')->contains($userId);
        } else {
            return false;
        }
    }
}

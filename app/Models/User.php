<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\NewAccessToken;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    // Required because primary key is uuid.
    public $incrementing = false;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'string',
        'is_active' => 'boolean',
        'is_admin' => 'boolean',
        'pin_code_timestamp' => 'datetime'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'is_active',
        'is_admin',
        'pin_code',
        'pin_code_timestamp'
    ];

    /**
     * This method returns a collection of pivot model instances.
     * @return mixed
     */
    public function roles()
    {
        return $this->hasMany(EventUser::class);
    }

    // The events that belong to the user.
    public function events()
    {
        return $this->belongsToMany(Event::class)
            ->using(EventUser::class)
            ->withPivot('role');
    }

    /**
     * Accessor method which returns all roles that belong to the user. Use $event->pivot->role / $user->pivot->role to access role with respect to specific event / user.
     * @return array
     */
    public function getRoles()
    {
        return $this->roles()->pluck('role');
    }

    /**
     * Accessor method which returns all events that belong to the user.
     * @return array
     */
    public function getEvents()
    {
        return $this->events()->pluck('event_id');
    }

    // Overrides createToken method from laravel/sanctum.
    public function createToken(string $name, $identifier, array $abilities = ['*'])
    {
        $token = $this->tokens()->create([
            'name' => $name,
            'identifier' => $identifier,
            'token' => hash('sha256', $plainTextToken = Str::random(40)),
            'abilities' => $abilities,
        ]);

        return new NewAccessToken($token, $token->getKey() . '|' . $plainTextToken);
    }
}

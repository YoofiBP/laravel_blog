<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phoneNo',
        'address'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'isAdmin'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'isAdmin' => 'boolean'
    ];

    protected $attributes = [
        'isAdmin' => false,
    ];

    public function posts() {
        return $this->hasMany('App\Models\Post');
    }

    public function comments() {
        return $this->hasMany('App\Models\Comment');
    }

    public static function deleteUser($user) {
        try {
            $user->tokens()->delete();
            $user->delete();
            return;
        }catch (\Exception $e){
            throw new \Exception("Delete Failed");
        }
    }

    public function setPasswordAttribute($value) {
        $this->attributes['password'] = password_hash($value, PASSWORD_DEFAULT);
    }

    public function setPhonenoAttribute($value) {
        $cleanNumber = preg_replace("/[^0-9]/","", $value);
        $newNumber = "233". substr($cleanNumber, -9);
        $this->attributes['phoneNo'] = $newNumber;
    }

    public static function getUserWithCredentials($email, $password){
        $user = User::where('email', $email)->first();
        if (isset($user)){
            if(password_verify($password, $user['password'])){
                return $user;
            }
        }
        throw new \Exception("Unable to Login");

    }

    public function generateAuthToken() {
        $user = $this;
        if($user->isAdmin){
            $token = $user->createToken('login-token', ['role:admin']);
        } else {
            $token = $user->createToken('token-name', ['role:user']);
        }
        return $token->plainTextToken;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class SettingLogin extends Model
{
    use HasFactory;

    protected $fillable = [
        'username',
        'password',
        'name',
        'is_active'
    ];

    protected $hidden = [
        'password'
    ];

    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = Hash::make($value);
    }

    public function checkPassword($password)
    {
        return Hash::check($password, $this->password);
    }
}
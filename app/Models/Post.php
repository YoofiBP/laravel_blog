<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'post_title',
        'post_description',
        'post_body'
    ];

    protected $primaryKey = 'id';

    public function user() {
        return $this->belongsTo('App\Models\User', 'user_id', 'id');
    }

    public function comments() {
        return $this->hasMany('App\Models\Comment');
    }
}

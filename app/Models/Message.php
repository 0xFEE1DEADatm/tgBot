<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;
    protected $table = 'message';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'image',
        'title',
        'description',
        'buttons_json',
    ]; 
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    // config gerais do model
    // relações 
    // 1_para_1
    // 1_para_muitos
    // muitos_para_muitos

    protected $fillable = [
        'title', 'body'
    ];

}



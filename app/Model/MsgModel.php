<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class MsgModel extends Model
{
    public $table = 'msg';
    protected $primaryKey = 'mid';
}

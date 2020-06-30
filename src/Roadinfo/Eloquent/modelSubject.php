<?php

namespace Roadinfo\Eloquent;

use Illuminate\Database\Eloquent\Model;

class modelSubject extends Model
{
    protected $table = "subject";
    public $primaryKey = "keysubject";
    protected $connection = 'users';
}


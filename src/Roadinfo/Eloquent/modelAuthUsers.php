<?php

namespace Roadinfo\Eloquent;

use Illuminate\Database\Eloquent\Model;

class modelAuthUsers extends Model
{
    protected $table = "user";
    public $primaryKey = "keyuser";
    protected $connection = 'users';

    public function Subject()
    {
        return $this->hasOne('Roadinfo\Eloquent\modelSubject', 'keysubject', 'keyorg')->get()->first();
    }
}


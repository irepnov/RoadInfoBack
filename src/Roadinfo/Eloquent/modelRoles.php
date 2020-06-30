<?php

namespace Roadinfo\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class modelRoles extends Model
{
    protected $table = "roles";
    public $primaryKey = "id";
    public $incrementing = true;
    public $timestamps = false;
    protected $connection = 'roadinfo';
}


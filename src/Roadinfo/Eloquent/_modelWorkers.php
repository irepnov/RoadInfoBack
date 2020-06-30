<?php

namespace Roadinfo\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class modelWorkers extends Model
{
    use SoftDeletes;
    protected $dates = ['deleted_at'];
    protected $table = "workers";
    public $primaryKey = "id";
    public $incrementing = true;
    public $timestamps = true;
    protected $connection = 'companies';
}


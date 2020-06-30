<?php

namespace Roadinfo\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class modelTest_layer_file extends Model
{
    use SoftDeletes;
    protected $dates = ['deleted_at'];
    protected $table = "test_layer_file";
    public $primaryKey = "id";
    public $incrementing = true;
    public $timestamps = true;
    protected $connection = 'roadinfo';
}


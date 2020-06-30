<?php

namespace Roadinfo\Eloquent;

use Kalnoy\Nestedset\NodeTrait;
use Illuminate\Database\Eloquent\Model;

class modelJ extends Model
{
    protected $table = "j";
    public $primaryKey = "id";
    public $incrementing = false;
    public $timestamps = false;
    protected $connection = 'roadinfo';
}


<?php

namespace Roadinfo\Eloquent;

use Illuminate\Database\Eloquent\Model;

class modelMunobr extends Model
{
    protected $table = "regions";
    public $primaryKey = "id";
    protected $connection = 'general';

    public function Objects()
    {
        return $this->hasMany('Roadinfo\Eloquent\modelObjects', 'munobr_id', 'id')->get();
    }
    public function ObjectsId() {
        return $this->hasMany('Roadinfo\Eloquent\modelObjects', 'munobr_id', 'id')->select(['id'])->get();
    }
}


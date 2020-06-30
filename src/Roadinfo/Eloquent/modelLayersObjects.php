<?php

namespace Roadinfo\Eloquent;

use Illuminate\Database\Eloquent\Model;

class modelLayersObjects extends Model
{
    protected $table = "layer_objects";
    public $primaryKey = "id";
    public $incrementing = true;
    public $timestamps = true;
    protected $connection = 'roadinfo';

    public function Object()
    {
        return $this->hasOne('Roadinfo\Eloquent\modelObjects', 'id', 'object_id')->get()->first();
    }
    public function Layer()
    {
        return $this->hasOne('Roadinfo\Eloquent\modelLayers', 'id', 'layer_id')->get()->first();
    }
}


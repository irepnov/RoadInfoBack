<?php

namespace Roadinfo\Eloquent;

use Illuminate\Database\Eloquent\Model;

class modelObjects extends Model
{
    protected $table = "objects";
    public $primaryKey = "id";
    public $incrementing = false;
    public $timestamps = false;
    protected $connection = 'distances';

    public function Munobraz()
    {
        return $this->hasOne('Roadinfo\Eloquent\modelMunobr', 'id', 'munobr_id')->get()->first();
    }
    public function Attachments()
    {
        return $this->hasMany('Roadinfo\Eloquent\modelAttachments', 'object_id', 'id')->get();
    }
}


<?php

namespace Roadinfo\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class modelAttachments extends Model
{
    use SoftDeletes;
    protected $dates = ['deleted_at'];
    protected $table = "attachments";
    public $primaryKey = "id";
    public $incrementing = true;
    public $timestamps = true;
    protected $connection = 'roadinfo';

    public function Object()
    {
        return $this->hasOne('Roadinfo\Eloquent\modelObjects', 'id', 'object_id')->get()->first();
    }
    public function User()
    {
        return $this->hasOne('Roadinfo\Eloquent\modelUsers', 'id', 'user_id')->get()->first();
    }
}


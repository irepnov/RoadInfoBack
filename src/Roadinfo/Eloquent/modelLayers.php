<?php

namespace Roadinfo\Eloquent;

use Kalnoy\Nestedset\NodeTrait;
use Illuminate\Database\Eloquent\Model;

class modelLayers extends Model
{
    use NodeTrait;
    protected $table = "layers";
    public $primaryKey = "id";
    public $incrementing = false;
    public $timestamps = false;
    protected $connection = 'roadinfo';

    public function getLftName()
    {
        return 'lft';
    }
    public function getRgtName()
    {
        return 'rgt';
    }
    public function getParentIdName()
    {
        return 'parent_id';
    }
    public function setParentAttribute($value)
    {
        $this->setParentIdAttribute($value);
    }
}


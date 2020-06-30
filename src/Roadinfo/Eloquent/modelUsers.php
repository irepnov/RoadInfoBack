<?php

namespace Roadinfo\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class modelUsers extends Model
{
    use SoftDeletes;
    protected $dates = ['deleted_at'];
    protected $table = "users";
    public $primaryKey = "id";
    public $incrementing = true;
    public $timestamps = true;
    protected $connection = 'roadinfo';

    public function AuthUser()
    {
        return $this->hasOne('Roadinfo\Eloquent\modelAuthUsers', 'keyuser', 'auth_user_id')->get()->first();
    }
    public function Role()
    {
        return $this->hasOne('Roadinfo\Eloquent\modelRoles', 'id', 'role_id')->get()->first();
    }
}


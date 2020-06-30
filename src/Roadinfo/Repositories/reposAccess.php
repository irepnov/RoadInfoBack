<?php

namespace Roadinfo\Repositories;

use Roadinfo\Eloquent\modelAuthUsers;
use Roadinfo\Eloquent\modelRoles;
use Roadinfo\Eloquent\modelUsers;
use Roadinfo\Eloquent\modelWorkers;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Carbon;
use Roadinfo\Eloquent\modelMunobr;


class reposAccess
{
    public function __construct($user)
    {
        ini_set('max_execution_time', 300);
        date_default_timezone_set('Europe/Moscow');

        $this->user = $user;
        if (empty($this->user) and strtoupper($GLOBALS['settings']['debug']) == 'TRUE') {
            $responseAPI = '{"id": "1", "keyuser":"617","login":"Igor","namefull":"Debug Debug Debug","email":"irepnov@gmail.com", "layers": [40011,40012,40013,40014,40015,   30501,30502,30503,30504,   221,325,176,336], "munobrs": [200,201,203,205,209,211,212,213], "role_id": 1}';
            $this->user = json_decode($responseAPI);
        }
        if (empty($this->user)) throw new \Exception('пользователь не авторизован');
    }

    public function getAccess($user_id)
    {
        if (empty($user_id)){
            $itemUser = (new modelUsers())::where('auth_user_id', '=', $this->user->keyuser)->first();
        }else {
            $itemUser = (new modelUsers())::where('id', '=', $user_id)->first();
        }

        if (empty($itemUser)) throw new \Exception('пользователь не идентифицирован');

        $user = ['id' => $itemUser->id,
            // 'worker_id' => $itemUser->worker_id,
            'auth_user_id' => $itemUser->auth_user_id,
            'keyuser' => $itemUser->auth_user_id,
            'role_id' => $itemUser->role_id,
            //'namefull' => $itemUser->Worker()->fam . ' ' . $itemUser->Worker()->im . ' ' . $itemUser->Worker()->ot
            'namefull' => empty($itemUser->AuthUser()) ? null : $itemUser->AuthUser()->name
        ];

        $data_mun = Capsule::connection('roadinfo')->table('user_munobrs')->where('user_id', '=', $user['id'])->select('munobr_id')->pluck('munobr_id')->toArray();
        $data_lay = Capsule::connection('roadinfo')->table('user_layers')->where('user_id', '=', $user['id'])->select('layer_id')->pluck('layer_id')->toArray();
        $user['munobrs'] = $data_mun;
        $user['layers'] = $data_lay;

        $this->user = (object)$user;

        return $this->user;
    }

    public function getActiveUserInfo()
    {
        if (!empty($this->user)) {
            return [
                'success' => true,
                'statuscode' => 200,
                'data' => $this->user
            ];
        } else {
            return [
                'success' => false,
                'statuscode' => 400,
                'data' => null
            ];
        }
    }

    //получить списко Муниципальных образование
    public function getMunobrAl()
    {
        try {
            $items = [];
            $itemModel = new modelMunobr();
            $data = $itemModel::select('id', 'name')->orderBy('name')->get();
            foreach ($data as $item) {
                $items[] = ['id' => $item->id, 'name' => $item->name];
            }
            $itemModel = null;
            return ['success' => true, 'statuscode' => 200, 'totalcount' => count($items), 'data' => $items];
        } catch (\Exception $ee) {
            return ['success' => false, 'statuscode' => 400, 'message' => $ee->getMessage()];
        }
    }

    //получить списко Муниципальных образование
    public function getRoles()
    {
        try {
            $items = [];
            $itemModel = new modelRoles();
            $data = $itemModel::select('id', 'name')->orderBy('name')->get();
            foreach ($data as $item) {
                $items[] = ['id' => $item->id, 'name' => $item->name];
            }
            $itemModel = null;
            return ['success' => true, 'statuscode' => 200, 'totalcount' => count($items), 'data' => $items];
        } catch (\Exception $ee) {
            return ['success' => false, 'statuscode' => 400, 'message' => $ee->getMessage()];
        }
    }

    //получить списко Муниципальных образование
    public function getAuthUsers()
    {
        try {
            $items = [];
            $itemModel = new modelAuthUsers();

            $data = $itemModel
                ::where("active", "=", "1")
                ->whereNotNull('email')
                ->join('subject', 'user.keyorg', '=', 'subject.keysubject')
                ->orderBy('subject.fullname')
                ->orderBy('user.name')
                ->select('user.keyuser as keyuser', 'user.login as login', 'user.name as name', 'subject.fullname as company')
                ->get();

            foreach ($data as $item) {
                $items[] = ['id' => $item->keyuser, "login" => $item->login, "name" => $item->name, "company" => $item->company];
            }
            $itemModel = null;
            return ['success' => true, 'statuscode' => 200, 'totalcount' => count($items), 'data' => $items];
        } catch (\Exception $ee) {
            return ['success' => false, 'statuscode' => 400, 'message' => $ee->getMessage()];
        }
    }

    //получить списко Муниципальных образование
    public function getUsersList()
    {
        try {
            $items = [];
            $itemModel = new modelUsers();
            $data = $itemModel::whereNull('deleted_at')->get();
            foreach ($data as $item) {
                $data_mun = Capsule::connection('roadinfo')->table('user_munobrs')->where('user_id', '=', $item->id)->select('munobr_id')->pluck('munobr_id')->toArray();
                $data_lay = Capsule::connection('roadinfo')->table('user_layers')->where('user_id', '=', $item->id)->select('layer_id')->pluck('layer_id')->toArray();
                $items[] = ['id' => $item->id, /*"worker_id" => $item->worker_id,*/
                    "auth_user_id" => $item->auth_user_id, "role_id" => $item->role_id, "munobrs" => $data_mun, "layers" => $data_lay];
            }
            $itemModel = null;
            return ['success' => true, 'statuscode' => 200, 'totalcount' => count($items), 'data' => $items];
        } catch (\Exception $ee) {
            return ['success' => false, 'statuscode' => 400, 'message' => $ee->getMessage()];
        }
    }

    public function createUsers($parsedParams, $parsedBody)
    {
        try {
            if (empty($parsedBody) or count($parsedBody) == 0) throw new \Exception('отсутствуют данные для добавления');
            unset($parsedBody['id'], $parsedBody['munobrs'], $parsedBody['layers']); //удалю id

            $find = Capsule::connection('roadinfo')->table('users')
                ->where('auth_user_id', '=', \Illuminate\Support\Arr::get($parsedBody, 'auth_user_id'))->first();
            if (!empty($find->id)) {
                Capsule::connection('roadinfo')->beginTransaction();
                $parsedBody['updated_at'] = new \DateTime();
                $parsedBody['deleted_at'] = null;
                Capsule::connection('roadinfo')->table('users')->where('id', '=', $find->id)->update($parsedBody);
                $id = $find->id;
                Capsule::connection('roadinfo')->commit();
            } else {
                Capsule::connection('roadinfo')->beginTransaction();
                $parsedBody['created_at'] = new \DateTime();
                $id = Capsule::connection('roadinfo')->table('users')->insertGetId($parsedBody);
                Capsule::connection('roadinfo')->commit();
            }

            return ['success' => true, 'statuscode' => 200, 'message' => 'выполнено успешно', 'data' => ['id' => $id]];
        } catch (\Exception $ee) {
            Capsule::connection('roadinfo')->rollback();
            return ['success' => false, 'statuscode' => 400, 'message' => $ee->getMessage()];
        }
    }

    public function updateUsers($parsedParams, $parsedBody)
    {
        try {
            if (empty($parsedBody) or count($parsedBody) == 0) throw new \Exception('отсутствуют данные для добавления');
            $id = \Illuminate\Support\Arr::get($parsedBody, 'id');
            if (empty($id)) throw new \Exception('отсутствуют ИД записи');
            unset($parsedBody['id'], $parsedBody['munobrs'], $parsedBody['layers']); //удалю id

            Capsule::connection('roadinfo')->beginTransaction();
            $parsedBody['updated_at'] = new \DateTime();
            Capsule::connection('roadinfo')->table('users')->where('id', '=', $id)->update($parsedBody);
            Capsule::connection('roadinfo')->commit();

            if ($this->user->id == $id){ //осли обновил самого себя, то заменю сессию
                (new \RKA\Session())->set('user_roadinfo', null);
                (new \RKA\Session())->set('user_roadinfo', $this->getAccess($id));
            }

            return ['success' => true, 'statuscode' => 200, 'message' => 'выполнено успешно', 'data' => ['id' => $id]];
        } catch (\Exception $ee) {
            Capsule::connection('roadinfo')->rollback();
            return ['success' => false, 'statuscode' => 400, 'message' => $ee->getMessage()];
        }
    }

    public function destroyUsers($parsedParams, $parsedBody)
    {
        try {
            if (empty($parsedBody) or count($parsedBody) == 0) throw new \Exception('отсутствуют данные для добавления');
            $id = \Illuminate\Support\Arr::get($parsedBody, 'id');
            if (empty($id)) throw new \Exception('отсутствуют ИД записи');
            unset($parsedBody['id'], $parsedBody['munobrs'], $parsedBody['layers']); //удалю id

            Capsule::connection('roadinfo')->beginTransaction();
            Capsule::connection('roadinfo')->table('users')->where('id', '=', $id)->update(['deleted_at' => new \DateTime()]);
            Capsule::connection('roadinfo')->commit();

            if ($this->user->id == $id){ //осли обновил самого себя, то заменю сессию
                (new \RKA\Session())->set('user_roadinfo', null);
                (new \RKA\Session())->set('user_roadinfo', $this->getAccess($id));
            }

            return ['success' => true, 'statuscode' => 200, 'message' => 'выполнено успешно', 'data' => ['id' => $id]];
        } catch (\Exception $ee) {
            Capsule::connection('roadinfo')->rollback();
            return ['success' => false, 'statuscode' => 400, 'message' => $ee->getMessage()];
        }
    }

    public function accessUsers($parsedParams, $parsedBody)
    {
        try {
            if (empty($parsedBody) or count($parsedBody) == 0) throw new \Exception('отсутствуют данные для добавления');
            $user_id = \Illuminate\Support\Arr::get($parsedBody, 'user_id');
            $munobrs = \Illuminate\Support\Arr::get($parsedBody, 'munobrs');
            $layers = \Illuminate\Support\Arr::get($parsedBody, 'layers');
            if (empty($user_id)) throw new \Exception('отсутствуют ИД пользователя');

            Capsule::connection('roadinfo')->beginTransaction();
            Capsule::connection('roadinfo')->table('user_munobrs')->where('user_id', '=', $user_id)->delete();
            Capsule::connection('roadinfo')->table('user_layers')->where('user_id', '=', $user_id)->delete();
            foreach ($munobrs as $mun) {
                Capsule::connection('roadinfo')->table('user_munobrs')->insert(["user_id" => $user_id, "munobr_id" => $mun, 'created_at' => new \DateTime()]);
            }
            foreach ($layers as $lay) {
                Capsule::connection('roadinfo')->table('user_layers')->insert(["user_id" => $user_id, "layer_id" => $lay, 'created_at' => new \DateTime()]);
            }
            Capsule::connection('roadinfo')->commit();

            if ($this->user->id == $user_id){ //осли обновил самого себя, то заменю сессию
                (new \RKA\Session())->set('user_roadinfo', null);
                (new \RKA\Session())->set('user_roadinfo', $this->getAccess($user_id));
            }

            return ['success' => true, 'statuscode' => 200, 'message' => 'выполнено успешно'];
        } catch (\Exception $ee) {
            Capsule::connection('roadinfo')->rollback();
            return ['success' => false, 'statuscode' => 400, 'message' => $ee->getMessage()];
        }
    }

}


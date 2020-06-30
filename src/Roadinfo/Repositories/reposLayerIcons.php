<?php

namespace Roadinfo\Repositories;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Carbon;


class reposLayerIcons
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

    public function getList($parsedParams)
    {
        $layer_id = \Illuminate\Support\Arr::get($parsedParams, 'layer');
        if (!empty($layer_id))
            $results = Capsule::connection('roadinfo')->table('layer_icons')->whereNull('deleted_at')->where('layer_id', '=', $layer_id)->get();
        else
            $results = Capsule::connection('roadinfo')->table('layer_icons')->whereNull('deleted_at')->orderBy('layer_id')->get();
       // if (empty($results) or empty($results[0])) throw new \Exception('содержимое пусто');
        $data = [];
        foreach ($results as $it) {
            array_push($data, get_object_vars($it));
        }
        return [
            'success' => true,
            'statuscode' => 200,
            'totalcount' => count($data),
            'data' => $data
        ];
    }

    public function Create($parsedParams, $parsedBody)
    {
        try {
            if (empty($parsedBody) or count($parsedBody) == 0) throw new \Exception('отсутствуют данные для добавления');
            if (empty(\Illuminate\Support\Arr::get($parsedBody, 'layer_id'))) throw new \Exception('отсутствует идентификатор ведомости');
            if (empty(\Illuminate\Support\Arr::get($parsedBody, 'dict_icons'))) throw new \Exception('отсутствует идентификатор иконки');
            if (empty(\Illuminate\Support\Arr::get($parsedBody, 'where_param')) or count(\Illuminate\Support\Arr::get($parsedBody, 'where_param')) == 0) throw new \Exception('отсутствует список условия отображения иконки');

            $layer_id = \Illuminate\Support\Arr::get($parsedBody, 'layer_id');
            $dict_icons = \Illuminate\Support\Arr::get($parsedBody, 'dict_icons');
            $where_param = json_encode(\Illuminate\Support\Arr::get($parsedBody, 'where_param'), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

            $results = Capsule::connection('roadinfo')->table('layer_icons')
                ->where('layer_id', '=', $layer_id)
                ->where('dict_icons', '=', $dict_icons)
                ->whereRaw('json_contains(where_param, ?)', [$where_param])
                ->select('id')->get();

            if (!empty($results) and !empty($results[0])) {
                $id = $results[0]->id;
                $body['updated_at'] = new \DateTime();
                $body['deleted_at'] = null;
                Capsule::connection('roadinfo')->beginTransaction();
                Capsule::connection('roadinfo')->table('layer_icons')->where('id', '=', $id)->update($body);
                Capsule::connection('roadinfo')->commit();
            } else {
                $body['layer_id'] = $layer_id;
                $body['dict_icons'] = $dict_icons;
                $body['where_param'] = $where_param;
                $body['created_at'] = new \DateTime();
                Capsule::connection('roadinfo')->beginTransaction();
                $id = Capsule::connection('roadinfo')->table('layer_icons')->insertGetId($body);
                Capsule::connection('roadinfo')->commit();
            }

            return ['success' => true, 'statuscode' => 200, 'message' => 'выполнено успешно', 'data' => ['id' => $id]];
        } catch (\Exception $ee) {
            Capsule::connection('roadinfo')->rollback();
            return ['success' => false, 'statuscode' => 400, 'message' => $ee->getMessage()];
        }
    }

    public function Update($parsedParams, $parsedBody)
    {
        try {
            if (empty($parsedBody) or count($parsedBody) == 0) throw new \Exception('отсутствуют данные для добавления');
            if (empty(\Illuminate\Support\Arr::get($parsedBody, 'id'))) throw new \Exception('отсутствует идентификатор записи');
            if (empty(\Illuminate\Support\Arr::get($parsedBody, 'layer_id'))) throw new \Exception('отсутствует идентификатор ведомости');
            if (empty(\Illuminate\Support\Arr::get($parsedBody, 'dict_icons'))) throw new \Exception('отсутствует идентификатор иконки');
            if (empty(\Illuminate\Support\Arr::get($parsedBody, 'where_param')) or count(\Illuminate\Support\Arr::get($parsedBody, 'where_param')) == 0) throw new \Exception('отсутствует список условия отображения иконки');

            $id = \Illuminate\Support\Arr::get($parsedBody, 'id');
            $layer_id = \Illuminate\Support\Arr::get($parsedBody, 'layer_id');
            $dict_icons = \Illuminate\Support\Arr::get($parsedBody, 'dict_icons');
            $where_param = json_encode(\Illuminate\Support\Arr::get($parsedBody, 'where_param'), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

            $body['layer_id'] = $layer_id;
            $body['dict_icons'] = $dict_icons;
            $body['where_param'] = $where_param;
            $body['updated_at'] = new \DateTime();
            $body['deleted_at'] = null;
            Capsule::connection('roadinfo')->beginTransaction();
            Capsule::connection('roadinfo')->table('layer_icons')->where('id', '=', $id)->update($body);
            Capsule::connection('roadinfo')->commit();

            return ['success' => true, 'statuscode' => 200, 'message' => 'выполнено успешно', 'data' => ['id' => $id]];
        } catch (\Exception $ee) {
            Capsule::connection('roadinfo')->rollback();
            return ['success' => false, 'statuscode' => 400, 'message' => $ee->getMessage()];
        }
    }

    public function Destroy($parsedParams, $parsedBody)
    {
        try {
            if (empty($parsedBody) or count($parsedBody) == 0) throw new \Exception('отсутствуют данные для добавления');
            if (empty(\Illuminate\Support\Arr::get($parsedBody, 'id'))) throw new \Exception('отсутствует идентификатор записи');

            $id = \Illuminate\Support\Arr::get($parsedBody, 'id');

            $body['deleted_at'] = new \DateTime();
            Capsule::connection('roadinfo')->beginTransaction();
            Capsule::connection('roadinfo')->table('layer_icons')->where('id', '=', $id)->update($body);
            Capsule::connection('roadinfo')->commit();

            return ['success' => true, 'statuscode' => 200, 'message' => 'выполнено успешно', 'data' => ['id' => $id]];
        } catch (\Exception $ee) {
            Capsule::connection('roadinfo')->rollback();
            return ['success' => false, 'statuscode' => 400, 'message' => $ee->getMessage()];
        }
    }

}


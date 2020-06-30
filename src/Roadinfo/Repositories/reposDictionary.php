<?php

namespace Roadinfo\Repositories;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Carbon;


class reposDictionary
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

    public function getMetaData($parsedParams)
    {
        $dict = \Illuminate\Support\Arr::get($parsedParams, 'dict');
        $results = Capsule::connection('roadinfo')->select('select * from dicts where table_name = \'' . $dict . '\';');
        if (empty($results) or empty($results[0])) throw new \Exception('наименование справочника ' . $dict . ' пусто');
        $dict_id = $results[0]->id;
        $dict_name = $results[0]->name;
        $dict_table_name = $results[0]->table_name;
        $sql_select_from_table_attributes = 'select * from dict_attributes where dict_id = \'' . $dict_id . '\';';
        $results = Capsule::connection('roadinfo')->select($sql_select_from_table_attributes);
        if (empty($results) or empty($results[0])) throw new \Exception('описание справочника ' . $dict_table_name . ' не найдено');
        $data = [];
        foreach ($results as $it) {
            $prop = get_object_vars($it);
            unset($prop['dict_id']);
            array_push($data, $prop);
        }
        return ['success' => true,
            'statuscode' => 200,
            'totalcount' => count($data),
            'metaData' => ['id' => $dict_id, 'title' => $dict_name, 'objectDBName' => $dict_table_name, 'fields' => $data]
        ];
    }

    public function getDictList($parsedParams)
    {
        $results = Capsule::connection('roadinfo')->table('dicts')->whereNotIn('table_name', ['amstrad_routes.objects'])->orderBy('name')->get();
        if (empty($results) or empty($results[0])) throw new \Exception('содержимое пусто');
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

    public function dictSelect($parsedParams)
    {
        $dict = \Illuminate\Support\Arr::get($parsedParams, 'dict');
        if (empty($dict)) throw new \Exception('идентификатор справочника не указан');
        $metaData = $this->getMetaData($parsedParams)['metaData'];

        switch ($metaData['objectDBName']) {
            case "amstrad_routes.objects":
                $itemModel = new \Roadinfo\Eloquent\modelObjects();
                $results = $itemModel::
                select('id', 'name', 'km_beg', 'km_end', 'munobr_id')
                    ->whereIn('munobr_id', $this->user->munobrs)
                    ->orderBy('munobr_id', 'asc')
                    ->orderBy('name', 'asc');
                $results = $results->get()->all();
                $data = [];
                foreach ($results as $item) {
                    $data[] = ['id' => $item->id,
                        'code' => $item->id,
                        'nameshort' => $item->name,
                        'munobr' => empty($item->Munobraz()) ? null : $item->Munobraz()->name,
                        'munobr_id' => [$item->munobr_id],
                        'km_beg' => $item->km_beg,
                        'km_end' => $item->km_end,
                        'name' => $item->name . ', км. ' . $item->km_beg . ' - ' . $item->km_end
                    ];
                }
                break;
            case "dict_icons":
                $results = Capsule::connection('roadinfo')->select('select *, code as img, code as file from ' . $metaData['objectDBName'] . ' where deleted_at is null order by id;');
                $data = [];
                foreach ($results as $it) {
                    array_push($data, get_object_vars($it));
                }
                break;
            default:
                $results = Capsule::connection('roadinfo')->select('select * from ' . $metaData['objectDBName'] . ' where deleted_at is null order by code;');
                $data = [];
                foreach ($results as $it) {
                    array_push($data, get_object_vars($it));
                }
        }

        $result = [
            'success' => true,
            'statuscode' => 200,
            'totalcount' => count($data),
            'data' => $data
        ];
        return $result;
    }

    private function moveUploadedFile($directory, $uploadedFile, $name_file)
    {
        if (!file_exists($uploadedFile->file)) {
            throw new \Exception('временный файл отсутствует ' . $uploadedFile->file);
            return;
        }
        if (empty($name_file)) {
            $file_correct_name = str_replace(' ', '_', $uploadedFile->getClientFilename());
            $file_correct_name = str_replace('.', '_', pathinfo($file_correct_name, PATHINFO_FILENAME)); //без расширения
            $file_correct_name = str_replace(',', '_', $file_correct_name);
            $file_correct_name = str_replace('!', '_', $file_correct_name);
            $file_correct_name = str_replace('-', '_', $file_correct_name);
        } else {
            $file_correct_name = $name_file;
        }

        $filename = $directory . $file_correct_name;
        $uploadedFile->moveTo($filename);
        $filename = str_replace(PUBLICDIR, '', $filename);

        return $filename;
    }

    public function dictUpdate($parsedParams, $parsedBody)
    {
        try {
            $dict = \Illuminate\Support\Arr::get($parsedParams, 'dict');
            if (empty($parsedBody) or count($parsedBody) == 0) throw new \Exception('отсутствуют данные для добавления');
            if (empty($dict)) throw new \Exception('отсутствует идентификатор справочника');

            $files = \Illuminate\Support\Arr::get($parsedBody, 'uploaded_files');
            if (!empty($files)) $files = $files['items']['code'];
            if (empty($files->file)) $files = null;
            unset($parsedBody['uploaded_files']); //удалю uploaded_files

            $dict_meta = $this->getMetaData($parsedParams)['metaData'];

            switch ($dict) {
                case "dict_icons":
                    $id = \Illuminate\Support\Arr::get($parsedBody, 'id');
                    if (empty($id)) throw new \Exception('отсутствует идентификатор записи');
                    unset($parsedBody['id']); //удалю id

                    $parsedBody['updated_at'] = new \DateTime();
                    Capsule::connection('roadinfo')->beginTransaction();
                    Capsule::connection('roadinfo')->table($dict_meta['objectDBName'])->where('id', '=', $id)->update($parsedBody);
                    if (!empty($files)) { //заменю фото на диске
                        $results = Capsule::connection('roadinfo')->table($dict_meta['objectDBName'])->select('code')->where('id', '=', $id)->get();
                        $icon_code = $results[0]->code;
                        if (empty($icon_code)) throw new \Exception('код иконки не определен в БД');
                        if ($files->getError() === UPLOAD_ERR_OK) { //заменю новым
                            $filemoved = $this->moveUploadedFile(LAYERS_ICONS, $files, $icon_code);
                        } else throw new \Exception('Файл не прикреплен');
                    }
                    Capsule::connection('roadinfo')->commit();
                    break;
                default:
                    $attr = [];
                    foreach ($dict_meta['fields'] as $field) {
                        $attr[$field['field_name']] = $field['type_name'];
                    }
                    if (empty($attr) or count($attr) == 0) throw new \Exception('отсутствует описание полей таблицы');
                    $bodys = [];
                    if (array_key_exists("id", $parsedBody)) {//если в массиве одна запись
                        array_push($bodys, $parsedBody);
                    } else { // если в массиве несколько добавляемых записей
                        $bodys = $parsedBody;
                    }
                    foreach ($bodys as $body) {
                        //if (!empty($body['id'])) throw new \Exception('попытка добавить существующую запись');
                        $id = \Illuminate\Support\Arr::get($body, 'id');
                        unset($body['id']); //удалю id

                        foreach (array_keys($body) as $pr) {//удаляю из пришедшей записи всякие служебные поля, КреатеДате, ид, оставлю только те поля которые в описании ведомости
                            if (empty($attr[$pr])) {
                                unset($body[$pr]);
                            }
                        }

                        foreach (array_keys($body) as $pr) {
                            switch ($attr[$pr]) {
                                case '14':
                                case '15':
                                case '16':
                                    if (is_numeric($body[$pr])) {//если пришел Timestamp
                                        $date = new \DateTime();
                                        $body[$pr] = $date->setTimestamp($body[$pr]);
                                    } else {
                                        $body[$pr] = new \DateTime($body[$pr]);
                                    }
                                    break;

                                case '13':
                                case '5':
                                case '18':
                                case '19':
                                    $body[$pr] = $body[$pr];
                                    break;
                                case '9':
                                case '17':
                                    if (empty($body[$pr])) {
                                        $body[$pr] = null;
                                    }//если пустая строка, то нулл
                                    else {
                                        $body[$pr] = trim($body[$pr]);
                                    }
                                    break;
                                    case '7':
                                case '10':
                                    $body[$pr] = intval(trim($body[$pr]));
                                    break;
                                case '8':
                                case '11':
                                case '30':
                                    $body[$pr] = floatval(trim($body[$pr]));
                                    break;
                                case '12':
                                case '21':
                                    if ($body[$pr] === "" or $body[$pr] === null) {
                                        $body[$pr] = null;
                                    } else {
                                        $body[$pr] = filter_var(trim($body[$pr]), FILTER_VALIDATE_BOOLEAN);
                                    }
                                    break;
                                default:
                                    throw new \Exception('Неизвестный тип переменной');
                            }
                        }

                        $body['updated_at'] = new \DateTime();
                        Capsule::connection('roadinfo')->beginTransaction();
                        Capsule::connection('roadinfo')->table($dict_meta['objectDBName'])->where('id', '=', $id)->update($body);
                        Capsule::connection('roadinfo')->commit();
                    }
            }

            return ['success' => true, 'statuscode' => 200, 'message' => 'выполнено успешно'];
        } catch (\Exception $ee) {
            Capsule::connection('roadinfo')->rollback();
            return ['success' => false, 'statuscode' => 400, 'message' => $ee->getMessage()];
        }
    }

    public function dictCreate($parsedParams, $parsedBody)
    {
        try {
            $dict = \Illuminate\Support\Arr::get($parsedParams, 'dict');
            if (empty($parsedBody) or count($parsedBody) == 0) throw new \Exception('отсутствуют данные для добавления');
            if (empty($dict)) throw new \Exception('отсутствует идентификатор справочника');

            $files = \Illuminate\Support\Arr::get($parsedBody, 'uploaded_files');
            if (!empty($files)) $files = $files['items']['code'];
            if (empty($files->file)) $files = null;

            unset($parsedBody['uploaded_files']); //удалю uploaded_files
            $dict_meta = $this->getMetaData($parsedParams)['metaData'];

            switch ($dict) {
                case "dict_icons":
                    unset($parsedBody['id']);
                    if (empty($files)) throw new \Exception('отсутствует графическое изображение');
                    $parsedBody['created_at'] = new \DateTime();
                    $parsedBody['code'] = $files->getClientFilename();
                    Capsule::connection('roadinfo')->beginTransaction();
                    $id = Capsule::connection('roadinfo')->table($dict_meta['objectDBName'])->insertGetId($parsedBody);
                    $icon_code = $id . '.' . pathinfo($files->getClientFilename(), PATHINFO_EXTENSION);
                    Capsule::connection('roadinfo')->table($dict_meta['objectDBName'])->where('id', '=', $id)->update(['code' => $icon_code]);
                    Capsule::connection('roadinfo')->commit();
                    if (!empty($files)) { //заменю фото на диске
                        if (empty($icon_code)) throw new \Exception('код иконки не определен в БД');
                        if ($files->getError() === UPLOAD_ERR_OK) { //заменю новым
                            $filemoved = $this->moveUploadedFile(LAYERS_ICONS, $files, $icon_code);
                        } else throw new \Exception('Файл не прикреплен');
                    }
                    Capsule::connection('roadinfo')->commit();
                    break;
                default:
                    $attr = [];
                    foreach ($dict_meta['fields'] as $field) {
                        $attr[$field['field_name']] = $field['type_name'];
                    }
                    if (empty($attr) or count($attr) == 0) throw new \Exception('отсутствует описание полей таблицы');
                    $bodys = [];
                    if (array_key_exists("id", $parsedBody)) {//если в массиве одна запись
                        array_push($bodys, $parsedBody);
                    } else { // если в массиве несколько добавляемых записей
                        $bodys = $parsedBody;
                    }
                    foreach ($bodys as $body) {
                        //if (!empty($body['id'])) throw new \Exception('попытка добавить существующую запись');
                        unset($body['id']); //удалю id

                        foreach (array_keys($body) as $pr) {//удаляю из пришедшей записи всякие служебные поля, КреатеДате, ид, оставлю только те поля которые в описании ведомости
                            if (empty($attr[$pr])) {
                                unset($body[$pr]);
                            }
                        }

                        foreach (array_keys($body) as $pr) {
                            switch ($attr[$pr]) {
                                case '14':
                                case '15':
                                case '16':
                                    if (is_numeric($body[$pr])) {//если пришел Timestamp
                                        $date = new \DateTime();
                                        $body[$pr] = $date->setTimestamp($body[$pr]);
                                    } else {
                                        $body[$pr] = new \DateTime($body[$pr]);
                                    }
                                    break;

                                case '13':
                                case '5':
                                case '18':
                                case '19':
                                    $body[$pr] = $body[$pr];
                                    break;
                                case '9':
                                case '17':
                                    if (empty($body[$pr])) {
                                        $body[$pr] = null;
                                    }//если пустая строка, то нулл
                                    else {
                                        $body[$pr] = trim($body[$pr]);
                                    }
                                    break;
                                    case '7':
                                case '10':
                                    $body[$pr] = intval(trim($body[$pr]));
                                    break;
                                case '8':
                                case '11':
                                case '30':
                                    $body[$pr] = floatval(trim($body[$pr]));
                                    break;
                                case '12':
                                case '21':
                                    if ($body[$pr] === "" or $body[$pr] === null) {
                                        $body[$pr] = null;
                                    } else {
                                        $body[$pr] = filter_var(trim($body[$pr]), FILTER_VALIDATE_BOOLEAN);
                                    }
                                    break;
                                default:
                                    throw new \Exception('Неизвестный тип переменной');
                            }
                        }

                        $body['created_at'] = new \DateTime();
                        Capsule::connection('roadinfo')->beginTransaction();
                        $id = Capsule::connection('roadinfo')->table($dict_meta['objectDBName'])->insertGetId($body);
                        Capsule::connection('roadinfo')->commit();
                    }
            }

            return ['success' => true, 'statuscode' => 200, 'message' => 'выполнено успешно', 'data' => ['id' => $id]];
        } catch (\Exception $ee) {
            Capsule::connection('roadinfo')->rollback();
            return ['success' => false, 'statuscode' => 400, 'message' => $ee->getMessage()];
        }
    }

    public function dictDestroy($parsedParams, $parsedBody)
    {
        try {
            if (empty($parsedBody) or count($parsedBody) == 0) throw new \Exception('отсутствуют данные для удаления');
            $dict_meta = $this->getMetaData($parsedParams)['metaData'];
            $bodys = [];
            if (array_key_exists("id", $parsedBody)) {//если в массиве одна запись
                array_push($bodys, $parsedBody);
            } else { // если в массиве несколько добавляемых записей
                $bodys = $parsedBody;
            }
            foreach ($bodys as $body) {
                $id = \Illuminate\Support\Arr::get($body, 'id');

                $body = ['deleted_at' => new \DateTime()];
                Capsule::connection('roadinfo')->beginTransaction();
                Capsule::connection('roadinfo')->table($dict_meta['objectDBName'])->where('id', '=', $id)->update($body);
                Capsule::connection('roadinfo')->commit();
            }
            return ['success' => true, 'statuscode' => 200, 'message' => 'выполнено успешно', 'data' => ['id' => $id]];
        } catch (\Exception $ee) {
            Capsule::connection('roadinfo')->rollback();
            return ['success' => false, 'statuscode' => 400, 'message' => $ee->getMessage()];
        }
    }

}


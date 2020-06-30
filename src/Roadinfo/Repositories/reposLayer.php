<?php

namespace Roadinfo\Repositories;

use Roadinfo\Eloquent\modelLayers;
use Roadinfo\Eloquent\modelObjects;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Kalnoy\Nestedset\NodeTrait;


class reposLayer
{
    use NodeTrait;

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


    //создать ведомость из Excel
    public function getCreate_layer_from_xls($body)
    {
        try {
            $file = \Illuminate\Support\Arr::get($body, 'uploaded_files')['layerupload'];
            if ($file->getError() !== UPLOAD_ERR_OK) { //заменю новым
                throw new \Exception('Ошибка загрузки файла ведомости');
            }
            $zip = new \ZipArchive;
            $dir = UPLOADDIR . 'create_layer_from_xls' . DS;
            if ($zip->open($file->file) === TRUE) {
                mkdir($dir);
                foreach (glob($dir . '*.*') as $v) {
                    unlink($v);
                }
                $zip->extractTo($dir);
                $zip->close();
            } else {
                throw new \Exception('Ошибка разархивирования файла ведомости');
            }

            //получаю списко файлов
            $xls_files = [];
            foreach (glob($dir . '*.xls') as $v) {
                array_push($xls_files, $v);
            }
            if (count($xls_files) == 0) throw new \Exception('В архивном файле отсутствуют файлы конфигурации ведомости (.xls)');

            //читаю структуру и данные из Ексел, а также проверяю логику данных
            $arr_files = [];
            foreach ($xls_files as $f) {
                array_push($arr_files, $this->readXls_file($f));
            }

            //наличие ошибок
            $all_error = [];
            foreach ($arr_files as $f) {
                $fn = $f['file'];
                $fe = $f['error'];
                if (count($fe) > 0) {
                    array_push($all_error, ['file' => $fn, 'error' => $fe]);
                }
            }
            if (count($all_error) > 0) {
                return ['success' => false, 'statuscode' => 400, 'message' => $all_error];
            }

            //загружу файлы в БД
            $fffile = '';
            Capsule::connection('roadinfo')->statement('SET autocommit=0;');
            Capsule::connection('roadinfo')->beginTransaction();
            foreach ($arr_files as $f) {
                $fffile = $f['file'];
                $this->loadFile($f);
            }
            Capsule::connection('roadinfo')->commit();
            Capsule::connection('roadinfo')->statement('SET autocommit=1;');
            return ['success' => true, 'statuscode' => 200];
        } catch (\Exception $ee) {
            Capsule::connection('roadinfo')->rollBack();
            Capsule::connection('roadinfo')->statement('SET autocommit=1;');
            return ['success' => false, 'statuscode' => 400, 'message' => ['file' => $fffile, 'error' => [$ee->getMessage()]]];
        }
    }

    private function array_has_dupes($array)
    {
        return count($array) !== count(array_unique($array));
    }

    private function checkError($info, $fields)
    {
        $error = [];

        if (empty($info['caption'])) array_push($error, 'не указано наименование таблицы');
        if (empty($info['table_name'])) array_push($error, 'не указано имя таблицы');
        if (!empty($info['table_name']) and !preg_match("/^[A-Za-z0-9_]+$/", $info['table_name'])) array_push($error, 'имя таблицы может содержать латинские буквы, цифры и подчеркивание');
        if (empty($info['table_category'])) array_push($error, 'не указана категория таблицы');
        if (!empty($info['table_category']) and !in_array($info['table_category'], ['справочник', 'ведомость'])) array_push($error, 'категория таблицы некорректна');
        if ($info['table_category'] == 'справочник' and !empty($info['geometry_type'])) array_push($error, 'указан тип географических элементов для справочника');

        if (empty($fields) or count($fields) == 0) array_push($error, 'не указано описание полей ведомости или справочника');

        foreach ($fields as $f) {
            if (empty($f['name'])) array_push($error, 'ОПИСАНИЕ ПОЛЕЙ: не указано имя поля');
            if (empty($f['type'])) array_push($error, 'ОПИСАНИЕ ПОЛЕЙ: не указан тип данных');
            if (empty($f['display'])) array_push($error, 'ОПИСАНИЕ ПОЛЕЙ: не указано пользовательское наименование');

            if (!empty($f['type']) and !in_array($f['type'], [10, 8, 9, 12, 14, 7, 13, 60, 30])) array_push($error, 'ОПИСАНИЕ ПОЛЕЙ: тип данных некорректен');

            if ($f['name'] == 'object_id' and $f['type'] != '7') array_push($error, 'ОПИСАНИЕ ПОЛЕЙ: для поля object_id не указан типа данных "7 - справочник Объектов содержания"');
            if ($f['name'] == 'km_beg' and $f['type'] != '30') array_push($error, 'ОПИСАНИЕ ПОЛЕЙ: для поля km_beg не указан типа данных "30 - привязка (км.)"');
            if ($f['name'] == 'km_end' and $f['type'] != '30') array_push($error, 'ОПИСАНИЕ ПОЛЕЙ: для поля km_end не указан типа данных "30 - привязка (км.)"');
            if ($f['name'] == 'code' and $info['table_category'] == 'справочник' and $f['type'] != '9') array_push($error, 'ОПИСАНИЕ ПОЛЕЙ: для поля code не указан типа данных "9 - текст"');

            if (!empty($f['extensions']) and count(explode("|", $f['extensions'])) == 0) array_push($error, 'ОПИСАНИЕ ПОЛЕЙ: расширения файлов должны быть указаны через символ-разделитель | ');

            if ($f['type'] != '60' and !empty($f['extensions'])) array_push($error, 'ОПИСАНИЕ ПОЛЕЙ: расширения файлов допустимы только для типа данных "60 - прикрепленный файл"');
            if ($f['type'] == '7' and $f['ref'] != 'amstrad_routes.objects') array_push($error, 'ОПИСАНИЕ ПОЛЕЙ: для типа данных "7 - справочник Объектов содержания" не указан справочник "amstrad_routes.objects"');
            if ($f['type'] == '13' and empty($f['ref'])) array_push($error, 'ОПИСАНИЕ ПОЛЕЙ: для типа данных "13 - справочник" не указана ссылка на справочник');
            if ($f['type'] == '13' and $f['ref'] == 'amstrad_routes.objects') array_push($error, 'ОПИСАНИЕ ПОЛЕЙ: для типа данных "13 - справочник" указан справочник "amstrad_routes.objects"');
            if ($f['type'] == '9' and (empty($f['size']) or $f['size'] == 0)) array_push($error, 'ОПИСАНИЕ ПОЛЕЙ: для типа данных "9 - текст" не указана макс. длина символьных данных');

            if (!empty($f['extensions']) and !preg_match("/^[A-Za-z0-9|]+$/", $f['extensions'])) array_push($error, 'ОПИСАНИЕ ПОЛЕЙ: расширения файлов может содержать латинские буквы, цифры и символ-разделитель | ');
            if (!empty($f['name']) and !preg_match("/^[A-Za-z0-9_]+$/", $f['name'])) array_push($error, 'ОПИСАНИЕ ПОЛЕЙ: имя поля может содержать латинские буквы, цифры и подчеркивание');
            if (!empty($f['ref']) and $f['ref'] != 'amstrad_routes.objects' and !preg_match("/^[A-Za-z0-9_]+$/", $f['ref'])) array_push($error, 'ОПИСАНИЕ ПОЛЕЙ: ссылка на справочник может содержать латинские буквы, цифры и подчеркивание');
            if (!empty($f['ref']) and $f['ref'] == 'amstrad_routes.objects' and !preg_match("/^[A-Za-z0-9_.]+$/", $f['ref'])) array_push($error, 'ОПИСАНИЕ ПОЛЕЙ: ссылка на справочник может содержать латинские буквы, цифры и подчеркивание');
        }

        $field_names = array_column($fields, 'name');
        $field_types = array_column($fields, 'type');

        if ($info['table_category'] == 'ведомость') {
            if (!in_array('object_id', $field_names)) array_push($error, 'ОПИСАНИЕ ПОЛЕЙ: для ведомости отсутствует поле object_id');
            if (!in_array('km_beg', $field_names)) array_push($error, 'ОПИСАНИЕ ПОЛЕЙ: для ведомости отсутствует поле km_beg');
            if ($info['geometry_type'] == 'LineString' and !in_array('km_end', $field_names)) array_push($error, 'ОПИСАНИЕ ПОЛЕЙ: для типа географических элементов LineString отсутствует поле km_end');
        }
        if ($info['table_category'] == 'справочник') {
            if (!in_array('code', $field_names)) array_push($error, 'ОПИСАНИЕ ПОЛЕЙ: для справочника отсутствует поле code');
            if (!in_array('name', $field_names)) array_push($error, 'ОПИСАНИЕ ПОЛЕЙ: для справочника отсутствует поле name');
            if (in_array('60', $field_types)) array_push($error, 'ОПИСАНИЕ ПОЛЕЙ: для справочника недопустимо наличие поля типа данных "60 - прикрепленный файл"');
        }

        if (in_array('id', $field_names)) array_push($error, 'ОПИСАНИЕ ПОЛЕЙ: недопустимо наличие поля id');

        if ($this->array_has_dupes($field_names)) array_push($error, 'ОПИСАНИЕ ПОЛЕЙ: имеются повторяющиеся значения имен полей');

        $file_fields = array_filter($field_types, function($k) {return $k == 60;}); //отберу только поля типа Файл
        if (count($file_fields) > 1) array_push($error, 'ОПИСАНИЕ ПОЛЕЙ: допустимо наличие только одного поля типа данных "60 - прикрепленный файл"');

        //проверку >1 поля прикрепленный файл $field_types
        return $error;
    }

    private function readXls_file($file)
    {
        try {
            $inputFileType = 'Xls';
            $sheetnames = ['struct', 'data'];
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
            $reader->setLoadSheetsOnly($sheetnames);
            $spreadsheet = $reader->load($file);

            //read configuration
            $info = [];
            $spreadsheet->setActiveSheetIndexByName('struct');
            $caption_parent_name = trim($spreadsheet->getActiveSheet()->getCell('C4')->getValue());
            $caption = trim($spreadsheet->getActiveSheet()->getCell('C5')->getValue());
            $table_name = trim($spreadsheet->getActiveSheet()->getCell('C7')->getValue());
            $table_category = trim($spreadsheet->getActiveSheet()->getCell('C8')->getValue());
            $geometry_type = trim($spreadsheet->getActiveSheet()->getCell('C9')->getValue());
            if ($caption_parent_name === '') $caption_parent_name = null;
            if ($caption === '') $caption = null;
            if ($table_name === '') $table_name = null;
            if ($table_category === '') $table_category = null;
            if ($geometry_type === '') $geometry_type = null;

            $layer_parent_id = -1;
            if (!empty($caption_parent_name)) {
                $results = Capsule::connection('roadinfo')->select('select id from layers where name = \'' . $caption_parent_name . '\' limit 1 ;');
                if (!empty($results) and !empty($results[0])) $layer_parent_id = $results[0]->id;
            }
            if (!empty($table_name)) {
                if ($table_category == 'ведомость') {
                    $results_t = Capsule::connection('roadinfo')->select('select id, proc_agr_name from layers where table_name = \'' . $table_name . '\' limit 1 ;');
                }
                if ($table_category == 'справочник') {
                    $results_t = Capsule::connection('roadinfo')->select('select id from dicts where table_name = \'' . $table_name . '\' limit 1 ;');
                }
                $table_id = null;
                $proc_agr_name = null;
                if (!empty($results_t) and !empty($results_t[0])) $table_id = $results_t[0]->id;
                if (!empty($results_t) and !empty($results_t[1])) $proc_agr_name = $results_t[1]->id;
            }
            $info = ['caption_parent_name' => $caption_parent_name, 'table_id' => $table_id, 'layer_proc_agr_name' => $proc_agr_name, 'layer_parent_id' => $layer_parent_id, 'caption' => $caption, 'table_name' => $table_name, 'table_category' => $table_category, 'geometry_type' => $geometry_type];

            $row = 12;
            $fields = [];
            while (!empty(trim($spreadsheet->getActiveSheet()->getCell('A' . $row)->getValue()))
                or !empty(trim($spreadsheet->getActiveSheet()->getCell('B' . $row)->getValue()))
                or !empty(trim($spreadsheet->getActiveSheet()->getCell('E' . $row)->getValue()))
            ) {
                $name = trim($spreadsheet->getActiveSheet()->getCell('A' . $row)->getValue());
                $type = trim($spreadsheet->getActiveSheet()->getCell('B' . $row)->getValue());
                $size = trim($spreadsheet->getActiveSheet()->getCell('C' . $row)->getValue());
                $ref = trim($spreadsheet->getActiveSheet()->getCell('D' . $row)->getValue());
                $display = trim($spreadsheet->getActiveSheet()->getCell('E' . $row)->getValue());
                $extensions = trim($spreadsheet->getActiveSheet()->getCell('F' . $row)->getValue());

                $isHidden = 0;
                $isDisabled = 0;
                $isRequired = 0;

                if ($name === '') $name = null;
                if ($type === '') $type = null;
                if ($size === '') $size = null;
                if ($ref === '') $ref = null;
                if ($display === '') $display = null;
                $type = substr($type, 0, strpos($type, ' -'));
                if ($type == "9") $size = intval($size);
                if ($type == "13") $size = 11;
                if ($type == "60") { //прикрепленный файл, не редактируетеся
                    $size = 20;
                    $isDisabled = 1;
                }
                if (in_array($name, ["object_id", "km_beg", "km_end"])) $isRequired = 1; //помечу обязательными
                array_push($fields, ['name' => $name, 'type' => intval($type), 'size' => $size, 'ref' => $ref, 'display' => $display,
                    'isHidden' => $isHidden,
                    'isDisabled' => $isDisabled,
                    'extensions' => $extensions,
                    'isOffsetPosition' => 0,
                    'isOffsetValue' => 0,
                    'isRequired' => $isRequired]);
                $row++;
            }
            $error_file = $this->checkError($info, $fields);

            //добавлю столбец ID
            array_unshift($fields,
                ['name' => 'id',
                    'type' => 10,
                    'size' => null,
                    'ref' => null,
                    'display' => '№',
                    'isHidden' => 1,
                    'isDisabled' => 1,
                    'extensions' => null,
                    'isOffsetPosition' => 0,
                    'isOffsetValue' => 0,
                    'isRequired' => 0]);

            //read data
            $spreadsheet->setActiveSheetIndexByName('data');
            $col = 1;
            $row = 1;
            $fields_name = [];
            while (!empty(trim($spreadsheet->getActiveSheet()->getCellByColumnAndRow($col, $row)->getValue()))) {
                $name = trim($spreadsheet->getActiveSheet()->getCellByColumnAndRow($col, $row)->getValue());
                $type = '';
                foreach ($fields as $ff) {
                    if ($ff['name'] == $name) {
                        $type = $ff['type'];
                    }
                }
                array_push($fields_name, ['name' => $name, 'columnindex' => $col, 'type' => $type]);
                $col++;
            }
            foreach ($fields_name as $ff) {
                if (empty($ff['name']) or empty($ff['columnindex']) or empty($ff['type'])) {
                    array_push($error_file, 'ДАННЫЕ: имя поля отсутствует среди описания полей ведомости или справочника');
                }
            }

            $row = 2;
            $rows = [];
            while (!empty(trim($spreadsheet->getActiveSheet()->getCellByColumnAndRow(1, $row)->getValue()))) {
                $row_data = [];
                for ($i = 1; $i <= count($fields_name); $i++) {
                    $f = $fields_name[$i - 1];
                    $f_name = $f['name'];
                    $f_type = $f['type'];
                    $f_column = $f['columnindex'];
                    $val = trim($spreadsheet->getActiveSheet()->getCellByColumnAndRow($f_column, $row)->getFormattedValue());
                    if ($val !== '0' and empty($val)) {
                        $val = null;
                    } else {
                        if ($f_type == 14) {
                            $val = date('Y-m-d', strtotime($val));
                            $val = new \DateTime($val);
                        } elseif (in_array($f_type, [10, 8, 13, 7, 30])) {
                            $val = floatval(str_replace(',', '.', $val));
                        } elseif ($f_type == 12) {
                            $val = boolval($val);
                        } else {
                            $val = $val;
                        }
                    }
                    $row_data[$f_name] = $val;
                }
                array_push($rows, $row_data);
                $row++;
            }

            return ['file' => pathinfo($file)['basename'], 'error' => $error_file, 'info' => $info, 'fields' => $fields, 'data' => $rows];
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    private function getMySqlType($type, $size)
    {
        switch ($type) {
            case "10":
                return "int(11)";
            case "8":
            case "11":
            case "30":
                return "decimal(13,5)";
            case "13":
            case "60":
                return "varchar(" . ($size) . ")";
            case "9":
                return "varchar(" . ($size + 3) . ")";
            case "12":
                return "int(1)";
            case "7":
                return "int(11)";
            case "14":
                return "datetime";
            default:
                return "varchar(10)";
        }
    }

    private function getMySqlColumn($field)
    {
        $f_n = $field['name'];
        $f_t = $field['type'];
        $f_s = $field['size'];
        switch ($f_n) {
            case "id":
                return "`" . $f_n . "` int(11) NOT NULL AUTO_INCREMENT";
            case "km_beg":
            case "km_end":
                return "`" . $f_n . "` decimal(8,3) DEFAULT NULL";
            case "object_id":
                return "`" . $f_n . "` int(11) DEFAULT NULL";
            default:
                return "`" . $f_n . "` " . $this->getMySqlType($f_t, $f_s) . " DEFAULT NULL";
        }
    }

    private function loadFile($file)
    {
        $info = $file['info'];
        $fields = $file['fields'];
        $data = $file['data'];

        $sql_drop_proc = "";
        $sql_drop_table = "";
        $sql_del_attributes = "";
        $sql_del_info = "";
        $sql_del_role = "";
        $sql_create = "";

        $sql_drop_table = " drop table if exists " . $info['table_name'] . "; ";

        if ($info['table_category'] == 'ведомость') {
            if (!empty($info['proc_agr_name'])) $sql_drop_proc = " drop procedure if exists " . $info['proc_agr_name'] . "; ";
            if (!empty($info['table_id'])) {
                $sql_del_attributes = " delete from layer_attributes where layer_id = " . $info['table_id'] . "; ";
                $sql_del_info = " delete from layers where id = " . $info['table_id'] . "; ";
                $sql_del_role = " delete from user_layers where layer_id = " . $info['table_id'] . "; ";
            }
            //удалю ранее созданное
            if (!empty($sql_drop_proc)) Capsule::connection('roadinfo')->statement($sql_drop_proc);
            if (!empty($sql_drop_table)) Capsule::connection('roadinfo')->statement($sql_drop_table);
            if (!empty($sql_del_attributes)) Capsule::connection('roadinfo')->statement($sql_del_attributes);
            if (!empty($sql_del_info)) Capsule::connection('roadinfo')->statement($sql_del_info);
            if (!empty($sql_del_role)) Capsule::connection('roadinfo')->statement($sql_del_role);

            //создам таблицу
            $sql_create = " create table " . $info['table_name'] . " ( ";
            foreach ($fields as $f) {
                $sql_create = $sql_create . $this->getMySqlColumn($f) . ", ";
            }
            $sql_create = $sql_create . " `created_at` datetime DEFAULT NULL, `updated_at` datetime DEFAULT NULL, `deleted_at` datetime DEFAULT NULL, PRIMARY KEY (`id`) ) ";
            $sql_create = $sql_create . " ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8 COLLATE=utf8_bin; ";
            if (!empty($sql_create)) Capsule::connection('roadinfo')->statement($sql_create);
            //добавлю ведомость
            $table_id = Capsule::connection('roadinfo')->table('layers')->insertGetId(
                [
                    'name' => $info['caption'],
                    'parent_id' => $info['layer_parent_id'],
                    'table_name' => $info['table_name'],
                    'geometry_type' => $info['geometry_type']
                ]);
            //обновлю дерево
            (new modelLayers())::fixTree();
            //добавлю описание
            foreach ($fields as $f) {
                Capsule::connection('roadinfo')->table('layer_attributes')->insert(
                    [
                        'layer_id' => $table_id,
                        'type_layer' => 1,
                        'field_name' => $f['name'],
                        'display_name' => $f['display'],
                        'type_name' => $f['type'],
                        'table_ref_name' => $f['ref'],
                        'maxLength' => $f['size'],
                        'isHidden' => $f['isHidden'],
                        'isDisabled' => $f['isDisabled'],
                        'extensions' => $f['extensions'],
                        'isOffsetPosition' => $f['isOffsetPosition'],
                        'isOffsetValue' => $f['isOffsetValue'],
                        'isRequired' => $f['isRequired']
                    ]);
            }
            //данные
            foreach ($data as $d) {
                $d['created_at'] = new \DateTime();
                Capsule::connection('roadinfo')->table($info['table_name'])->insert($d);
            }

            //удалю прикрепленныей файлы
            $dir = UPLOADDIR . 'layer_attachments' . DS . $info['table_id'] . DS;
            if (!empty($info['table_id']) and is_dir($dir)) {
                $objects = scandir($dir);
                foreach ($objects as $object) {
                    if ($object != "." && $object != "..") {
                        if (filetype($dir."/".$object) == "dir") rmdir($dir."/".$object); else unlink($dir."/".$object);
                    }
                }
                reset($objects);
                rmdir($dir);
            }
        }
        if ($info['table_category'] == 'справочник') {
            if (!empty($info['table_id'])) {
                $sql_del_attributes = " delete from dict_attributes where dict_id = " . $info['table_id'] . "; ";
                $sql_del_info = " delete from dicts where id = " . $info['table_id'] . "; ";
            }
            //удалю ранее созданное
            if (!empty($sql_drop_table)) Capsule::connection('roadinfo')->statement($sql_drop_table);
            if (!empty($sql_del_attributes)) Capsule::connection('roadinfo')->statement($sql_del_attributes);
            if (!empty($sql_del_info)) Capsule::connection('roadinfo')->statement($sql_del_info);
            //создам таблицу
            $sql_create = " create table " . $info['table_name'] . " ( ";
            foreach ($fields as $f) {
                $sql_create = $sql_create . $this->getMySqlColumn($f) . ", ";
            }
            $sql_create = $sql_create . " `created_at` datetime DEFAULT NULL, `updated_at` datetime DEFAULT NULL, `deleted_at` datetime DEFAULT NULL, PRIMARY KEY (`id`) ) ";
            $sql_create = $sql_create . " ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8 COLLATE=utf8_bin; ";
            if (!empty($sql_create)) Capsule::connection('roadinfo')->statement($sql_create);
            //добавлю ведомость
            $table_id = Capsule::connection('roadinfo')->table('dicts')->insertGetId(
                [
                    'name' => $info['caption'],
                    'table_name' => $info['table_name']
                ]);
            //добавлю описание
            foreach ($fields as $f) {
                Capsule::connection('roadinfo')->table('dict_attributes')->insert(
                    [
                        'dict_id' => $table_id,
                        'field_name' => $f['name'],
                        'display_name' => $f['display'],
                        'type_name' => $f['type'],
                        'table_ref_name' => $f['ref'],
                        'maxLength' => $f['size'],
                        'isHidden' => $f['isHidden'],
                        'isDisabled' => $f['isDisabled'],
                        'extensions' => $f['extensions'],
                        'isRequired' => $f['isRequired']
                    ]);
            }
            //данные
            foreach ($data as $d) {
                $d['created_at'] = new \DateTime();
                Capsule::connection('roadinfo')->table($info['table_name'])->insert($d);
            }
        }
    }


    public function getMetaData($parsedParams)
    {
        $layer = \Illuminate\Support\Arr::get($parsedParams, 'layer');
        $type_layer = \Illuminate\Support\Arr::get($parsedParams, 'type_layer');
        if (empty($layer)) throw new \Exception('идентификатор ведомости не указан');
        if (empty($type_layer)) throw new \Exception('идентификатор типа ведомости не указан');

        $results = Capsule::connection('roadinfo')->select('select * from layers where id = \'' . $layer . '\';');
        if (empty($results) or empty($results[0])) throw new \Exception('наименование ведомости ' . $layer . ' пусто');
        $layer_id = $results[0]->id;
        $layer_name = $type_layer == 1 ? $results[0]->name : $results[0]->name . ' (свод)';
        $layer_table_name = $type_layer == 1 ? $results[0]->table_name : $results[0]->proc_agr_name;
        $layer_geometry = $results[0]->geometry_type;
        $layer_icons = $results[0]->dict_icons;
        $layer_offset_field_lr = null;
        $layer_offset_field_v = null;
        $layer_offset_dict_lr = null;
        // $layer_dict_name_icons = $results[0]->dict_name_icons;

        $sql_select_from_table_attributes = 'select * from layer_attributes where layer_id = \'' . $layer_id . '\' and type_layer = ' . $type_layer . ' order by orderId;';
        $results = Capsule::connection('roadinfo')->select($sql_select_from_table_attributes);

        if (empty($results) or empty($results[0])) throw new \Exception('описание ведомости ' . $layer . ' не найдено');
        $data = [];
        foreach ($results as $it) {
            $prop = get_object_vars($it);
            if ($prop['isOffsetPosition'] == 1 and !empty($prop['table_ref_name'])) {
                $layer_offset_field_lr = $prop['field_name'];
                $layer_offset_dict_lr = $prop['table_ref_name'];
            }
            if ($prop['isOffsetValue'] == 1) {
                $layer_offset_field_v = $prop['field_name'];
            }
            // unset($prop['isOffsetPosition']);
            // unset($prop['isOffsetValue']);
            unset($prop['layer_id'], $prop['type_layer']);
            array_push($data, $prop);
        }
        return ['success' => true,
            'statuscode' => 200,
            'totalcount' => count($data),
            'metaData' => [
                'id' => $layer_id,
                'title' => $layer_name,
                'objectDBName' => $layer_table_name,
                'geometry_type' => $layer_geometry,
                'offset_field_position' => $layer_offset_field_lr,
                'offset_dict_position' => $layer_offset_dict_lr,
                'offset_field_value' => $layer_offset_field_v,
                'icons' => $layer_icons,
                'fields' => $data]
        ];
    }

    public function updateMetaData($parsedParams, $parsedBody)
    {
        try {
            $layer_id = \Illuminate\Support\Arr::get($parsedParams, 'layer');
            if (empty($layer_id)) throw new \Exception('отсутствуют ИД ведомости');
            if (empty($parsedBody) or count($parsedBody) == 0) throw new \Exception('отсутствуют данные для добаления');
            $id = \Illuminate\Support\Arr::get($parsedBody, 'id');
            if (empty($id)) throw new \Exception('отсутствуют ИД записи');
            unset($parsedBody['id']); //удалю id

            Capsule::connection('roadinfo')->beginTransaction();
            Capsule::connection('roadinfo')->table('layer_attributes')->where('id', '=', $id)->update($parsedBody);
            Capsule::connection('roadinfo')->commit();

            return ['success' => true, 'statuscode' => 200, 'message' => 'выполнено успешно', 'metaData' => ['id' => $id]];
        } catch (\Exception $ee) {
            Capsule::connection('roadinfo')->rollback();
            return ['success' => false, 'statuscode' => 400, 'message' => $ee->getMessage()];
        }
    }

    public function updateInfoLayer($parsedParams, $parsedBody)
    {
        try {
            if (empty($parsedBody) or count($parsedBody) == 0) throw new \Exception('отсутствуют данные для добаления');
            $id = \Illuminate\Support\Arr::get($parsedBody, 'id');
            if (empty($id)) throw new \Exception('отсутствуют ИД записи');
            if (empty(\Illuminate\Support\Arr::get($parsedBody, 'geometry_type'))) $parsedBody['geometry_type'] = null;
            if (empty(\Illuminate\Support\Arr::get($parsedBody, 'dict_icons'))) $parsedBody['dict_icons'] = null;
            if (empty(\Illuminate\Support\Arr::get($parsedBody, 'proc_agr_name'))) $parsedBody['proc_agr_name'] = null;
            unset($parsedBody['id']); //удалю id

            Capsule::connection('roadinfo')->beginTransaction();
            Capsule::connection('roadinfo')->table('layers')->where('id', '=', $id)->update($parsedBody);
            Capsule::connection('roadinfo')->commit();

            $itemModel = (new modelLayers())::fixTree();

            return ['success' => true, 'statuscode' => 200, 'message' => 'выполнено успешно', 'metaData' => ['id' => $id]];
        } catch (\Exception $ee) {
            Capsule::connection('roadinfo')->rollback();
            return ['success' => false, 'statuscode' => 400, 'message' => $ee->getMessage()];
        }
    }

    public function layerList($parsedParams)
    {
        $results = Capsule::connection('roadinfo')->select('select * from layers;');
        if (empty($results) or empty($results[0])) throw new \Exception('список ведомостей пуст');

        $data = [];
        foreach ($results as $it) {
            array_push($data, get_object_vars($it));
        }
        $result = [
            'success' => true,
            'statuscode' => 200,
            'totalcount' => count($data),
            'data' => $data
        ];
        return $result;
    }

    public function layerSelect($parsedParams)
    {
        $layer = \Illuminate\Support\Arr::get($parsedParams, 'layer');
        $type_layer = \Illuminate\Support\Arr::get($parsedParams, 'type_layer');
        $objects = json_decode(\Illuminate\Support\Arr::get($parsedParams, 'objects_id'));
        if (empty($layer)) throw new \Exception('идентификатор ведомости не указан');
        if (empty($type_layer)) throw new \Exception('идентификатор типа ведомости не указан');
        if ((empty($objects) or count($objects) == 0)) throw new \Exception('для ведомости не указаны муниципальные районы');

        $results = Capsule::connection('roadinfo')->select('select table_name from layers where id = \'' . $layer . '\';');
        if (empty($results) or empty($results[0])) throw new \Exception('наименование ведомости ' . $layer . ' пусто');
        $layer = $results[0]->table_name;

        $results = Capsule::connection('roadinfo')->select('select * from layers where table_name = \'' . $layer . '\';');
        if (empty($results) or empty($results[0])) throw new \Exception('наименование ведомости ' . $layer . ' пусто');
        $layer_table_name = $type_layer == 1 ? $results[0]->table_name : $results[0]->proc_agr_name;

        if ($type_layer == 1) {
            $results = Capsule::connection('roadinfo')->table($layer_table_name)->whereNull('deleted_at')
                ->whereIn('object_id', $objects)->orderBy('object_id')->orderBy('km_beg')->orderBy('id')->get();
        } else { //хранимая процедура и параметр обжект
            $results = Capsule::connection('roadinfo')->select('call ' . $layer_table_name . '(?)', [implode(",", $objects)]);
        }

        $data = [];
        foreach ($results as $it) {
            array_push($data, get_object_vars($it));
        }
        $result = [
            'success' => true,
            'statuscode' => 200,
            'totalcount' => count($data),
            'data' => $data
        ];
        return $result;
    }

    private function getLayerIcons($layer_id)
    {
        $results = Capsule::connection('roadinfo')->table('layer_icons')
            ->where('layer_id', '=', $layer_id)->whereNotNull('where_param')
            ->whereNotNull('dict_icons')->whereNull('deleted_at')->get();
        $data = [];
        foreach ($results as $it) {
            $data[$it->dict_icons] = json_decode($it->where_param);
        }
        return $data;
    }

    private function getIconByProperties($layer_icon, $where_params, $property_item)
    {
        if (empty($where_params)) return $layer_icon;
        $res = $layer_icon;
        foreach ($where_params as $key => $param) {
            $item = $property_item;
            $param_ar = get_object_vars($param);
            $intersect_count = count(array_intersect($param_ar, $item));
            if ($intersect_count == count($param_ar)) { //если количество совпавших свойист записи и Условия равно количеству свойист условия
                $res = $key;
                break;
            }
        }
        return $res;
    }

    public function layerSelectWithGeometry($parsedParams, $parsedBody)
    {
        try {
            $layer = \Illuminate\Support\Arr::get($parsedParams, 'layer');
            //$differenticon = \Illuminate\Support\Arr::get($parsedParams, 'differenticon'];
            $objects_id = \Illuminate\Support\Arr::get($parsedBody, 'objects_id'); //для разных дорог или разных районов
            $rows_id = \Illuminate\Support\Arr::get($parsedBody, 'rows_id'); //если применили фильтр к таблице слоя, то только остащиеся записи !!
            if (empty($objects_id) and empty($rows_id)) {
                throw new \Exception('отсутствуют обязательные параметры');
            }
            if ( (is_array($objects_id) and count($objects_id) == 0) and (is_array($rows_id) and count($rows_id) == 0) ) {
                throw new \Exception('отсутствуют обязательные параметры');
            }

            /*if ($differenticon == true) { //разные иконки для каждой точки ???
                //если выбрана только одна дорога !!!!!
            }*/

            $parsedParams['type_layer'] = 1;
            $layer_meta = $this->getMetaData($parsedParams)['metaData'];
            if (empty($layer_meta['geometry_type'])) throw new \Exception('для ведомости не указан тип географического объекта');
            $layer_table_name = $layer_meta['objectDBName'];
            $layer_icons_where = $this->getLayerIcons($layer_meta['id']); //условия для иконок
            //if ($layer_meta['geometry_type'] == "LineString"){ //для линии возможно смещение от оси дороги
            $dict_offset_position = [];
            if (!empty($layer_meta['offset_field_position'])) {
                $dict_offset = (new reposDictionary((new \RKA\Session())->get('user_roadinfo', null)))->dictSelect(['dict' => $layer_meta['offset_dict_position']])['data'];
                foreach ($dict_offset as $it) {
                    $dict_offset_position[$it['code']] = [$it['offset'], $it['name']];
                }
            }
            //}

            if (!empty($rows_id) and count($rows_id) > 0) {
                $result_objects_ids = Capsule::connection('roadinfo')->table($layer_table_name)->whereNull('deleted_at')->whereIn('id', $rows_id)->distinct()->select('object_id')->get();
                $results = Capsule::connection('roadinfo')->table($layer_table_name)->whereNull('deleted_at')->whereIn('id', $rows_id)->orderBy('object_id')->orderBy('km_beg')->orderBy('id')->get();
            } elseif (!empty($objects_id) and count($objects_id) > 0) {
                $result_objects_ids = Capsule::connection('roadinfo')->table($layer_table_name)->whereNull('deleted_at')->whereIn('object_id', $objects_id)->distinct()->select('object_id')->get();
                $results = Capsule::connection('roadinfo')->table($layer_table_name)->whereNull('deleted_at')->whereIn('object_id', $objects_id)->orderBy('object_id')->orderBy('km_beg')->orderBy('id')->get();
            } else {
                $result_objects_ids = Capsule::connection('roadinfo')->table($layer_table_name)->whereNull('deleted_at')->distinct()->select('object_id')->get();
                $results = Capsule::connection('roadinfo')->table($layer_table_name)->whereNull('deleted_at')->orderBy('object_id')->orderBy('km_beg')->orderBy('id')->get();
            }

            //объекты содержания
            $arr_objects_ids = [];
            $arr_objects = [];
            foreach ($result_objects_ids as $it) {
                array_push($arr_objects_ids, $it->object_id);
                $object = (new modelObjects())::where('id', '=', $it->object_id)->get()->first();
                if (!empty($object->id)) {
                    $object = json_decode($object->yandex_properties);
                    $arr_objects[$it->object_id] = $object;
                };
            }

            $data = [];
            $object_id = null;
            $object = null;
            $ballonMaxSize = ['width' => 800, 'height' => 500];
            foreach ($results as $it) {
                $object = $arr_objects[$it->object_id]; //получу объект содержания
                if (empty($object) or empty($object->points_route_all) or count($object->points_route_all) == 0) continue;

                if ($layer_meta['geometry_type'] == "LineString") {
                    $geoobject = $this->getLine($it->km_beg, $it->km_end, $object->points_route_all);
                } else {
                    $geoobject = $this->getPoint($it->km_beg, $object->points_route_all);
                }

                if (!empty($geoobject)) {
                    $property_item = get_object_vars($it);
                    unset($property_item['_road_id__'], $property_item['_road_id_'], $property_item['created_at'],
                          $property_item['updated_at'], $property_item['deleted_at'], $property_item['object_id']); //удалю id

                    $iconHref = $this->getIconByProperties($layer_meta['icons'], $layer_icons_where, $property_item);

                    if ($layer_meta['geometry_type'] == "LineString" and count($geoobject) > 1) { //линия и точек на линии больше 1
                        if (count($dict_offset_position) > 0 and !empty($layer_meta['offset_field_value'])) {//указываем настройки смещения
                            $offset_val = intval($property_item[$layer_meta['offset_field_value']]);  //величина смещения в сторону
                            $offset_ = $dict_offset_position[$property_item[$layer_meta['offset_field_position']]]; // сторона смещения -1    0    1
                            $offsets = $offset_val * $offset_[0];
                        }
                        if (array_key_exists('km_beg', $property_item) and array_key_exists('km_end', $property_item)) {
                            $property_item['hintContent'] = 'км. ' . $property_item['km_beg'] . '-' . $property_item['km_end'] . ' ' . (empty($offset_) ? null : $offset_[1]);
                        } //добавлю подсказку на линию, с указанием начала и конца участка
                        $option = array(
                            'iconImageHref' => $iconHref,
                            'draggable' => false,
                            'opacity' => 0.8,
                            'strokeWidth' => 3,
                            'strokeColor' => '#0ABC22'
                        );
                        if (count($arr_objects_ids) == 1 and count($dict_offset_position) > 0) //Если одна дорога, то указываем смещение в сторону от оси дороги
                            $option['offsets'] = $offsets;
                        array_push($data, ['type' => 'Feature',
                            'layer_id' => $layer_meta['id'],
                            'id' => $it->id,
                            'geometry' => ['type' => $layer_meta['geometry_type'], 'coordinates' => $geoobject],
                            'properties' => $property_item,
                            'options' => $option
                        ]);
                    }

                    if ($layer_meta['geometry_type'] == "Point") { //точка
                        if (count($dict_offset_position) > 0) {
                            $offset_ = $dict_offset_position[$property_item[$layer_meta['offset_field_position']]]; // сторона смещения -1    0    1
                        }
                        if (array_key_exists('km_beg', $property_item)) {
                            $property_item['hintContent'] = 'км. ' . $property_item['km_beg'] . ' ' . (empty($offset_) ? null : $offset_[1]);
                        }
                        $option = array(
                            'iconLayout' => 'default#image',
                            'iconImageHref' => $iconHref,
                            'iconImageSize' => [24, 24],
                            // 'balloonMaxWidth' => 800,
                            // 'balloonMaxHeight' => 500,
                            'draggable' => false
                        );
                        if (count($arr_objects_ids) == 1 and count($dict_offset_position) > 0)//Если одна дорога, то указываем смещение в сторону от оси дороги
                            $option['iconImageOffset'] = [-20, -20];
                        array_push($data, ['type' => 'Feature',
                                        'layer_id' => $layer_meta['id'],
                                        'id' => $it->id,
                                        'geometry' => ['type' => $layer_meta['geometry_type'], 'coordinates' => $geoobject],
                                        'properties' => $property_item,
                                        'options' => $option
                                    ]);
                    }
                }
            }

            $clusterInfo = null;
            if ($layer_meta['geometry_type'] == "Point") {
                $clusterInfo = ['href' => $layer_meta['icons'], 'size' => [24, 24], 'offset' => [-10, -10]];
            }

            $result = [
                'success' => true,
                'statuscode' => 200,
                'totalcount' => count($data),
                'layer_id' => $layer_meta['id'],
                'layer_geometry_type' => $layer_meta['geometry_type'],
                'objects_id' => $arr_objects_ids,
                'clusterIcons' => $clusterInfo,
                'ballonMaxSize' => $ballonMaxSize,
                'type' => 'FeatureCollection',
                'features' => $data
            ];
            return $result;
        } catch (\Exception $ee) {
            return ['success' => false, 'statuscode' => 400, 'message' => $ee->getMessage()];
        }
    }

    public function layerUpdate($parsedParams, $parsedBody)
    {
        try {
            if (empty($parsedBody) or count($parsedBody) == 0) throw new \Exception('отсутствуют данные для добаления');
            $parsedParams['type_layer'] = 1;
            $layer_meta = $this->getMetaData($parsedParams)['metaData'];
            $attr = [];
            $layer_field_name_attachFile = null;
            foreach ($layer_meta['fields'] as $field) {
                $attr[$field['field_name']] = $field['type_name'];
                if ($field['type_name'] == '60') $layer_field_name_attachFile = $field['field_name']; //Установлю признак того, что у ведомости есть прикрепляемые файлы
            }
            if (empty($attr) or count($attr) == 0) throw new \Exception('отсутствует описание полей таблицы');
            $bodys = [];
            if (array_key_exists("id", $parsedBody)) {//если в массиве одна запись
                array_push($bodys, $parsedBody);
            } else { // если в массиве несколько добавляемых записей
                $bodys = $parsedBody;
            }

            $ignore_field_keys = ['file_content_value', ''];

            foreach ($bodys as $body) {
                //if (!empty($body['id'])) throw new \Exception('попытка добавить существующую запись');
                $id = \Illuminate\Support\Arr::get($body, 'id');
                unset($body['id']); //удалю id

                foreach (array_keys($body) as $pr) {//удаляю из пришедшей записи всякие служебные поля, КреатеДате, ид, оставлю только те поля которые в описании ведомости
                    if (in_array($pr, $ignore_field_keys)) {
                        //списко полей исключение, которые нельзя удалять из массива, т.к. их нет в описании таблицы
                    } else {
                        if (empty($attr[$pr])) {
                            unset($body[$pr]);
                        }
                    }
                }

                foreach (array_keys($body) as $pr) {
                    if (in_array($pr, $ignore_field_keys)) {
                        //список полей исключение, которые нельзя удалять из массива, т.к. их нет в описании таблицы
                    } else {
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
                            case '60':
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
                }

                ///обработка прикрепленного файла
                if ($layer_field_name_attachFile) { //если в ведомостьи есть поле содержащее прикрепленный файл
                    $file_name = $body[$layer_field_name_attachFile]; //прочитаю имя нового файла file_name
                    $file_content = \Illuminate\Support\Arr::get($body, 'file_content_value');  //прочитаю новое содержимое файла
                    unset($body['file_content_value']); //удалю пришедший file_content
                    unset($body[$layer_field_name_attachFile]); //удалю пришедший file_name
                    if (!empty($file_content) and !empty($file_name)) {  //если прикреплен файл
                        if (!empty($id)) //если есть идентификатор записи, то прочитаю имя прежднего файла
                            $old_file_name = Capsule::connection('roadinfo')->table($layer_meta['objectDBName'])
                                ->whereNotNull($layer_field_name_attachFile)->where('id', '=', $id)
                                ->select($layer_field_name_attachFile)->get();
                        if (!empty($old_file_name) and !empty($old_file_name[0])) {
                            $old_file_name = $old_file_name[0]->{$layer_field_name_attachFile};
                            unlink(UPLOADDIR . 'layer_attachments' . DS . $layer_meta['id'] . DS . $old_file_name); //удалю старый файл
                        }
                        $this->upload_file(UPLOADDIR . 'layer_attachments' . DS . $layer_meta['id'] . DS, $file_content, $file_name); //сгенерирую новый файл
                        $body[$layer_field_name_attachFile] = $file_name; //если пришел файл, то создам имя файла для вставки в таблицу
                    }
                }

                $body['updated_at'] = new \DateTime();
                $ids = [];
                try {
                    Capsule::connection('roadinfo')->beginTransaction();
                    Capsule::connection('roadinfo')->table($layer_meta['objectDBName'])->where('id', '=', $id)->update($body);
                    if ($layer_field_name_attachFile and !empty($file_name)) { //если у ведомости есть поле содержащее имя файла, то верну новое имя файла
                        array_push($ids, ['id' => $id/*, $layer_field_name_attachFile => 'uploads' . DS . 'layer_attachments' . DS . $file_name*/]); //также верну новое имя файла
                    } else {
                        array_push($ids, ['id' => $id]);
                    }
                    Capsule::connection('roadinfo')->commit();
                } catch (\Exception $ee) {
                    Capsule::connection('roadinfo')->rollback();
                    throw new \Exception($ee->getMessage());
                }
            }
            return ['success' => true, 'statuscode' => 200, 'message' => 'выполнено успешно', 'data' => $ids];
        } catch (\Exception $ee) {
            return ['success' => false, 'statuscode' => 400, 'message' => $ee->getMessage()];
        }
    }

    public function layerCreate($parsedParams, $parsedBody)
    {
        try {
            if (empty($parsedBody) or count($parsedBody) == 0) throw new \Exception('отсутствуют данные для добаления');
            $parsedParams['type_layer'] = 1;
            $layer_meta = $this->getMetaData($parsedParams)['metaData'];
            $attr = [];
            $layer_field_name_attachFile = null;
            foreach ($layer_meta['fields'] as $field) {
                $attr[$field['field_name']] = $field['type_name'];
                if ($field['type_name'] == '60') $layer_field_name_attachFile = $field['field_name']; //Установлю признак того, что у ведомости есть прикрепляемые файлы
            }
            if (empty($attr) or count($attr) == 0) throw new \Exception('отсутствует описание полей таблицы');
            $bodys = [];
            if (array_key_exists("id", $parsedBody)) {//если в массиве одна запись
                array_push($bodys, $parsedBody);
            } else { // если в массиве несколько добавляемых записей
                $bodys = $parsedBody;
            }

            $ignore_field_keys = ['file_content_value', ''];

            foreach ($bodys as $body) {
                //if (!empty($body['id'])) throw new \Exception('попытка добавить существующую запись');
                unset($body['id']); //удалю id
                $id = \Illuminate\Support\Arr::get($body, 'id');

                foreach (array_keys($body) as $pr) {//удаляю из пришедшей записи всякие служебные поля, КреатеДате, ид, оставлю только те поля которые в описании ведомости
                    if (in_array($pr, $ignore_field_keys)) {
                        //списко полей исключение, которые нельзя удалять из массива, т.к. их нет в описании таблицы
                    } else {
                        if (empty($attr[$pr])) {
                            unset($body[$pr]);
                        }
                    }
                }

                foreach (array_keys($body) as $pr) {
                    if (in_array($pr, $ignore_field_keys)) {
                        //список полей исключение, которые нельзя удалять из массива, т.к. их нет в описании таблицы
                    } else {
                        switch ($attr[$pr]) {
                            case '14':
                            case '15':
                            case '16':
                                if (empty($body[$pr])) {
                                    $body[$pr] = null;
                                } else {
                                    if (is_numeric($body[$pr])) {//если пришел Timestamp
                                        $date = new \DateTime();
                                        $body[$pr] = $date->setTimestamp($body[$pr]);
                                    } else {
                                        $body[$pr] = new \DateTime($body[$pr]);
                                    }
                                }
                                break;
                            case '13':
                            case '5':
                            case '18':
                            case '19':
                            case '60':
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
                }

                ///обработка прикрепленного файла
                if ($layer_field_name_attachFile) { //если в ведомостьи есть поле содержащее прикрепленный файл
                    $file_name = $body[$layer_field_name_attachFile]; //прочитаю имя нового файла file_name
                    $file_content = \Illuminate\Support\Arr::get($body, 'file_content_value');  //прочитаю новое содержимое файла
                    unset($body['file_content_value']); //удалю пришедший file_content
                    unset($body[$layer_field_name_attachFile]); //удалю пришедший file_name
                    if (!empty($file_content) and !empty($file_name)) {  //если прикреплен файл
                        if (!empty($id)) //если есть идентификатор записи, то прочитаю имя прежднего файла
                            $old_file_name = Capsule::connection('roadinfo')->table($layer_meta['objectDBName'])
                                ->whereNotNull($layer_field_name_attachFile)->where('id', '=', $id)->select($layer_field_name_attachFile)->get();
                        if (!empty($old_file_name) and !empty($old_file_name[0])) {
                            $old_file_name = $old_file_name[0]->{$layer_field_name_attachFile};
                            unlink(UPLOADDIR . 'layer_attachments' . DS . $layer_meta['id'] . DS . $old_file_name); //удалю старый файл
                        }
                        $this->upload_file(UPLOADDIR . 'layer_attachments' . DS . $layer_meta['id'] . DS, $file_content, $file_name); //сгенерирую новый файл
                        $body[$layer_field_name_attachFile] = $file_name; //если пришел файл, то создам имя файла для вставки в таблицу
                    }
                }

                $body['created_at'] = new \DateTime();
                $ids = [];
                try {
                    Capsule::connection('roadinfo')->beginTransaction();
                    $idd = Capsule::connection('roadinfo')->table($layer_meta['objectDBName'])->insertGetId($body);
                    if ($layer_field_name_attachFile and !empty($file_name)) { //если у ведомости есть поле содержащее имя файла, то верну новое имя файла
                        array_push($ids, ['id' => $idd/*, $layer_field_name_attachFile => 'uploads' . DS . 'layer_attachments' . DS . $file_name*/]); //также верну новое имя файла
                    } else {
                        array_push($ids, ['id' => $idd]);
                    }
                    Capsule::connection('roadinfo')->commit();
                } catch (\Exception $ee) {
                    Capsule::connection('roadinfo')->rollback();
                    throw new \Exception($ee->getMessage());
                }

            }
            return ['success' => true, 'statuscode' => 200, 'message' => 'ok', 'data' => $ids];
        } catch (\Exception $ee) {
            Capsule::connection('roadinfo')->rollback();
            return ['success' => false, 'statuscode' => 400, 'message' => $ee->getMessage()];
        }
    }

    public function layerDestroy($parsedParams, $parsedBody)
    {
        try {
            if (empty($parsedBody) or count($parsedBody) == 0) throw new \Exception('отсутствуют данные для удаления');
            $parsedParams['type_layer'] = 1;
            $layer_meta = $this->getMetaData($parsedParams)['metaData'];
            $bodys = [];
            if (array_key_exists("id", $parsedBody)) {//если в массиве одна запись
                array_push($bodys, $parsedBody);
            } else { // если в массиве несколько добавляемых записей
                $bodys = $parsedBody;
            }
            foreach ($bodys as $body) {
                $id = \Illuminate\Support\Arr::get($body, 'id');

                $body = ['deleted_at' => new \DateTime()];
                $ids = [];
                try {
                    Capsule::connection('roadinfo')->beginTransaction();
                    Capsule::connection('roadinfo')->table($layer_meta['objectDBName'])->where('id', '=', $id)->update($body);
                    array_push($ids, ['id' => $id]);
                    Capsule::connection('roadinfo')->commit();
                } catch (\Exception $ee) {
                    Capsule::connection('roadinfo')->rollback();
                    throw new \Exception($ee->getMessage());
                }

            }
            return ['success' => true, 'statuscode' => 200, 'message' => 'ok', 'data' => $ids];
        } catch (\Exception $ee) {
            return ['success' => false, 'statuscode' => 400, 'message' => $ee->getMessage()];
        }
    }


    private function upload_file($directory, $encoded_string, $filename)
    {
        if (!empty($encoded_string)) {
            $find = strpos($encoded_string, ';base64,');  // уберу всякие там варианты 'data:image/jpeg;base64,'
            if ($find !== false) {
                $encoded_string = substr($encoded_string, $find + 8);
            }
            $decoded_file = base64_decode($encoded_string); // decode the file
            //$mime_type = finfo_buffer(finfo_open(), $decoded_file, FILEINFO_MIME_TYPE); // extract mime type
            //$extension = $this->mime2ext($mime_type); // extract extension from mime type
            //$file = bin2hex(random_bytes(8)) . '.' . $extension; // rename file as a unique name
            if (!is_dir($directory)) {
                mkdir($directory);
            }

            $file_dir = $directory . $filename;
            file_put_contents($file_dir, $decoded_file); // save
            return $filename;
        }
    }

    private function mime2ext($mime)
    {
        $all_mimes = '{"png":["image\/png","image\/x-png"],"bmp":["image\/bmp","image\/x-bmp",
    "image\/x-bitmap","image\/x-xbitmap","image\/x-win-bitmap","image\/x-windows-bmp",
    "image\/ms-bmp","image\/x-ms-bmp","application\/bmp","application\/x-bmp",
    "application\/x-win-bitmap"],"gif":["image\/gif"],"jpg":["image\/jpg"],"jpeg":["image\/jpeg",
    "image\/pjpeg"],"xspf":["application\/xspf+xml"],"vlc":["application\/videolan"],
    "wmv":["video\/x-ms-wmv","video\/x-ms-asf"],"au":["audio\/x-au"],
    "ac3":["audio\/ac3"],"flac":["audio\/x-flac"],"ogg":["audio\/ogg",
    "video\/ogg","application\/ogg"],"kmz":["application\/vnd.google-earth.kmz"],
    "kml":["application\/vnd.google-earth.kml+xml"],"rtx":["text\/richtext"],
    "rtf":["text\/rtf"],"jar":["application\/java-archive","application\/x-java-application",
    "application\/x-jar"],"zip":["application\/x-zip","application\/zip",
    "application\/x-zip-compressed","application\/s-compressed","multipart\/x-zip"],
    "7zip":["application\/x-compressed"],"xml":["application\/xml","text\/xml"],
    "svg":["image\/svg+xml"],"3g2":["video\/3gpp2"],"3gp":["video\/3gp","video\/3gpp"],
    "mp4":["video\/mp4"],"m4a":["audio\/x-m4a"],"f4v":["video\/x-f4v"],"flv":["video\/x-flv"],
    "webm":["video\/webm"],"aac":["audio\/x-acc"],"m4u":["application\/vnd.mpegurl"],
    "pdf":["application\/pdf","application\/octet-stream"],
    "pptx":["application\/vnd.openxmlformats-officedocument.presentationml.presentation"],
    "ppt":["application\/powerpoint","application\/vnd.ms-powerpoint","application\/vnd.ms-office",
    "application\/msword"],"docx":["application\/vnd.openxmlformats-officedocument.wordprocessingml.document"],
    "xlsx":["application\/vnd.openxmlformats-officedocument.spreadsheetml.sheet","application\/vnd.ms-excel"],
    "xl":["application\/excel"],"xls":["application\/msexcel","application\/x-msexcel","application\/x-ms-excel",
    "application\/x-excel","application\/x-dos_ms_excel","application\/xls","application\/x-xls"],
    "xsl":["text\/xsl"],"mpeg":["video\/mpeg"],"mov":["video\/quicktime"],"avi":["video\/x-msvideo",
    "video\/msvideo","video\/avi","application\/x-troff-msvideo"],"movie":["video\/x-sgi-movie"],
    "log":["text\/x-log"],"txt":["text\/plain"],"css":["text\/css"],"html":["text\/html"],
    "wav":["audio\/x-wav","audio\/wave","audio\/wav"],"xhtml":["application\/xhtml+xml"],
    "tar":["application\/x-tar"],"tgz":["application\/x-gzip-compressed"],"psd":["application\/x-photoshop",
    "image\/vnd.adobe.photoshop"],"exe":["application\/x-msdownload"],"js":["application\/x-javascript"],
    "mp3":["audio\/mpeg","audio\/mpg","audio\/mpeg3","audio\/mp3"],"rar":["application\/x-rar","application\/rar",
    "application\/x-rar-compressed"],"gzip":["application\/x-gzip"],"hqx":["application\/mac-binhex40",
    "application\/mac-binhex","application\/x-binhex40","application\/x-mac-binhex40"],
    "cpt":["application\/mac-compactpro"],"bin":["application\/macbinary","application\/mac-binary",
    "application\/x-binary","application\/x-macbinary"],"oda":["application\/oda"],
    "ai":["application\/postscript"],"smil":["application\/smil"],"mif":["application\/vnd.mif"],
    "wbxml":["application\/wbxml"],"wmlc":["application\/wmlc"],"dcr":["application\/x-director"],
    "dvi":["application\/x-dvi"],"gtar":["application\/x-gtar"],"php":["application\/x-httpd-php",
    "application\/php","application\/x-php","text\/php","text\/x-php","application\/x-httpd-php-source"],
    "swf":["application\/x-shockwave-flash"],"sit":["application\/x-stuffit"],"z":["application\/x-compress"],
    "mid":["audio\/midi"],"aif":["audio\/x-aiff","audio\/aiff"],"ram":["audio\/x-pn-realaudio"],
    "rpm":["audio\/x-pn-realaudio-plugin"],"ra":["audio\/x-realaudio"],"rv":["video\/vnd.rn-realvideo"],
    "jp2":["image\/jp2","video\/mj2","image\/jpx","image\/jpm"],"tiff":["image\/tiff"],
    "eml":["message\/rfc822"],"pem":["application\/x-x509-user-cert","application\/x-pem-file"],
    "p10":["application\/x-pkcs10","application\/pkcs10"],"p12":["application\/x-pkcs12"],
    "p7a":["application\/x-pkcs7-signature"],"p7c":["application\/pkcs7-mime","application\/x-pkcs7-mime"],
    "p7r":["application\/x-pkcs7-certreqresp"],"p7s":["application\/pkcs7-signature"],
    "crt":["application\/x-x509-ca-cert","application\/pkix-cert"],"crl":["application\/pkix-crl","application\/pkcs-crl"],
    "pgp":["application\/pgp"],"gpg":["application\/gpg-keys"],"rsa":["application\/x-pkcs7"],"ics":["text\/calendar"],"zsh":["text\/x-scriptzsh"],
    "cdr":["application\/cdr","application\/coreldraw","application\/x-cdr","application\/x-coreldraw","image\/cdr","image\/x-cdr","zz-application\/zz-winassoc-cdr"],
    "wma":["audio\/x-ms-wma"],"vcf":["text\/x-vcard"],"srt":["text\/srt"],"vtt":["text\/vtt"],"ico":["image\/x-icon","image\/x-ico","image\/vnd.microsoft.icon"],
    "csv":["text\/x-comma-separated-values","text\/comma-separated-values","application\/vnd.msexcel"],"json":["application\/json","text\/json"]}';
        $all_mimes = json_decode($all_mimes, true);
        foreach ($all_mimes as $key => $value) {
            if (array_search($mime, $value) !== false) return $key;
        }
        throw new \Exception('не известный тип файла');
    }


//определение точки отдаленной от начальной на заданом расстоянии и в направлении к следующей точке
    private function solveDirectProblem($pointStart, $pointNext, float $distanceMeters)
    {
        //расчет азимута по двум точкам
        /* $direction = [$pointNext[0] - $pointStart[0], $pointNext[1] - $pointStart[1]];
         $bearing = ($direction[1] / $direction[0]) + 180;
        */
        $lat1 = deg2rad($pointStart[0]);
        $lat2 = deg2rad($pointNext[0]);
        $lng1 = deg2rad($pointStart[1]);
        $lng2 = deg2rad($pointNext[1]);
        $y = sin($lng2 - $lng1) * cos($lat2);
        $x = cos($lat1) * sin($lat2) - sin($lat1) * cos($lat2) * cos($lng2 - $lng1);
        $bearing = rad2deg(atan2($y, $x));
        if ($bearing < 0) {
            $bearing = fmod($bearing + 360, 360);
        }

        $D = $distanceMeters / 6371009.0;
        $B = deg2rad($bearing);
        $φ = deg2rad($pointStart[0]);
        $λ = deg2rad($pointStart[1]);

        $Φ = asin(sin($φ) * cos($D) + cos($φ) * sin($D) * cos($B));
        $Λ = $λ + atan2(sin($B) * sin($D) * cos($φ), cos($D) - sin($φ) * sin($φ));

        return [rad2deg($Φ), rad2deg($Λ)];
    }

    private function getCloseKm($arrayPoints, $km_point)
    {
        //find middle coordinate
        $targetPoint = [];
        for ($i = 0; $i < count($arrayPoints); $i++) {
            if ($i != 0)
                $coord_ = $arrayPoints[$i - 1];
            else
                $coord_ = $arrayPoints[$i];
            $coord = $arrayPoints[$i];
            if ($coord[2] >= $km_point) {
                if ((!empty($coord_)) and (($km_point - $coord_[2]) > ($coord[2] - $km_point))) { //определю какая точка ближе, предыдущая или следующая
                    $targetPoint = $coord;
                    array_push($targetPoint, $i); //добавлю ссылку на индекс элемента в массиве точек
                    array_push($targetPoint, round(($coord[2] - $km_point) * 1000, 3)); //добавлю дистанцию до объекта
                } else {
                    $targetPoint = $coord_;
                    array_push($targetPoint, ($i > 0 ? $i - 1 : 0)); //добавлю ссылку на индекс элемента в массиве точек
                    array_push($targetPoint, round(($coord_[2] - $km_point) * 1000, 3)); //добавлю дистанцию до объекта
                }

                break;
            }
        }

        return $targetPoint;
    }

    private function getPoint($km, $object_points)
    {
        $clossetPoint = $this->getCloseKm($object_points, $km);
        if (empty($clossetPoint)) {
            return null;
            //throw new \Exception('Невозможно определить ближайшую точку оси объекта');
        }
        $itogPoint = [];
        if ($clossetPoint[4] == 0) {
            $itogPoint = [$clossetPoint[0], $clossetPoint[1], $clossetPoint[2]];
        } elseif ($clossetPoint[4] > 0) { //ближайшая точка оси находится после нужного километража
            //движемся от бижайшей точке к предыдущей (в начало оси)
            $indexPoint = $clossetPoint[3];
            $pointStart = $object_points[$indexPoint];
            $pointNext = ($indexPoint <= 0) ? null : $object_points[$indexPoint - 1];
            $dist = abs($clossetPoint[4]);
            $itogPoint = $this->solveDirectProblem($pointStart, $pointNext, $dist);
            $itogPoint = [$itogPoint[0], $itogPoint[1], floatval($km)];
        } else {// ближайшая точка оси находится перед нужным километражем
            //движемся от бижайшей точке к следующей (в конец оси)
            $indexPoint = $clossetPoint[3];
            $pointStart = $object_points[$indexPoint];
            $pointNext = $object_points[$indexPoint + 1];
            $dist = abs($clossetPoint[4]);
            $itogPoint = $this->solveDirectProblem($pointStart, $pointNext, $dist);
            $itogPoint = [$itogPoint[0], $itogPoint[1], floatval($km)];
        }

        if (empty($itogPoint)) {
            return null;
            //throw new \Exception('Точка оси по киллометражу не определена');
        } else
            return $itogPoint;
    }

    private function getLine($km_beg, $km_end, $object_points)
    {
        $itogPoints = [];
        $firstPoint = $this->getPoint($km_beg, $object_points);
        if (!empty($firstPoint)) array_push($itogPoints, $firstPoint);
        foreach ($object_points as $p) {
            if ($p[2] > $km_beg and $p[2] < $km_end) {
                array_push($itogPoints, $p);
            }
        }
        $lastPoint = $this->getPoint($km_end, $object_points);
        if (!empty($lastPoint)) array_push($itogPoints, $lastPoint);

        if (count($itogPoints) == 0) {
            return null;
        } else
            return $itogPoints;
    }

}


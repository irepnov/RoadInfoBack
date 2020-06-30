<?php

namespace Roadinfo\Repositories;

use Roadinfo\Eloquent\modelLayers;
use Roadinfo\Eloquent\modelObjects;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Carbon;
use Kalnoy\Nestedset\NodeTrait;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use DiDom\Document;


class reposInfo
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

    //получить списко Муниципальных образование
    public function getMunobr($parsedParams)
    {
        try {
            $objects = json_decode(\Illuminate\Support\Arr::get($parsedParams, 'object_ids'));

            $items = [];
            $itemModel = new \Roadinfo\Eloquent\modelMunobr();

            if (empty($objects)) { //без ограничений, все разрещенные районы
                $data = $itemModel::select('id', 'name', 'geo_json', 'color_hex')->whereIn('id', $this->user->munobrs)->orderBy('name')->get();
                if (count($data) > 0) $items[] = ['id' => 0, 'name' => 'ВСЕ'];
                foreach ($data as $item) {
                    $objectsID = [];
                    foreach ($item->ObjectsId() as $obj) {
                        $objectsID[] = $obj->id;
                    };
                    $items[] = ['id' => $item->id,
                        'name' => $item->name,
                        'geo_json' => $item->geo_json,
                        'objects_id' => $objectsID,
                        'color_hex' => $item->color_hex];
                }
            } else { //когда ограничены районы и дороги определенным списком дорог
                $munobrForObjects = modelObjects::whereIn('id', $objects)->distinct()->select('munobr_id')->pluck('munobr_id')->toArray();
                $data = $itemModel::select('id', 'name', 'geo_json', 'color_hex')
                    ->whereIn('id', $this->user->munobrs)
                    ->whereIn('id', $munobrForObjects)
                    ->orderBy('name')
                    ->get();

                if (count($data) > 0) $items[] = ['id' => 0, 'name' => 'ВСЕ'];
                foreach ($data as $item) {
                    $objectsID = [];
                    foreach ($item->ObjectsId() as $obj) {
                        if (in_array($obj->id, $objects)){
                            $objectsID[] = $obj->id; //добавить вхождение в ограниченный список дорог
                        }
                    };
                    $items[] = ['id' => $item->id,
                        'name' => $item->name,
                        'geo_json' => $item->geo_json,
                        'objects_id' => $objectsID,
                        'color_hex' => $item->color_hex];
                }

            }

            $itemModel = null;
            return ['success' => true, 'statuscode' => 200, 'data' => $items];
        } catch (\Exception $ee) {
            return ['success' => false, 'statuscode' => 400, 'message' => $ee->getMessage()];
        }
    }

    //получить списко Объектов содержания
    public function getObjects($parsedParams)
    {
        try {
            $munobr = json_decode(\Illuminate\Support\Arr::get($parsedParams, 'munobr_ids'));
            $objects = json_decode(\Illuminate\Support\Arr::get($parsedParams, 'object_ids'));
            $notIncludeAll = json_decode(\Illuminate\Support\Arr::get($parsedParams, 'not_include_all'));
            if (empty($munobr) and empty($objects)) throw new \Exception('Не указан список районов или список объектов содержания');

            $itemModel = new \Roadinfo\Eloquent\modelObjects();
            $data = $itemModel::select('id', 'name', 'km_beg', 'km_end', 'munobr_id')->orderBy('name', 'asc');

            if (!empty($objects)) { //если конкретные дороги, то только их
                $data = $data->whereIn('id', $objects)->whereIn('munobr_id', $this->user->munobrs); //иначе конкретные
            } else { //иначе фильтр по районам
                if (in_array(0, $munobr)) { //выбрано ВСЕ
                    $data = $data->whereIn('munobr_id', $this->user->munobrs); //тогда разрещенные муниципальные образования
                } else {
                    $data = $data->whereIn('munobr_id', $munobr); //иначе конкретные
                }
            }

            $data = $data->get()->all();

            $items = [];
            if ($notIncludeAll !== 1 and count($data) > 0)
                $items[] = ['id' => 0, 'nameshort' => 'ВСЕ', 'munobr' => 'отмеченные', 'munobr_id' => $munobr, 'km_beg' => 0, 'km_end' => 0, 'name' => 'ВСЕ'];
            foreach ($data as $item) {
                $items[] = ['id' => $item->id,
                    'nameshort' => $item->name,
                    'munobr' => empty($item->Munobraz()) ? null : $item->Munobraz()->name,
                    'munobr_id' => [$item->munobr_id],
                    'km_beg' => $item->km_beg,
                    'km_end' => $item->km_end,
                    'name' => $item->name . ', км ' . $item->km_beg . ' - ' . $item->km_end
                ];
            }

            $itemModel = null;
            return ['success' => true, 'statuscode' => 200, 'totalcount' => count($data), 'data' => $items];
        } catch (\Exception $ee) {
            return ['success' => false, 'statuscode' => 400, 'message' => $ee->getMessage()];
        }
    }

    //получить списко Объектов содержания c yandex координатами
    //"&objects_id=" + Ext.JSON.encode(objects_id)
    public function getObjectsYandex($parsedParams)
    {
        try {
            $object = json_decode(\Illuminate\Support\Arr::get($parsedParams, 'objects_id'));

            if (empty($object)) throw new \Exception('Не указан список желаемых объектов');
            // if (in_array(0, $object) and empty($munobr)) throw new \Exception('Для позиции ВСЕ объекты, не указан список муницпальных образований');

            $itemModel = new \Roadinfo\Eloquent\modelObjects();
            $data = $itemModel::select('id', 'yandex_properties', 'munobr_id')
                ->whereNotNull('yandex_properties')
                ->orderBy('id', 'asc');
            $data = $data->whereIn('id', $object);
            $data = $data->get()->all();

            $items = [];
            foreach ($data as $item) {
                $items[] = ['id' => $item->id,
                    'munobr_color' => empty($item->Munobraz()) ? null : $item->Munobraz()->color_hex,
                    'yandex_properties' => $item->yandex_properties
                ];
            }
            $itemModel = null;
            return ['success' => true, 'statuscode' => 200, 'totalcount' => count($data), 'data' => $items];
        } catch (\Exception $ee) {
            return ['success' => false, 'statuscode' => 400, 'message' => $ee->getMessage()];
        }
    }

    //обновить дерево Слоев
    public function getUpdateLayers()
    {
        try {
            $itemModel = (new modelLayers())::fixTree();
            return ['success' => true, 'statuscode' => 200];
        } catch (\Exception $ee) {
            return ['success' => false, 'statuscode' => 400, 'message' => $ee->getMessage()];
        }
    }

    private function createTree($arrays, $isCheckedParent)
    {
        $result = [];
        foreach ($arrays as $item) {
            if (!empty($item)) {
                if ($item->children->count() == 0) {

                    $result[] = [
                        'expanded' => false,
                        'id' => $item->id,
                        'parent_id' => $item->parent_id,
                        'checked' => false,
                        'text' => $item->name,
                        'iconHref' => $item->dict_icons,
                        'table_name' => $item->table_name,
                        'proc_agr_name' => $item->proc_agr_name,
                        'geometry_type' => $item->geometry_type,
                        'leaf' => ($item->children->count() == 0),
                        'children' => $this->createTree($item->children, $isCheckedParent)
                    ];
                } else {
                    $item = [
                        'expanded' => empty($item->parent_id),
                        'id' => $item->id,
                        'parent_id' => $item->parent_id,
                        'text' => $item->name,
                        'iconHref' => $item->dict_icons,
                        'table_name' => $item->table_name,
                        'proc_agr_name' => $item->proc_agr_name,
                        'geometry_type' => $item->geometry_type,
                        'leaf' => ($item->children->count() == 0),
                        'children' => $this->createTree($item->children, $isCheckedParent)
                    ];
                    if ($isCheckedParent) $item['checked'] = false; //если разрещено включение дочерних Узлов через родительский узел
                    $result[] = $item;
                }

            }
        }
        return $result;
    }

    //получить дерево слоев
    public function getLayersTree($parsedParams)
    {
        $foruser = \Illuminate\Support\Arr::get($parsedParams, 'foruser');
        if ($foruser == 0) {
            $data = (new modelLayers())::descendantsAndSelf(-1)->toTree();
            $isCheckedParent = true;
        } else {
            $isCheckedParent = false;
            $lays = [];
            $laysWithData = Capsule::connection('roadinfo')->table('layers')->whereNotNull('table_name')->whereIn('id', $this->user->layers)->select('id')->pluck('id')->toArray();
            foreach ($laysWithData as $lay) {
                $arr = (new modelLayers())::ancestorsAndSelf($lay)->pluck('id')->toArray();
                $lays = array_merge($lays, $arr);
            }
            $data = (new modelLayers())::whereIn('id', array_unique($lays))->descendantsAndSelf(-1)->toTree();
        }

        return ['success' => true, 'statuscode' => 200, 'children' => $this->createTree($data, $isCheckedParent)];
    }


    public function getLayerWithObjects($parsedParams)
    {
        function name_sort($x, $y)
        {
            return strcasecmp($x['имя'], $y['имя']);
        }

        try {
            $layer_ids = json_decode(\Illuminate\Support\Arr::get($parsedParams, 'layers_id'));

            if (empty($layer_ids)) throw new \Exception('Не указан список желаемых элементов');

            $itemModel = new \Roadinfo\Eloquent\modelLayersObjects();
            $data = $itemModel::whereIn('layer_id', [$layer_ids])->get()->all();

            $items = [];
            foreach ($data as $item) {
                $object = $item->Object();
                $munobraz = (empty($object) or empty($object->Munobraz())) ? null : $object->Munobraz();

                if (!empty($object) and in_array($munobraz->id, $this->user->munobrs)) {
                    $items[] = ['id' => $item->id,
                        'munobr_id' => $munobraz->id,
                        'munobr_name' => $munobraz->name,
                        'object_id' => $object->id,
                        'object_name' => $object->name . ', км ' . $object->km_beg . ' - ' . $object->km_end,
                        'layer_id' => empty($item->Layer()) ? null : $item->Layer()->id,
                        'layer_name' => empty($item->Layer()) ? null : $item->Layer()->name,
                        'counts' => $item->counts
                    ];
                }
            }
            $itemModel = null;

            /* uasort($items, function ($x, $y) {
                 return strcasecmp($x['munobr_name'], $y['munobr_name']);
             });*/
            uasort($items, function ($a, $b) {
                $la = mb_substr($a['munobr_name'], 0, 1, 'utf-8');
                $lb = mb_substr($b['munobr_name'], 0, 1, 'utf-8');
                if (ord($la) > 122 && ord($lb) > 122) {
                    return $a['munobr_name'] > $b['munobr_name'] ? 1 : -1;
                }
                if (ord($la) > 122 || ord($lb) > 122) {
                    return $a['munobr_name'] < $b['munobr_name'] ? 1 : -1;
                }
            });
            $items_sort = [];
            foreach ($items as $item) {
                $items_sort[] = $item;
            }

            return ['success' => true, 'statuscode' => 200, 'totalcount' => count($items_sort), 'data' => $items_sort];
        } catch (\Exception $ee) {
            return ['success' => false, 'statuscode' => 400, 'message' => $ee->getMessage()];
        }
    }

}


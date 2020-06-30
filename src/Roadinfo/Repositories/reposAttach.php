<?php

namespace Roadinfo\Repositories;

use Roadinfo\Eloquent\modelAttachment_type;
use Roadinfo\Eloquent\modelLayers;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Carbon;
use Kalnoy\Nestedset\NodeTrait;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use DiDom\Document;


class reposAttach
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

    //получить список документов Объекта содержания
    public function getAttachList($parsedParams)
    {
        try {
            $object_id = json_decode(\Illuminate\Support\Arr::get($parsedParams, 'object_id'));
            if (empty($object_id)) throw new \Exception('Не указан объект содержания');

            $itemModel = new \Roadinfo\Eloquent\modelAttachments();
            $data = $itemModel::
            select('id', 'desc', 'name', 'mime', 'size', 'object_id', 'attachment_type_id', 'user_id', 'file_created_at', /*'path',*/ 'km_beg')
                ->where('object_id', '=', $object_id)
                ->orderBy('name', 'asc');
            $data = $data->get()->all();

            $items = [];
            foreach ($data as $item) {
                $items[] = [
                    'id' => $item->id,
                    'object_id' => $item->object_id,
                    'desc' => $item->desc,
                    'name' => $item->name,
                    'path' => 'uploads' . DS . 'doc_attachments' . DS . $item->name,
                    'km_beg' => $item->km_beg,
                    'mime' => $item->mime,
                    'size' => round($item->size / 1024 / 1024, 2),
                    'attachment_type_id' => $item->attachment_type_id,
                    'user_id' => $item->user_id,
                    'file_created_at' => $item->file_created_at];
            }
            $itemModel = null;
            return ['success' => true, 'statuscode' => 200, 'totalcount' => count($data), 'data' => $items];
        } catch (\Exception $ee) {
            return ['success' => false, 'statuscode' => 400, 'message' => $ee->getMessage()];
        }
    }

    private function moveUploadedFile($directory, $uploadedFile, $name_file)
    {
        if (!file_exists($uploadedFile->file)) {
            throw new \Exception('временный файл отсутствует ' . $uploadedFile->file);
            return;
        }
        if (empty($name_file)) {
            $file_correct_name = str_replace(' ', '_', $uploadedFile->getClientFilename());
            $file_correct_name_ext = str_replace('.', '_', pathinfo($file_correct_name,PATHINFO_EXTENSION)); //расширение
            $file_correct_name = str_replace('.', '_', pathinfo($file_correct_name,PATHINFO_FILENAME)); //без расширения
            $file_correct_name = str_replace(',', '_', $file_correct_name);
            $file_correct_name = str_replace('!', '_', $file_correct_name);
            $file_correct_name = str_replace('-', '_', $file_correct_name);
            $rand = bin2hex(random_bytes(3));
            $file_correct_name = mb_substr($file_correct_name, 0, 30) . '_' . $rand . '.' . $file_correct_name_ext;
        } else {
            $file_correct_name = $name_file;
        }

        $filename = $directory . $file_correct_name;
        $uploadedFile->moveTo($filename);
        $filename = str_replace(PUBLICDIR, '', $filename);

        return $filename;
    }

    //прикрепить документ к Объекту содержания
    public function addAttachFile($parsedBody)
    {
        try {
            $object_id = json_decode(\Illuminate\Support\Arr::get($parsedBody, 'attach_object_id'));
            $desc = \Illuminate\Support\Arr::get($parsedBody, 'attach_desc');
            $id = json_decode(\Illuminate\Support\Arr::get($parsedBody, 'attach_file_id'));
            $file = \Illuminate\Support\Arr::get($parsedBody, 'uploaded_files')['items']['attach_file'];
            $km_beg = json_decode(\Illuminate\Support\Arr::get($parsedBody, 'attach_km_beg'));;
            $coord = \Illuminate\Support\Arr::get($parsedBody, 'attach_coord');
            $attach_file_create_date = \Illuminate\Support\Arr::get($parsedBody,'attach_file_create_date');

            if (empty($object_id)) throw new \Exception('Не указан объект содержания');
            if (empty($desc)) throw new \Exception('Не указан объект содержания');
            if (!empty($file) and $file->getError() === UPLOAD_ERR_OK) { //заменю новым
                $file_mime = $file->getClientMediaType();
                if (strpos($file_mime, 'image/') !== false){
                    $attachment_type_id = 3;
                }
                if (strpos($file_mime, 'video/') !== false){
                    $attachment_type_id = 4;
                }
                if (strpos($file_mime, 'application/pdf') !== false){
                    $attachment_type_id = 2;
                }
                if (strpos($file_mime, 'application/x-zip-compressed') !== false or strpos($file_mime, 'application/octet-stream') !== false){
                    $attachment_type_id = 5;
                }
                if (!empty($attach_file_create_date)) $attach_file_create_date = \DateTime::createFromFormat('Y-m-d H:i:s', $attach_file_create_date);
               // $file_name = $file->getClientFilename();
                $file_size = $file->getSize();
                $file_name =pathinfo($this->moveUploadedFile(UPLOADDIR . 'doc_attachments' . DS, $file,null),PATHINFO_BASENAME);
            }

            if (empty($id)) $itemModel = new \Roadinfo\Eloquent\modelAttachments();
            if (!empty($id)) $itemModel = (new \Roadinfo\Eloquent\modelAttachments())->where('id', '=', $id)->first();
            if (!empty($id) and empty($itemModel)) throw new \Exception('Файл с идентификатором не найден');

            $itemModel->desc = $desc;
            if (!empty($file_name)) $itemModel->name = $file_name; //если прикреплен файл
            if (!empty($file_mime)) $itemModel->mime = $file_mime; //если прикреплен файл
            if (!empty($file_size)) $itemModel->size = $file_size; //если прикреплен файл
            if (!empty($attachment_type_id)) $itemModel->attachment_type_id = $attachment_type_id; //если прикреплен файл
            if (!empty($attach_file_create_date)) $itemModel->file_created_at = $attach_file_create_date; //если прикреплен файл
            $itemModel->object_id = $object_id;
            $itemModel->km_beg = $km_beg;
            $itemModel->coordinates = $coord;
            $itemModel->user_id = $this->user->id;
            $itemModel->Save();

            //сохраняем в БД блоб поле
//            if (empty($object_id)) throw new \Exception('Не указан объект содержания');
//            if (empty($desc)) throw new \Exception('Не указан объект содержания');
//            if ($file->getError() === UPLOAD_ERR_OK) { //заменю новым
//                $file_blob = $file->getStream()->getContents();
//                $file_mime = $file->getClientMediaType();
//                $file_name = $file->getClientFilename();
//                $file_size = $file->getSize();
//            } else throw new \Exception('Файл не прикреплен');
//
//            if (empty($id)) $itemModel = new \Roadinfo\Eloquent\modelAttachments();
//            if (!empty($id)) $itemModel = (new \Roadinfo\Eloquent\modelAttachments())->where('id', '=', $id)->first();
//            if (!empty($id) and empty($itemModel)) throw new \Exception('Файл с идентификатором не найден');
//
//            $itemModel->desc = $desc;
//            $itemModel->name = $file_name;
//            $itemModel->file = $file_blob;
//            $itemModel->mime = $file_mime;
//            $itemModel->size = $file_size;
//            $itemModel->object_id = $object_id;
//            $itemModel->Save();

            //download файла из блоб поля БД
//            public function getAttachFile($parsedBody)
//            {
//                $id = json_decode($parsedBody['id']);
//                $name = \Illuminate\Support\Arr::get($parsedBody, 'name'];
//                if (empty($id)) throw new \Exception('Не указан ИД файла');
//                if (empty($name)) throw new \Exception('Не указан имя файла');
//
//                $itemModel = new \Roadinfo\Eloquent\modelAttachments();
//                $data = $itemModel::
//                select('id', 'desc', 'name', 'mime', 'size', 'object_id', 'file')
//                    ->where('id', '=', $id)
//                    ->where('name', '=', $name);
//                $data = $data->get()->first();
//                if (empty($data)) throw new \Exception('Указанный файл не найден');
//
//                return ['success' => true, 'statuscode' => 200, 'data' => $data->attributesToArray()];
//            }

             //Маршрут download файла из блоб поля БД
//            try{
//                $parsedParams = $request->getQueryParams();
//                $repos = (new reposAttach((new \RKA\Session())->get('user_roadinfo', null)))->getAttachFile($parsedParams);
//
//                $stream = fopen('php://memory','r+');
//                fwrite($stream, $repos['data']['file']);
//                rewind($stream);
//
//                $streamslim = new \Slim\Http\Stream($stream);
//
//                return $response->withHeader('Content-Type', 'application/force-download')
//                    ->withHeader('Content-Type', 'application/octet-stream')
//                    ->withHeader('Content-Type', 'application/download')
//                    ->withHeader('Content-Description', 'File Transfer')
//                    ->withHeader('Content-Transfer-Encoding', 'binary')
//                    ->withHeader('Content-Disposition', 'attachment; filename="' . $repos['data']['name'] . '"')
//                    ->withHeader('Expires', '0')
//                    ->withHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
//                    ->withHeader('Pragma', 'public')
//                    ->withBody($streamslim); // all stream contents will be sent to the response
//
//            } catch (\Exception $ee) {
//                $statuscode = 400;
//                return $response->withStatus($statuscode)
//                    ->withHeader('Content-Type', 'application/json')
//                    ->write(json_encode(['success' => false, 'statuscode' => $statuscode, 'message' => $ee->getMessage()]), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
//            }
//            return $response;

            return ['success' => true, 'statuscode' => 200];
        } catch (\Exception $ee) {
            return ['success' => false, 'statuscode' => 400, 'message' => $ee->getMessage()];
        }
    }

    //скачать документ Объекту содержания
    public function getAttachFile($parsedBody)
    {
        $id = json_decode(\Illuminate\Support\Arr::get($parsedBody, 'id'));
        $name = \Illuminate\Support\Arr::get($parsedBody, 'name');
        if (empty($id)) throw new \Exception('Не указан ИД файла');
        if (empty($name)) throw new \Exception('Не указан имя файла');

        $itemModel = new \Roadinfo\Eloquent\modelAttachments();
        $data = $itemModel::
        select('id', 'desc', 'name', 'mime', 'size', 'object_id', 'file')
            ->where('id', '=', $id)
            ->where('name', '=', $name);
        $data = $data->get()->first();
        if (empty($data)) throw new \Exception('Указанный файл не найден');

        return ['success' => true, 'statuscode' => 200, 'data' => $data->attributesToArray()];
    }

    //скачать документ Объекту содержания
    public function deleteAttachFile($parsedBody)
    {
        try {
            $id = json_decode(\Illuminate\Support\Arr::get($parsedBody, 'id'));
            $name = \Illuminate\Support\Arr::get($parsedBody, 'name');
            if (empty($id)) throw new \Exception('Не указан ИД файла');
            if (empty($name)) throw new \Exception('Не указан имя файла');

            $itemModel = new \Roadinfo\Eloquent\modelAttachments();
            $data = $itemModel::where('id', '=', $id)->where('name', '=', $name)->first();
            if (empty($data)) throw new \Exception('Указанный файл не найден');
            Capsule::connection('roadinfo')->beginTransaction();
            $data->delete();

            Capsule::connection('roadinfo')->commit();
            return [
                'success' => true,
                'statuscode' => 200,
                'message' => 'удалено успешно'
            ];
        } catch (\Exception $ee) {
            Capsule::connection('roadinfo')->rollback();
            return [
                'success' => false,
                'statuscode' => 400,
                'message' => $ee->getMessage(),
                'uuid' => null
            ];
        }
    }

    //обновить дерево типов документов
    public function getUpdateAttachmentType()
    {
        try {
            (new modelAttachment_type())::fixTree();
            return ['success' => true, 'statuscode' => 200];
        } catch (\Exception $ee) {
            return ['success' => false, 'statuscode' => 400, 'message' => $ee->getMessage()];
        }
    }

    private function createTree($arrays)
    {
        $result = [];
        foreach ($arrays as $item) {
            if (!empty($item)) {
                if ($item->children->count() == 0) {
                    $result[] = [
                        'expanded' => true,
                        'id' => $item->id,
                        'parent_id' => $item->parent_id,
                        'text' => $item->name,
                        'iconHref' => $item->dict_icons,
                        'extensions' => $item->extensions,
                        'leaf' => ($item->children->count() == 0),
                        'children' => $this->createTree($item->children)
                    ];
                } else {
                    $item = [
                        'expanded' => empty($item->parent_id),
                        'id' => $item->id,
                        'parent_id' => $item->parent_id,
                        'text' => $item->name,
                        'iconHref' => $item->dict_icons,
                        'extensions' => $item->extensions,
                        'leaf' => ($item->children->count() == 0),
                        'children' => $this->createTree($item->children)
                    ];
                    $result[] = $item;
                }
            }
        }
        return $result;
    }

    //получить дерево слоев
    public function getAttachmentTypeTree()
    {
        $data = (new modelAttachment_type())::descendantsAndSelf(-1)->toTree();
        return ['success' => true, 'statuscode' => 200, 'children' => $this->createTree($data)];
    }

}


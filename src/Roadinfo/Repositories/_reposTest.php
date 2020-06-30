<?php

namespace Roadinfo\Repositories;

use Roadinfo\Eloquent\modelAttachment_type;
use Roadinfo\Eloquent\modelTest_layer_file;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Carbon;


class reposTest
{
    public function __construct($user)
    {
        ini_set('max_execution_time', 300);
        date_default_timezone_set('Europe/Moscow');

        $this->user = $user;
        if (empty($this->user) and strtoupper($GLOBALS['settings']['debug']) == 'TRUE') {
            $responseAPI = '{"id": "1", "keyuser":"617","login":"Igor","namefull":"Debug Debug Debug","email":"irepnov@gmail.com", "layers": [40011,40012,40013,40014,40015,   30501,30502,30503,30504], "munobrs": [200,201,203,205,209,211,212,213], "role_id": 1}';
            $this->user = json_decode($responseAPI);
        }
        if (empty($this->user)) throw new \Exception('пользователь не авторизован');
    }

    //получить список документов Объекта содержания
    public function getTestList()
    {
        try {
            $itemModel = new modelTest_layer_file();
            $data = $itemModel::select('id', 'name', 'date', 'file_name', 'deleted_at', 'created_at', 'updated_at')->get()->all();

            $items = [];
            foreach ($data as $item) {
                $items[] = [
                    'id' => $item->id,
                    'name' => $item->name,
                    'file_name' => 'uploads' . DS . 'layer_attachments' . DS . $item->file_name,
                    'date' => $item->date,
                    'deleted_at' => $item->deleted_at,
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at];
            }
            $itemModel = null;
            return ['success' => true, 'statuscode' => 200, 'totalcount' => count($data), 'data' => $items];
        } catch (\Exception $ee) {
            return ['success' => false, 'statuscode' => 400, 'message' => $ee->getMessage()];
        }
    }

    //прикрепить документ к Объекту содержания
    public function addTest($parsedBody)
    {
        try {
            if (empty($parsedBody) or count($parsedBody) == 0) throw new \Exception('отсутствуют данные для добаления');
            $bodys = [];
            if (array_key_exists("id", $parsedBody)) {//если в массиве одна запись
                array_push($bodys, $parsedBody);
            } else { // если в массиве несколько добавляемых записей
                $bodys = $parsedBody;
            }
            $layer_is_file = true;
            foreach ($bodys as $body) {
                unset($body['id']); //удалю id

                if (empty(\Illuminate\Support\Arr::get($body, 'date'))) {
                    $body['date'] = null;
                } else {
                    if (is_numeric(\Illuminate\Support\Arr::get($body, 'date'))) {//если пришел Timestamp
                        $date = new \DateTime();
                        $body['date'] = $date->setTimestamp(\Illuminate\Support\Arr::get($body, 'date'));
                    } else {
                        $body['date'] = new \DateTime(\Illuminate\Support\Arr::get($body, 'date'));
                    }
                }

                $body['created_at'] = new \DateTime();
                $id = [];

                if ($layer_is_file){ //если ведомость содержит файл
                    $file_name = \Illuminate\Support\Arr::get($body, 'file_name'); //прочитаю прежднее имя файла file_name
                    $file_content = \Illuminate\Support\Arr::get($body, 'file_content_value');  //прочитаю новое содержимое файла
                    unset($body['file_content_value'], $body['file_name']); //удалю пришедший file_content
                    if (!empty($file_content)){  //если прикреплен файл
                        if (!empty($file_name)) {
                            unlink(UPLOADDIR . 'layer_attachments' . DS . $file_name); //удалю старый
                            $file_name = null;
                        }
                        $file_name = $this->upload_file(UPLOADDIR . 'layer_attachments' . DS, $file_content); //скопирую новый
                        $body['file_name'] = $file_name; //если пришел файл, то создам имя файла для вставки в таблицу
                    }
                }

                try {
                    Capsule::connection('roadinfo')->beginTransaction();
                    $idd = Capsule::connection('roadinfo')->table('test_layer_file')->insertGetId($body);
                    if ($layer_is_file and !empty($file_name)) {
                        array_push($id, ['id' => $idd, 'file_name' => 'uploads' . DS . 'layer_attachments' . DS . $file_name]); //также верну новое имя файла
                    } else {
                        array_push($id, ['id' => $idd]);
                    }
                    Capsule::connection('roadinfo')->commit();
                } catch (\Exception $ee) {
                    Capsule::connection('roadinfo')->rollback();
                    throw new \Exception($ee->getMessage());
                }
            }
            return ['success' => true, 'statuscode' => 200, 'message' => 'выполнено успешно', 'data' => $id];
        } catch (\Exception $ee) {
            return ['success' => false, 'statuscode' => 400, 'message' => $ee->getMessage()];
        }
    }

    //прикрепить документ к Объекту содержания
    public function updateTest($parsedBody)
    {
        try {
            if (empty($parsedBody) or count($parsedBody) == 0) throw new \Exception('отсутствуют данные для добаления');
            $bodys = [];
            if (array_key_exists("id", $parsedBody)) {//если в массиве одна запись
                array_push($bodys, $parsedBody);
            } else { // если в массиве несколько добавляемых записей
                $bodys = $parsedBody;
            }
            $layer_is_file = true;
            foreach ($bodys as $body) {
                unset($body['id']); //удалю id

                if (empty(\Illuminate\Support\Arr::get($body, 'date'))) {
                    $body['date'] = null;
                } else {
                    if (is_numeric(\Illuminate\Support\Arr::get($body, 'date'))) {//если пришел Timestamp
                        $date = new \DateTime();
                        $body['date'] = $date->setTimestamp(\Illuminate\Support\Arr::get($body, 'date'));
                    } else {
                        $body['date'] = new \DateTime(\Illuminate\Support\Arr::get($body, 'date'));
                    }
                }

                $body['created_at'] = new \DateTime();
                $id = [];

                if ($layer_is_file){ //если ведомость содержит файл
                    $file_name = \Illuminate\Support\Arr::get($body, 'file_name'); //прочитаю прежднее имя файла file_name
                    $file_content = \Illuminate\Support\Arr::get($body, 'file_content');  //прочитаю новое содержимое файла
                    unset($body['file_content'], $body['file_name']); //удалю пришедший file_content
                    if (!empty($file_content)){  //если прикреплен файл
                        if (!empty($file_name)) {
                            unlink(UPLOADDIR . 'layer_attachments' . DS . $file_name); //удалю старый
                            $file_name = null;
                        }
                        $file_name = $this->upload_file(UPLOADDIR . 'layer_attachments' . DS, $file_content); //скопирую новый
                        $body['file_name'] = $file_name; //если пришел файл, то создам имя файла для вставки в таблицу
                    }
                }

                try {
                    Capsule::connection('roadinfo')->beginTransaction();
                    array_push($id, ['id' => Capsule::connection('roadinfo')->table('test_layer_file')->insertGetId($body)]);
                    Capsule::connection('roadinfo')->commit();
                } catch (\Exception $ee) {
                    Capsule::connection('roadinfo')->rollback();
                    throw new \Exception($ee->getMessage());
                }
            }
            return ['success' => true, 'statuscode' => 200, 'message' => 'выполнено успешно', 'data' => $id];
        } catch (\Exception $ee) {
            return ['success' => false, 'statuscode' => 400, 'message' => $ee->getMessage()];
        }
    }

    private function upload_file($directory, $encoded_string)
    {
        if (!empty($encoded_string)){
            $find = strpos($encoded_string,';base64,');  // уберу всякие там варианты 'data:image/jpeg;base64,'
            if ($find !== false){
                $encoded_string = substr($encoded_string,$find + 8);
            }
            $decoded_file = base64_decode($encoded_string); // decode the file
            $mime_type = finfo_buffer(finfo_open(), $decoded_file,FILEINFO_MIME_TYPE); // extract mime type
            $extension = $this->mime2ext($mime_type); // extract extension from mime type
            $file = bin2hex(random_bytes(8)) . '.' . $extension; // rename file as a unique name
            $file_dir = $directory . $file;
            file_put_contents($file_dir, $decoded_file); // save
            return $file;
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

}


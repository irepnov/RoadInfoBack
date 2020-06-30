<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use \Roadinfo\Repositories\reposInfo;

// http://roadinfo_back.test/munobr/
$app->get('/roadinfo/munobr/', function (Request $request, Response $response) {
    try {
        $parsedParams = $request->getQueryParams();
        $repos = (new reposInfo((new \RKA\Session())->get('user_roadinfo', null)))->getMunobr($parsedParams);
        return $response->withStatus($repos['statuscode'])
            ->withHeader('Content-Type', 'application/json')
            ->write(json_encode($repos, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    } catch (\Exception $ee) {
        $statuscode = 500;
        return $response->withStatus($statuscode)
            ->withHeader('Content-Type', 'application/json')
            ->write(json_encode(['success' => false, 'statuscode' => $statuscode, 'message' => $ee->getMessage()]), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
    return $response;
});

// http://roadinfo_back.test/objects/?munobr_id=[0,200,426]
$app->get('/roadinfo/objects/', function (Request $request, Response $response) {
    try {
        $parsedParams = $request->getQueryParams();
        $repos = (new reposInfo((new \RKA\Session())->get('user_roadinfo', null)))->getObjects($parsedParams);
        return $response->withStatus($repos['statuscode'])
            ->withHeader('Content-Type', 'application/json')
            ->write(json_encode($repos, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    } catch (\Exception $ee) {
        $statuscode = 500;
        return $response->withStatus($statuscode)
            ->withHeader('Content-Type', 'application/json')
            ->write(json_encode(['success' => false, 'statuscode' => $statuscode, 'message' => $ee->getMessage()]), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
    return $response;
});

// http://roadinfo_back.test/objects_with_yandex/?objects_id=[0]&munobr_id=[200,426]  //все дороги Краснодара и Сочи
// http://roadinfo_back.test/objects_with_yandex/?objects_id=[0]&munobr_id=[0]  //все дороги края
// http://roadinfo_back.test/objects_with_yandex/?objects_id=[251,252]  //две дороги
$app->get('/roadinfo/objects_with_yandex/', function (Request $request, Response $response) {
    try {
        $parsedParams = $request->getQueryParams();
        $repos = (new reposInfo((new \RKA\Session())->get('user_roadinfo', null)))->getObjectsYandex($parsedParams);
        return $response->withStatus($repos['statuscode'])
            ->withHeader('Content-Type', 'application/json')
            ->write(json_encode($repos, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    } catch (\Exception $ee) {
        $statuscode = 500;
        return $response->withStatus($statuscode)
            ->withHeader('Content-Type', 'application/json')
            ->write(json_encode(['success' => false, 'statuscode' => $statuscode, 'message' => $ee->getMessage()]), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
    return $response;
});

// http://roadinfo_back.test/update_layers_nested/
$app->get('/roadinfo/update_layers_nested/', function (Request $request, Response $response) {
    try {
        $repos = (new reposInfo((new \RKA\Session())->get('user_roadinfo', null)))->getUpdateLayers();
        return $response->withStatus($repos['statuscode'])
            ->withHeader('Content-Type', 'application/json')
            ->write(json_encode($repos, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    } catch (\Exception $ee) {
        $statuscode = 500;
        return $response->withStatus($statuscode)
            ->withHeader('Content-Type', 'application/json')
            ->write(json_encode(['success' => false, 'statuscode' => $statuscode, 'message' => $ee->getMessage()]), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
    return $response;
});

// http://roadinfo_back.test/layers/
$app->get('/roadinfo/layers/', function (Request $request, Response $response) {
    try {
        $parsedParams = $request->getQueryParams();
        $repos = (new reposInfo((new \RKA\Session())->get('user_roadinfo', null)))->getLayersTree($parsedParams);
        return $response->withStatus($repos['statuscode'])
            ->withHeader('Content-Type', 'application/json')
            ->write(json_encode($repos, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    } catch (\Exception $ee) {
        $statuscode = 500;
        return $response->withStatus($statuscode)
            ->withHeader('Content-Type', 'application/json')
            ->write(json_encode(['success' => false, 'statuscode' => $statuscode, 'message' => $ee->getMessage()]), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
    return $response;
});

// http://roadinfo_back.test/layers_objects/
$app->get('/roadinfo/layers_objects/', function (Request $request, Response $response) {
    try {
        $parsedParams = $request->getQueryParams();
        $repos = (new reposInfo((new \RKA\Session())->get('user_roadinfo', null)))->getLayerWithObjects($parsedParams);
        return $response->withStatus($repos['statuscode'])
            ->withHeader('Content-Type', 'application/json')
            ->write(json_encode($repos, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    } catch (\Exception $ee) {
        $statuscode = 500;
        return $response->withStatus($statuscode)
            ->withHeader('Content-Type', 'application/json')
            ->write(json_encode(['success' => false, 'statuscode' => $statuscode, 'message' => $ee->getMessage()]), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
    return $response;
});

//// http://roadinfo_back.test/attach_file/
//$app->get('/roadinfo/getfiles/', function (Request $request, Response $response) {
//    try{
//        $repos = (new reposInfo((new \RKA\Session())->get('user_roadinfo', null)))->getfile();
//        return $response->withStatus($repos['statuscode'])
//            ->withHeader('Content-Type', 'application/json')
//            ->write(json_encode($repos, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
//        /* return $response->withStatus(401)
//             ->withHeader('Content-Type', 'application/json')
//             ->write(json_encode(['success' => false, 'message' => 'df sdfsd sd sf '], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));*/
//    } catch (\Exception $ee) {
//        $statuscode = 500;
//        return $response->withStatus($statuscode)
//            ->withHeader('Content-Type', 'application/json')
//            ->write(json_encode(['success' => false, 'statuscode' => $statuscode, 'message' => $ee->getMessage()]), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
//    }
//    return $response;
//});

/*
// 1. скачивание с сайта справочника
$app->get('/roadinfo/loadSPR/', function (Request $request, Response $response) {
    try{
        $repos = (new reposInfo((new \RKA\Session())->get('user_roadinfo', null)))->tt();
        return $response->withStatus($repos['statuscode'])
            ->withHeader('Content-Type', 'application/json')
            ->write(json_encode($repos, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    } catch (\Exception $ee) {
        $statuscode = 500;
        return $response->withStatus($statuscode)
            ->withHeader('Content-Type', 'application/json')
            ->write(json_encode(['success' => false, 'statuscode' => $statuscode, 'message' => $ee->getMessage()]), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
    return $response;
});

// 2. выгрузка в эксел
$app->get('/roadinfo/exportExcel/', function (Request $request, Response $response) {
    try{
        $repos = (new reposInfo((new \RKA\Session())->get('user_roadinfo', null)))->getUpdateK();
        return $response->withStatus($repos['statuscode'])
            ->withHeader('Content-Type', 'application/json')
            ->write(json_encode($repos, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    } catch (\Exception $ee) {
        $statuscode = 500;
        return $response->withStatus($statuscode)
            ->withHeader('Content-Type', 'application/json')
            ->write(json_encode(['success' => false, 'statuscode' => $statuscode, 'message' => $ee->getMessage()]), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
    return $response;
});
*/


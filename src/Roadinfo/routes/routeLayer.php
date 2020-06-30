<?php

use Roadinfo\Repositories\reposInfo;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use \Roadinfo\Repositories\reposLayer;

// http://roadinfo_back.test/layer/meta/?layer=176&type_layer=1
$app->get('/roadinfo/layer/meta/', function (Request $request, Response $response) {
    try{
        $parsedParams = $request->getQueryParams();
        $repos = (new reposLayer((new \RKA\Session())->get('user_roadinfo', null)))->getMetaData($parsedParams);
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

// http://roadinfo_back.test/layer/meta/update/
$app->post('/roadinfo/layer/meta/update/', function (Request $request, Response $response) {
    try{
        $parsedParams = $request->getQueryParams();
        $parsedBody = $request->getParsedBody();
        $repos = (new reposLayer((new \RKA\Session())->get('user_roadinfo', null)))->updateMetaData($parsedParams, $parsedBody);
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

// http://roadinfo_back.test/layer/info/update/?layer=176
$app->post('/roadinfo/layer/info/update/', function (Request $request, Response $response) {
    try{
        $parsedParams = $request->getQueryParams();
        $parsedBody = $request->getParsedBody();
        $repos = (new reposLayer((new \RKA\Session())->get('user_roadinfo', null)))->updateInfoLayer($parsedParams, $parsedBody);
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

// http://roadinfo_back.test/layer/list/
$app->get('/roadinfo/layer/list/', function (Request $request, Response $response) {
    try{
        $parsedParams = $request->getQueryParams();
        $repos = (new reposLayer((new \RKA\Session())->get('user_roadinfo', null)))->layerList($parsedParams);
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

// http://roadinfo_back.test/layer/read/?layer=176&type_layer=1
$app->get('/roadinfo/layer/read/', function (Request $request, Response $response) {
    try{
        $parsedParams = $request->getQueryParams();
        $repos = (new reposLayer((new \RKA\Session())->get('user_roadinfo', null)))->layerSelect($parsedParams);
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

// http://roadinfo_back.test/layer/update/?layer=176
$app->post('/roadinfo/layer/update/', function (Request $request, Response $response) {
    try{
        $parsedParams = $request->getQueryParams();
        $parsedBody = $request->getParsedBody();
        $repos = (new reposLayer((new \RKA\Session())->get('user_roadinfo', null)))->layerUpdate($parsedParams, $parsedBody);
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

// http://roadinfo_back.test/layer/create/?layer=176
$app->post('/roadinfo/layer/create/', function (Request $request, Response $response) {
    try{
        $parsedParams = $request->getQueryParams();
        $parsedBody = $request->getParsedBody();
        $repos = (new reposLayer((new \RKA\Session())->get('user_roadinfo', null)))->layerCreate($parsedParams, $parsedBody);
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

// http://roadinfo_back.test/layer/destroy/?layer=176
$app->post('/roadinfo/layer/destroy/', function (Request $request, Response $response) {
    try{
        $parsedParams = $request->getQueryParams();
        $parsedBody = $request->getParsedBody();
        $repos = (new reposLayer((new \RKA\Session())->get('user_roadinfo', null)))->layerDestroy($parsedParams, $parsedBody);
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

// http://roadinfo_back.test/layer/geometry/?layer=176
$app->post('/roadinfo/layer/geometry/', function (Request $request, Response $response) {
    try{
        $parsedParams = $request->getQueryParams();
        $parsedBody = $request->getParsedBody();
        $repos = (new reposLayer((new \RKA\Session())->get('user_roadinfo', null)))->layerSelectWithGeometry($parsedParams, $parsedBody);
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

// http://roadinfo_back.test/layer/geometryline/?layer=325
$app->get('/roadinfo/layer/geometryline/', function (Request $request, Response $response) {
    try{
        $parsedParams = $request->getQueryParams();
        $parsedBody = $request->getParsedBody();
        $repos = (new reposLayer((new \RKA\Session())->get('user_roadinfo', null)))->layerSelectWithGeometryLine($parsedParams, $parsedBody);
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

// http://roadinfo_back.test/create_layer_from_xls/
$app->post('/roadinfo/layer/create_layer_from_xls/', function (Request $request, Response $response) {
    try {
        $parsedBody['uploaded_files'] = $request->getUploadedFiles();
        $repos = (new reposLayer((new \RKA\Session())->get('user_roadinfo', null)))->getCreate_layer_from_xls($parsedBody);
       /// $repos = ['success' => false, 'statuscode' => 400, 'message' => 'dfgdf df df gdg dfg'];
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
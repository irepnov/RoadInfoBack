<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use \Roadinfo\Repositories\reposDictionary;

// http://roadinfo_back.test/dict/read/?dict=dict_icons
$app->get('/roadinfo/dict/read/', function (Request $request, Response $response) {
    try{
        $parsedParams = $request->getQueryParams();
        $repos = (new reposDictionary((new \RKA\Session())->get('user_roadinfo', null)))->dictSelect($parsedParams);
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

// http://roadinfo_back.test/dict/list/
$app->get('/roadinfo/dict/list/', function (Request $request, Response $response) {
    try{
        $parsedParams = $request->getQueryParams();
        $repos = (new reposDictionary((new \RKA\Session())->get('user_roadinfo', null)))->getDictList($parsedParams);
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

// http://roadinfo_back.test/dict/create/?dict=dict_icons
$app->post('/roadinfo/dict/create/', function (Request $request, Response $response) {
    try{
        $parsedParams = $request->getQueryParams();
        $parsedBody = $request->getParsedBody();
        $parsedBody['uploaded_files'] = ['items' => $request->getUploadedFiles()];
        $repos = (new reposDictionary((new \RKA\Session())->get('user_roadinfo', null)))->dictCreate($parsedParams, $parsedBody);
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

// http://roadinfo_back.test/dict/update/?dict=dict_icons
$app->post('/roadinfo/dict/update/', function (Request $request, Response $response) {
    try{
        $parsedParams = $request->getQueryParams();
        $parsedBody = $request->getParsedBody();
        $parsedBody['uploaded_files'] = ['items' => $request->getUploadedFiles()];
        $repos = (new reposDictionary((new \RKA\Session())->get('user_roadinfo', null)))->dictUpdate($parsedParams, $parsedBody);
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

// http://roadinfo_back.test/dict/destroy/?layer=dict_icons
$app->post('/roadinfo/dict/destroy/', function (Request $request, Response $response) {
    try{
        $parsedParams = $request->getQueryParams();
        $parsedBody = $request->getParsedBody();
        $repos = (new reposDictionary((new \RKA\Session())->get('user_roadinfo', null)))->dictDestroy($parsedParams, $parsedBody);
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

// http://roadinfo_back.test/dict/meta/?dict=dict_icons
$app->get('/roadinfo/dict/meta/', function (Request $request, Response $response) {
    try{
        $parsedParams = $request->getQueryParams();
        $repos = (new reposDictionary((new \RKA\Session())->get('user_roadinfo', null)))->getMetaData($parsedParams);
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
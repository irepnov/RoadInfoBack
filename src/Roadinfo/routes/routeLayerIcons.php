<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use \Roadinfo\Repositories\reposLayerIcons;

// http://roadinfo_back.test/layericons/list/
$app->get('/roadinfo/layericons/list/', function (Request $request, Response $response) {
    try{
        $parsedParams = $request->getQueryParams();
        $repos = (new reposLayerIcons((new \RKA\Session())->get('user_roadinfo', null)))->getList($parsedParams);
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

// http://roadinfo_back.test/layericons/create/
$app->post('/roadinfo/layericons/create/', function (Request $request, Response $response) {
    try{
        $parsedParams = $request->getQueryParams();
        $parsedBody = $request->getParsedBody();
        $repos = (new reposLayerIcons((new \RKA\Session())->get('user_roadinfo', null)))->Create($parsedParams, $parsedBody);
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

// http://roadinfo_back.test/layericons/update/
$app->post('/roadinfo/layericons/update/', function (Request $request, Response $response) {
    try{
        $parsedParams = $request->getQueryParams();
        $parsedBody = $request->getParsedBody();
        $repos = (new reposLayerIcons((new \RKA\Session())->get('user_roadinfo', null)))->Update($parsedParams, $parsedBody);
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

// http://roadinfo_back.test/layericons/update/
$app->post('/roadinfo/layericons/destroy/', function (Request $request, Response $response) {
    try{
        $parsedParams = $request->getQueryParams();
        $parsedBody = $request->getParsedBody();
        $repos = (new reposLayerIcons((new \RKA\Session())->get('user_roadinfo', null)))->Destroy($parsedParams, $parsedBody);
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
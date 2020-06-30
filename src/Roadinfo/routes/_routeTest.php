<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use \Roadinfo\Repositories\reposTest;

//http://webeasystep.com/blog/view_article/How_to_upload_base64_file_in_PHP
/*
$app->get('/test/read/', function (Request $request, Response $response) {
    try{
        $repos = (new reposTest((new \RKA\Session())->get('user_roadinfo', null)))->getTestList();
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

$app->post('/test/create/', function (Request $request, Response $response) {
    try{
        $parsedBody = $request->getParsedBody();
        $repos = (new reposTest((new \RKA\Session())->get('user_roadinfo', null)))->addTest($parsedBody);
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

$app->post('/test/update/', function (Request $request, Response $response) {
    try{
        $parsedBody = $request->getParsedBody();
        $repos = (new reposTest((new \RKA\Session())->get('user_roadinfo', null)))->updateTest($parsedBody);
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

$app->post('/test/destroy/', function (Request $request, Response $response) {
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
*/
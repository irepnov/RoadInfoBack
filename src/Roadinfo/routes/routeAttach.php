<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use \Roadinfo\Repositories\reposAttach;

// http://roadinfo_back.test/attach/attach_list/?object_id=251
$app->get('/roadinfo/attach/attach_list/', function (Request $request, Response $response) {
    try {
        $parsedParams = $request->getQueryParams();
        $repos = (new reposAttach((new \RKA\Session())->get('user_roadinfo', null)))->getAttachList($parsedParams);
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

// http://roadinfo_back.test/attach/attach_file/
$app->post('/roadinfo/attach/attach_file/', function (Request $request, Response $response) {
    try{
        $body = $request->getParsedBody();
        $body['uploaded_files'] = ['items' => $request->getUploadedFiles()];
        $repos = (new reposAttach((new \RKA\Session())->get('user_roadinfo', null)))->addAttachFile($body);
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

//// http://roadinfo_back.test/attach/attach_file/
//$app->get('/roadinfo/attach/attach_file/', function (Request $request, Response $response) {
//    try{
//        $parsedParams = $request->getQueryParams();
//        $repos = (new reposAttach((new \RKA\Session())->get('user_roadinfo', null)))->getAttachFile($parsedParams);
//
//        $stream = fopen('php://memory','r+');
//        fwrite($stream, $repos['data']['file']);
//        rewind($stream);
//
//        $streamslim = new \Slim\Http\Stream($stream);
//
//        return $response->withHeader('Content-Type', 'application/force-download')
//            ->withHeader('Content-Type', 'application/octet-stream')
//            ->withHeader('Content-Type', 'application/download')
//            ->withHeader('Content-Description', 'File Transfer')
//            ->withHeader('Content-Transfer-Encoding', 'binary')
//            ->withHeader('Content-Disposition', 'attachment; filename="' . $repos['data']['name'] . '"')
//            ->withHeader('Expires', '0')
//            ->withHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
//            ->withHeader('Pragma', 'public')
//            ->withBody($streamslim); // all stream contents will be sent to the response
//
//    } catch (\Exception $ee) {
//        $statuscode = 400;
//        return $response->withStatus($statuscode)
//            ->withHeader('Content-Type', 'application/json')
//            ->write(json_encode(['success' => false, 'statuscode' => $statuscode, 'message' => $ee->getMessage()]), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
//    }
//    return $response;
//});

// http://roadinfo_back.test/attach/attach_file/
$app->delete('/roadinfo/attach/attach_file/', function (Request $request, Response $response) {
    try{
        $parsedParams = $request->getQueryParams();
        $repos = (new reposAttach((new \RKA\Session())->get('user_roadinfo', null)))->deleteAttachFile($parsedParams);
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

// http://roadinfo_back.test/attach/update_attachment_type_nested/
$app->get('/roadinfo/attach/update_attachment_type_nested/', function (Request $request, Response $response) {
    try {
        $repos = (new reposAttach((new \RKA\Session())->get('user_roadinfo', null)))->getUpdateAttachmentType();
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

// http://roadinfo_back.test/attach/attachment_type/
$app->get('/roadinfo/attach/attachment_type/', function (Request $request, Response $response) {
    try {
        $parsedParams = $request->getQueryParams();
        $repos = (new reposAttach((new \RKA\Session())->get('user_roadinfo', null)))->getAttachmentTypeTree();
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
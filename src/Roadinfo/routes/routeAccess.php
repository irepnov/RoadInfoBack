<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use \Roadinfo\Repositories\reposAccess;

use \Roadinfo\User;

$app->get('/roadinfo/', function ($request, $response, $args) {
    return $this->renderer->render($response, 'roadinfo/index.html', ['phpEnum' => 'production']);
})->setName('route.roadinfo');


$app->get('/roadinfo/logout/', function ($request, $response, $args) {
    \RKA\Session::destroy();
   // setcookie('user_roadinfo', '', time() - 4200);
    $uri = 'http://inet.amstrad-road.ru/login';
    return $response->withStatus(200)
        ->withHeader('Content-Type', 'application/json')
        ->write(json_encode(['uri'=>$uri], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
});

//http://inet.roadinfo.amstrad-road.ru/autologin/?hash=143b8528dec8454e5a5145254747f066
//http://inet.roadinfo.amstrad-road.ru/autologin/?hash=123456qwe
//http://81.95.223.232:8119/autologin/?hash=123456qwe
$app->get('/roadinfo/autologin/', function ($request, $response, $args) {
    if (strtoupper($GLOBALS['settings']['debug']) == 'FALSE') { //при Продакшене, авторизуемся через Амстрад
        $hash = $request->getQueryParam('hash');

        if ($hash == '123456qwe'){//заглушка для сотрудника ЦДИ Жуков   http://81.95.223.232:8119/autologin/?hash=123456qwe
            $responseAPI = '{"success":1,"user":{"keyuser":"664","login":"zukov","namefull":"Жуков Сергей Александрович","email":"zhukov@cdikuban.com"}}';
        }else{
            $urlAPI = 'http://inet.amstrad-road.ru/default/validate/auth';
            $data = array(
                'format' => 'json',
                'validate' => $hash
            );
            //Отправить запрос  http://inet.amstrad-road.ru/default/validate/auth?format=json&validate=ad1ef50c6bf81fadf925728be5f025d4
            // получить  {"success":1,"user":{"keyuser":"617","login":"Igor","name":"Репнов Игорь Борисович","email":"irepnov@gmail.com"}}
            $data = http_build_query($data, '', '&');
            $responseAPI = file_get_contents($urlAPI. '?'. $data, false);
        }

    }else {//делаем подставного пользователя
        //$responseAPI = '{"success":1,"user":{"keyuser":"617","login":"Igor","namefull":"Debug Debug Debug","email":"irepnov@gmail.com"}}';
        $responseAPI = '{"success":1,"user":{"keyuser":"664","login":"zukov","namefull":"Жуков Сергей Александрович","email":"zhukov@cdikuban.com"}}';
    }

    if (!empty($responseAPI)){ //ошибка, авторизация не прошла
        $responseAPI = json_decode($responseAPI);
        if ($responseAPI->success != 1 or empty($responseAPI->user)){
            \RKA\Session::destroy();
           // setcookie('user_roadinfo', '', time() - 4200);
            return $response->withStatus(400)
                ->withHeader('Content-Type', 'application/json')
                ->write(json_encode(['error' => 'user not autorization 1', 'respAPI' => $responseAPI, 'hash' => $hash], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        }
    }else{
        \RKA\Session::destroy();
        //setcookie('user_roadinfo', '', time() - 4200);
        return $response->withStatus(400)
            ->withHeader('Content-Type', 'application/json')
            ->write(json_encode(['error' => 'user not autorization 2', 'respAPI' => $responseAPI, 'hash' => $hash], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }

    $user = $responseAPI->user;
    $session = new \RKA\Session();
    $session->set('user_roadinfo', '');

    if (!empty($user)) {
        $userInfo = (new reposAccess($user))->getAccess(null);
        $session->set('user_roadinfo', $userInfo);
       // setcookie('user_roadinfo', json_encode($user),0); //создам кукие сроком действия до окончания сессии
        $url = $this->router->pathFor('route.roadinfo');//редирект на фронтэнд
        return $response->withStatus(302)->withHeader('Location', $url);
    }
});

/*
$app->get('/roadinfo/clientlogin', function ($request, $response, $args) {
    try {
        $userInfo = \Roadinfo\Repositories\reposAuth::getUserAuth();
        $session = new \RKA\Session();
        $session->set('user_roadinfo', '');
        if (!empty($userInfo)) {
            $userInfo = (new reposAccess($userInfo))->getAccess(null);
            $session->set('user_roadinfo', $userInfo);
            $url = $this->router->pathFor('route.roadinfo');//редирект на фронтэнд
            return $response->withStatus(302)->withHeader('Location', $url);
        }
    } catch (\Exception $ee) {
        return $response->withStatus(400)->withHeader("Content-Type", "application/json")->write(json_encode(["error" => $ee->getMessage()],JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }
});*/

$app->get('/roadinfo/access/activeuser/', function ($request, $response, $args) {
    try{
        $repos = (new reposAccess((new \RKA\Session())->get('user_roadinfo', null)))->getActiveUserInfo();
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

$app->get('/roadinfo/access/munobrs/', function ($request, $response, $args) {
    try{
        $repos = (new reposAccess((new \RKA\Session())->get('user_roadinfo', null)))->getMunobrAl();
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

$app->get('/roadinfo/access/authusers/', function ($request, $response, $args) {
    try{
        $repos = (new reposAccess((new \RKA\Session())->get('user_roadinfo', null)))->getAuthUsers();
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

$app->get('/roadinfo/access/roles/', function ($request, $response, $args) {
    try{
        $repos = (new reposAccess((new \RKA\Session())->get('user_roadinfo', null)))->getRoles();
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

$app->get('/roadinfo/access/users/', function ($request, $response, $args) {
    try{
        $repos = (new reposAccess((new \RKA\Session())->get('user_roadinfo', null)))->getUsersList();
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

$app->post('/roadinfo/access/users/create/', function ($request, $response, $args) {
    try{
        $parsedParams = $request->getQueryParams();
        $parsedBody = $request->getParsedBody();
        $repos = (new reposAccess((new \RKA\Session())->get('user_roadinfo', null)))->createUsers($parsedParams, $parsedBody);
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

$app->post('/roadinfo/access/users/update/', function ($request, $response, $args) {
    try{
        $parsedParams = $request->getQueryParams();
        $parsedBody = $request->getParsedBody();
        $repos = (new reposAccess((new \RKA\Session())->get('user_roadinfo', null)))->updateUsers($parsedParams, $parsedBody);
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

$app->post('/roadinfo/access/users/destroy/', function ($request, $response, $args) {
    try{
        $parsedParams = $request->getQueryParams();
        $parsedBody = $request->getParsedBody();
        $repos = (new reposAccess((new \RKA\Session())->get('user_roadinfo', null)))->destroyUsers($parsedParams, $parsedBody);
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

$app->post('/roadinfo/access/users/access/', function ($request, $response, $args) {
    try{
        $parsedParams = $request->getQueryParams();
        $parsedBody = $request->getParsedBody();
        $repos = (new reposAccess((new \RKA\Session())->get('user_roadinfo', null)))->accessUsers($parsedParams, $parsedBody);
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
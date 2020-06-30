<?php
namespace src\appSlim;

use Illuminate\Database\Capsule\Manager as Capsule;

class appSlim
{
    protected $appSlim;

    public function __construct($ROOT, $DS)
    {
        $configuration = [
            'settings' => [
                'displayErrorDetails' => true
            ],
        ];
        $cont = new \Slim\Container($configuration);

        $cont['renderer'] = function ($cont) {
            return new \Slim\Views\PhpRenderer(PUBLICDIR);
        };

        $this->appSlim = new \Slim\App($cont);

        $capsule = new Capsule;

        $connect = include $ROOT . "config" . $DS . 'database.config.php';

        if (!empty($connect))
        {
            $mysql_roadinfo = $connect['mysql_roadinfo'];
            $mysql_companies = $connect['mysql_companies'];
            $mysql_general = $connect['mysql_general'];
            $mysql_distances = $connect['mysql_distances'];
            $mysql_users = $connect['mysql_users'];

            if (!empty($mysql_roadinfo)){
                $capsule->addConnection($mysql_roadinfo,'roadinfo');
            }
            if (!empty($mysql_companies)){
                $capsule->addConnection($mysql_companies,'companies');
            }
            if (!empty($mysql_general)){
                $capsule->addConnection($mysql_general,'general');
            }
            if (!empty($mysql_distances)){
                $capsule->addConnection($mysql_distances,'distances');
            }
            if (!empty($mysql_users)){
                $capsule->addConnection($mysql_users,'users');
            }
        }

        $capsule->setAsGlobal();
        $capsule->bootEloquent();
        $this->appSlim->db = $capsule;
    }

    public function getAppSlim()
    {
        return $this->appSlim;
    }
}

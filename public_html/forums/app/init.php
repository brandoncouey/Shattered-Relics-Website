<?php
    session_start();
    use Illuminate\Database\Capsule\Manager as DB;
    
    require_once 'constants.php';
    require_once 'core/Controller.php';
    require_once 'Functions.php';

    include 'vendor/autoload.php';

    $dirs = [
        "app/core/",
        "app/core/acl/",
        "app/controllers/",
        "app/models/",
        "app/models/forums/",
        "app/plugins/",
    ];

    foreach($dirs as $dir) {
        foreach (glob($dir.'*.php') as $filename) {
            include_once(''.$filename.'');
        }
    }

    $database = new DB;

    $database->addConnection([
        "driver"   => "mysql",
        "host"     => MYSQL_HOST,
        "database" => MYSQL_DATABASE,
        "username" => MYSQL_USERNAME,
        "password" => MYSQL_PASSWORD,
    ]);

    $database->setAsGlobal();
    $database->bootEloquent();
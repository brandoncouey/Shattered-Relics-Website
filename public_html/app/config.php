<?php
define("web_root", "/");

define("title", "Shattered Relics");

define('MYSQL_HOST', 'grizzlyent.cpde7dtfjvvy.us-west-2.rds.amazonaws.com'); # usually localhost
define('MYSQL_DATABASE', 'grizzly');
define('MYSQL_USERNAME', 'admin');
define('MYSQL_PASSWORD', 'toodlesyacunt!');


const discord = [
    'client_id'     => '756394709722857542',
    'client_secret' => 'UGrHA6reY2F4NVcePN_5ZpRyDkG7QjR_',
    'redirect_uri'  => 'http://shatteredrelics.com/login/auth',
    'connect_uri'   => 'http://shatteredrelics.com/account/apps/link/discord',
    'auth_url'      => 'https://discordapp.com/api/oauth2/authorize',
    'token_url'     => 'https://discordapp.com/api/oauth2/token',
    'url_base'      => 'https://discordapp.com/api/users/@me'
];
<?php

define('MYSQL_HOST', 'grizzlyent.cpde7dtfjvvy.us-west-2.rds.amazonaws.com'); # usually localhost
define('MYSQL_DATABASE', 'grizzly');
define('MYSQL_USERNAME', 'admin');
define('MYSQL_PASSWORD', '!003786dc');

define("dark_mode", 1);

define("api_url", "http://localhost:85");

// the name of your website. used around the website
define('site_title', 'Community | Shattered Relics');

// the folder where this script is located. if in root, should just be / 
define('web_root', '/forums/');

define("csrf_key", "skdfmslfk345");

const discord = [
    'client_id'     => '699451380393443328',
    'client_secret' => 'M3edrb6V2G3PmLhXFdtBwVmexgxb3DRm',
    'redirect_uri'  => 'https://shatteredrelics.com/discord/auth',
    'auth_url'      => 'https://discordapp.com/api/oauth2/authorize',
    'token_url'     => 'https://discordapp.com/api/oauth2/token',
    'url_base'      => 'https://discordapp.com/api/users/@me'
];

define("imgur_key", "e41812326e69a80");
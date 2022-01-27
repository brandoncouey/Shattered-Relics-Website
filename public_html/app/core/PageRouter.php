<?php

use Router\Router;

class PageRouter extends Router {

    private static $instance;

    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new PageRouter(web_root);
        }
        return self::$instance;
    }

    private $controller;
    private $method;
    private $params;

    public $route_paths = [];

    public function initRoutes() {

        $this->all('', function() {
            return $this->setRoute('index', 'index');
        });

        /**
         * Login
         */
        $this->all('login', function() {
            return $this->setRoute('login', 'index');
        });

        $this->all('login/auth', function() {
            return $this->setRoute('login', 'auth');
        });
        
        $this->all('login/authenticate', function() {
            return $this->setRoute('login', 'authenticate');
        });

        $this->all('register', function() {
            return $this->setRoute('register', 'index');
        });

        $this->all('logout', function() {
            return $this->setRoute('index', 'logout');
        });

        $this->all('([0-9]+)-([A-Za-z0-9\-]+)', function($catId, $title) {
            return $this->setRoute('index', 'index', ['catId' => $catId]);
        });

        $this->all('subscribe', function() {
            return $this->setRoute('subscribe', 'index');
        });

        /**
         * Features
         */
        $this->all("game", function() {
            return $this->setRoute("game", "index");
        });
        /*$this->all("game/combat", function() {
            return $this->setRoute("game", "combat");
        });
        $this->all("game/professions", function() {
            return $this->setRoute("game", "professions");
        });
        $this->all("game/exploration", function() {
            return $this->setRoute("game", "exploration");
        });
        $this->all("game/quest", function() {
            return $this->setRoute("game", "quest");
        });
        $this->all("game/pvp", function() {
            return $this->setRoute("game", "pvp");
        });
        $this->all("game/pve", function() {
            return $this->setRoute("game", "pve");
        });*/


        /**
         * Pages
         */

        $this->all('faq', function() {
            return $this->setRoute('pages', 'faq');
        });

        $this->all('team', function() {
            return $this->setRoute('pages', 'team');
        });

        $this->all('terms', function() {
            return $this->setRoute('pages', 'terms');
        });
        $this->all('privacy', function() {
            return $this->setRoute('pages', 'privacy');
        });

        /*$this->all('funding', function() {
            return $this->setRoute('pages', 'funding');
        });*/

        /*$this->all('contact', function() {
            return $this->setRoute('pages', 'contact');
        });
        $this->all('terms', function() {
            return $this->setRoute('pages', 'terms');
        });
        $this->all('privacy', function() {
            return $this->setRoute('pages', 'privacy');
        });
        */
    }

    public function setRoute($controller, $method, $params = []) {
        $this->controller = $controller;
        $this->method = $method;
        $this->params = $params;

        return [$controller, $method, $params];
    }

    public function getController($formatted = false) {
        return $formatted ? ucfirst($this->controller).'Controller' : $this->controller;
    }

    public function getViewPath() {
        return $this->getController().'/'.$this->getMethod();
    }

    public function getMethod() {
        return $this->method;
    }

    public function getParams() {
        return $this->params;
    }

    public function isSecure() {
        return
          (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || $_SERVER['SERVER_PORT'] == 443;
    }

    public function getUrl() {
        $baseUrl =  'http'.($this->isSecure() ? 's' : '').'://' . $_SERVER['HTTP_HOST'];
        return $baseUrl.web_root;
    }
}
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
         * Categories
         */
       $this->all('([0-9]+)-([A-Za-z0-9\-]+)', function($catId, $catTitle) {
            return $this->setRoute('index', 'index', ['cid' => $catId]);
        });

        $this->all('([0-9]+)-([A-Za-z0-9\-]+)/([0-9]+)', function($catId, $catTitle, $page) {
            return $this->setRoute('index', 'index', ['cid' => $catId, 'page' => $page]);
        });

        $this->all('([0-9]+)', function($page) {
            return $this->setRoute('index', 'index', ['cid' => -1, 'page' => $page]);
        });

        /**
         * Topics
         */
        $this->all('topic/view/([0-9]+)-([A-Za-z0-9\-]+)', function($topicId, $title) {
            return $this->setRoute('topic', 'view', ['topicId' => $topicId, 'page' => 1]);
        });

        $this->all('topic/view/([0-9]+)-([A-Za-z0-9\-]+)/([0-9]+)', function($topicId, $title, $page) {
            return $this->setRoute('topic', 'view', ['topicId' => $topicId, 'page' => $page]);
        });

        $this->all('topic/edit/([0-9]+)', function($topicId) {
            return $this->setRoute('topic', 'edit', ['topicId' => $topicId]);
        });

        $this->all('topic/delete/([0-9]+)', function($topicId) {
            return $this->setRoute('topic', 'deletetopic', ['topicId' => $topicId]);
        });

        $this->post('topic/new', function() {
            return $this->setRoute('topic', 'new');
        });

        $this->post('topic/reply', function() {
            return $this->setRoute('topic', 'reply');
        });

        /**
         * Login
         */
        $this->all('login', function() {
            return $this->setRoute('login', 'index');
        });

        $this->post('login/auth', function() {
            return $this->setRoute('login', 'auth');
        });

        $this->get('logout', function() {
            return $this->setRoute('index', 'logout');
        });

        $this->post('discord', function() {
            return $this->setRoute('login', 'discord');
        });

        $this->get('discord/auth', function() {
            return $this->setRoute('login', 'dauth');
        });

        /**
         * Profile
         */
        $this->all('profile', function() {
            return $this->setRoute('profile', 'index');
        });

        /**
         * Admin Pages
         */
        $this->all('admin', function() {
            return $this->setRoute('admin', 'index');
        });

        $this->all('admin/users', function() {
            return $this->setRoute('users', 'index');
        });

        $this->all('admin/categories', function() {
            return $this->setRoute('categories', 'index');
        });

        $this->all('admin/categories/([0-9]+)', function($category) {
            return $this->setRoute('categories', 'index', ['category' => $category]);
        });

        $this->all('admin/categories/add', function() {
            return $this->setRoute('categories', 'add', ['parent' => -1]);
        });

        $this->all('admin/categories/add/([0-9]+)', function($parent) {
            return $this->setRoute('categories', 'add', ['parent' => $parent]);
        });

        $this->all('admin/categories/edit/([0-9]+)', function($catId) {
            return $this->setRoute('categories', 'edit', ['catId' => $catId]);
        });

        $this->all('admin/categories/delete/([0-9]+)', function($catId) {
            return $this->setRoute('categories', 'delete', ['catId' => $catId]);
        });
        
        $this->all('admin/users/([0-9]+)', function($page) {
            return $this->setRoute('users', 'index', ['page' => $page ]);
        });
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
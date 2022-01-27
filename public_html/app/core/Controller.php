<?php
use Fox\Request;
use Fox\Cookies;
use Fox\CSRF;
use Fox\Session;

class Controller {

    protected $view;
    protected $viewVars = array();
    protected $actionName;
    private $disableView;
    private $json_output;
    private $purifier;
    protected $router;
	protected $request;
    protected $cookies;
    protected $csrf;
    public $user;
    public $userRole;

    /**
     * Executes before the controller is executed. Can be used
     * for permissions
     */
    public function beforeExecute() {
        $this->request  = Request::getInstance();
        $this->cookies  = Cookies::getInstance();
        $userRole       = "Guest";
        $access_key     = $this->cookies->get("user_key");

        if ($access_key) {
            if ($this->request->hasQuery("logout")) {
                $session  = UsersSessions::where('access_key', $access_key)->first();
    
                if ($session) {
                    $session->delete();
                    $this->cookies->delete("user_key");
                    $this->request->redirect("");
                    exit;
                }
            }

            $address = $this->request->getAddress();
            $session = UsersSessions::where("access_key", $access_key)->first();

            if (!$session || $address != $session->ip_address) {
                $this->destroySession($session);
                exit;
            }

            $user = Users::where("id", $session['user_id'])->first();

            if (!$user) {
                $this->destroySession($session);
                exit;
            }

            $this->user = $user;
            $userRole = $user->rank;

            $session->last_visit = time();
            $session->save();

            $this->set("user", $user);
        }

        $footer_news = $this->getLatestNews();
        $controller  = $this->router->getController();
        $action      = $this->router->getMethod();

        if ($access_key && ($controller == "login" || $controller == "register")) {
            $this->request->redirect("");
            exit;
        }
        
        $this->set("controller", $controller);
        $this->set("action", $action);
        $this->set("footer_news", $footer_news);
        return true;
    }

    /**
     * Gets latest news from forum for footer
     */
    public function getLatestNews() {
        return Topics::select(['id', 'title', 'started'])
        ->where("parent", 1)
        ->orderBy("started", "DESC")
        ->limit(3)
        ->get();
    }

    /**
     * Displays the necessary template using Twig
     */
	public function show() {
	    if ($this->disableView) {
	        return;
        }

	    $loader = new Template('app/views');
        $loader->setCacheEnabled(false);

        if (!file_exists($loader->path.'/'.$this->view.".twig")) {
            $this->view = "errors/missing";
        }

	    try {
            $template = $loader->load($this->view);
            echo $template->render($this->viewVars);
        } catch (Exception $e) {
            
        }
	}

    /**
     * Gets the name of the action
     * @return mixed
     */
	public function getActionName() {
		return $this->actionName;
	}

    /**
     * Sets the action to be used.
     * @param $name
     */
	public function setActionName($name) {
		$this->actionName = $name;
	}

    /**
     * Sets a specific variable for the view with a value
     * @param $variableName
     * @param $value
     */
	public function set($variableName, $value) {
		$this->viewVars[$variableName] = $value;
	}

    /**
     * Sets variables to be used in the view
     * @param $params
     */
	public function setVars($params) {
		$this->viewVars = $params;
	}

    /**
     * Sets which view to use.
     * @param $view
     */
	public function setView($view) {
		$this->view = $view;
    }
    
    /**
     * @return string the view path
     */
    public function getView() {
        return $this->view;
    }

    /**
     * @return PageRouter
     */
    public function getRouter() {
        return $this->router;
    }

    /**
     * @param $router PageRouter
     */
	public function setRouter(PageRouter $router) {
	    $this->router = $router;
    }

    /**
     * Disables the view from rendering
     * @var bool $is_json
     */
    public function disableView($is_json = false) {
        $this->disableView = true;
        $this->json_output = $is_json;
    }

    /**
     * @return bool true if output should be json format
     */
    public function isJson() {
        return $this->json_output;
    }

    /**
     * @return Cookies
     */
    public function getCookies() {
        return $this->cookies;
    }

    /**
     * @return Request
     */
    public function getRequest() {
        return $this->request;
    }

    /**
     * @return EasyCSRF\EasyCSRF
     */
    public function getCsrf() {
        return $this->csrf;
    }

    /**
     * gets the contents of a template file
     * @return string 
     */
    public function getViewContents($view, $vars = []) {
        $loader = new Template('app/views');
        $loader->setCacheEnabled(false);

        try {
            $template = $loader->load($view);
            return $template->render($vars);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * gets the HTMLPurifier to sanitize html input in forms
     * @return HTMLPurifier
     */
    public function getPurifier() {
        $allowed_html = [
            'div[class]',
            'span[style]',
            'a[href|class|target]',
            'img[src|class]',
            'h1','h2','h3',
            'p[class]',
            'strong','em',
            'ul','u','ol','li',
            'table[class]','tr','td','th','thead','tbody'
        ];

        if (!$this->purifier) {
            $config = HTMLPurifier_Config::createDefault();
            $config->set("Core.Encoding", 'utf-8');
            $config->set('AutoFormat.RemoveEmpty', true);
            $config->set("HTML.Allowed", implode(',', $allowed_html));
            $config->set('HTML.AllowedAttributes', 'src, height, width, alt, href, class, style');
            $this->purifier = new HTMLPurifier($config);
        }

        return $this->purifier;
    }

    /**
     * Sanitizes html input
     * @return string
     */
    public function purify($text) {
        $text  = $this->getPurifier()->purify($text);
        $text  = preg_replace( "/\r|\n/", "", $text);
        $text  = preg_replace('/[^\00-\255]+/u', '', $text);
        return $text;
    }
}

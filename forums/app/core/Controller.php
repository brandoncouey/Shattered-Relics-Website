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

    /** @var HTMLPurifier purifier */
    private $purifier;

    /** @var PageRouter */
    protected $router;

	/** @var Request $request */
	protected $request;

	/** @var Cookies $cookies */
    protected $cookies;
    
    /** @var Session $session */
    protected $session;

    /** @var EasyCSRF\EasyCSRF $csrf */
    protected $csrf;

    /** @var Users $user */
    public $user;

    public $userRole;

    public function beforeExecute() {
        $this->request  = Request::getInstance();
        $this->cookies  = Cookies::getInstance();
        $this->csrf     = new EasyCSRF\EasyCSRF(new EasyCSRF\NativeSessionProvider());
        $this->session  = Session::getInstance();

        $controller = $this->router->getController();
        $action     = $this->router->getMethod();
        $userRank   = "Guest";
        $user_key   = $this->cookies->get("user_key");

        if ($user_key) {
            $address = $this->request->getAddress();
            $session = UsersSessions::where('access_key', $user_key)->first();

            if (!$session || $address != $session->ip_address) {
                $this->destroySession($session);
                exit;
            }

            $user = Users::where("id", $session['user_id'])->first();

            if (!$user) {
                $this->destroySession($session);
                exit;
            }

            $session->last_visit = time();
            $session->save();

            $userRank   = $user->rank;
            $this->user = $user;
            
            $this->set("user", $user);
        }

        $access = Security::canAccess($controller, $action, $userRank);

        if ($this->user) {
            if ($user->rank == "banned") {
                return false;
            }
            $this->userRole = Security::getRole($user->rank);
        }
        
        if (!$access || strtolower($userRank) == "banned") {
            $this->setView("errors/show401");
            return false;
        }

        if ($this->cookies->has("dark-mode")) {
            $this->set("dark_mode", true);
        }
        
        $this->set("controller", $controller);
        $this->set("action", $action);
        return true;
    }

    public function destroySession($session) {
        $this->cookies->delete("user_key");
        $this->request->redirect("");
        if ($session)
            $session->delete();
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
    
    public function getView() {
        return $this->view;
    }

    /**
     * @param $router PageRouter
     */
	public function setRouter(PageRouter $router) {
	    $this->router = $router;
    }

    /**
     * Filters a string.
     * @param $str
     * @return mixed
     */
    public function filter($str) {
        return filter_var($str, FILTER_SANITIZE_STRING,
            FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
    }

    /**
     * Filters an integer.
     * @param $int
     * @return mixed
     */
    public function filterInt($int) {
        return filter_var($int, FILTER_SANITIZE_NUMBER_INT,
            FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
    }

    public static function debug($array) {
        echo "<pre>".htmlspecialchars(json_encode($array, JSON_PRETTY_PRINT))."</pre>";
    }

    public static function printStr($str) {
        echo "<pre>".$str."</pre>";
    }

    public function disableView($is_json = false) {
        $this->disableView = true;
        $this->json_output = $is_json;
    }

    public function isJson() {
        return $this->json_output;
    }

    public function getCookies() {
        return $this->cookies;
    }

    public function getRequest() {
        return $this->request;
    }

    public function getRouter() {
        return $this->router;
    }

    public function getCsrf() {
        return $this->csrf;
    }

    public function redirect($location, $internal = true) {
        $this->request->redirect($location, $internal);
    }

    public function delayedRedirect($url, $time, $internal = false) {
        $this->request->delayedRedirect($url, $time, $internal);
    }

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

    public function purify($text) {
        $text  = $this->getPurifier()->purify($text);
        $text  = preg_replace( "/\r|\n/", "", $text);
        $text  = preg_replace('/[^\00-\255]+/u', '', $text);
        return $text;
    }
}

<?php
use GuzzleHttp\Client;

class LoginController extends Controller {

    public function index() {
        if (!$this->request->isPost()) {
            return [
                'success' => false,
                'message' => "Page is available via post only.",
                'token'   => $this->getCsrf()->getToken()
            ];
        }

        if (time() - $this->session->get("last_request") <= 1) {
            return [
                'success' => false,
                'message' => "You're requesting this too quickly."
            ];
        }

        $this->session->update("last_request", time());

        $email    = $this->request->getPost("email", "email");
        $pass     = $this->request->getPost("password");
        $remember = $this->request->getPost("remember");

        $user = Users::where("email", $email)->first();

        if (!$user) {
            return [
                'success' => false,
                'message' => "Invalid email or password."
            ];
        }

        if (!password_verify($pass, $user->password)) {
            return [
                'success' => false,
                'message' => "Invalid email or password."
            ];
        }

        if ($user->mfa_secret) {
            $_SESSION['authenticate'] = $user->id;
            $_SESSION['remember'] = $remember ?  true : false;

            return [
                'success'  => false,
                'need2fa'  => true,
                'formdata' => $this->getViewContents("login/mfa")
            ];
        }

        $time  = $remember ? (86400 * 30) : 86400;
        $token = Functions::generateString(25);
        $saved = $this->createSession($user, $token, $remember);

        $user->last_ip    = $this->request->getAddress();
        $user->last_login = time();
        $saved = $user->save();
        
        if ($saved) {
            $this->cookies->set("user_key", $token, $time);
            return [
                'success' => true,
                'message' => 'You have logged in!'
            ];
        }

        return [
            'success' => true,
            'message' => "Error Saving session."
        ];
    }

    public function auth() {
        if (time() - $this->session->get("last_request") <= 1) {
            return [
                'success' => false,
                'message' => "You're requesting this too quickly."
            ];
        }

        $this->session->update("last_request", time());

        if (!$this->request->isPost() || !$this->request->hasPost("code")) {
            return [
                'success' => false,
                'message' => "Page is available via post only."
            ];
        }

        if (!$this->session->has("authenticate")) {
            return [
                'success' => false,
                'message' => "Session variable not present"
            ];
        }

        $user_id  = $this->filter($_SESSION['authenticate']);
        $remember = $this->filterInt($_SESSION['remember']);
        $user     = Users::getById($user_id);

        if (!$user) {
            return [
                'success' => false,
                'message' => "Invalid user."
            ];
        }

        $code     = $this->request->getPost("code", "int");
        $secret   = $user['mfa_secret'];
        $tfa      = new RobThree\Auth\TwoFactorAuth(site_title);
        $verified = $tfa->verifyCode($secret, $code);

        if (!$verified) {
            return [
                'success' => false,
                'message' => 'Invalid code!'
            ];
        }
        
        $time  = $remember ? (86400 * 30) : 86400;
        $token = Functions::generateString(25);
        $saved = $this->createSession($user, $token, $remember);

        if ($saved) {
            $this->cookies->set("user_key", $token, $time);

            Users::update($user['id'], [
                'last_ip'    => $this->request->getAddress(),
                'last_login' => time()
            ]);

            return [
                'success' => true,
                'message' => 'Authentication successful!'
            ];
        }
        
        return [
            'success' => false,
            'message' => 'failed to save session.'
        ];
    }

    public function discord() {
        $params = array(
            'client_id'     => discord['client_id'],
            'redirect_uri'  => discord['redirect_uri'],
            'response_type' => 'code',
            'scope'         => 'identify'
        );

        return [
            'success' => true,
            'message' => 'https://discordapp.com/api/oauth2/authorize?'.http_build_query($params)
        ];
    }

    public function dauth() {
        if (!$this->request->hasQuery("code")) {
            return $this->redirect("");
        }

        $response = (new DiscordBot())
            ->setEndpoint("oauth2/token")
            ->setType("post")
            ->setContentType("x-www-form-urlencoded")
            ->setData([
                "grant_type"    => "authorization_code",
                'client_id'     => discord['client_id'],
                'client_secret' => discord['client_secret'],
                'redirect_uri'  => discord['redirect_uri'],
                'code'          => $this->request->getQuery("code")
            ])
            ->submit();


        if (isset($response->access_token)) {
            $expires = $response->expires_in;
            $token   = $response->access_token;

            $userInfo = (new DiscordBot())
                ->setEndpoint("users/@me")
                ->setAccessToken($token)
                ->submit();

            if (!$userInfo || isset($userInfo->code)) {
                $this->logout();
                $this->response->redirect("");
                return false;
            }

            $discord_id = $userInfo->id;
            $user = Users::where('discord_id', $discord_id)->first();

            if (!$user) {
                $this->redirect("");
                return;
            }

            $token = Functions::generateString(25);
            $saved = $this->createSession($user, $token, false);

            if ($saved) {
                $this->cookies->set("user_key", $token);
                $this->redirect("");

                Users::update($user->id, [
                    'last_ip'    => $this->request->getAddress(),
                    'last_login' => time()
                ]);
            } else {
                $this->set("error", "An error occured. Please try again.");
            }
        }
    }

    public function createSession($user, $token, $remember) {
        $time  = 86400 * ($remember ?  30 : 1);
        $ipAdd = $this->request->getAddress();

        if ($ipAdd != "::1" && $ipAdd != "127.0.0.1" && $ipAdd != "localhost") {
            $client   = new Client();
            $response = json_decode(($client->get('https://ipapi.co/'.$ipAdd.'/json'))->getBody());
        
            $country = $response->country_code;
            $city    = $response->city;
            $region  = $response->region;

            $location = ''.$city.', '.$region.' '.$country.'';
        } else {
            $location = "localhost";
        }

        
        return UsersSessions::create([
            'user_id'    => $user->id,
            'access_key' => $token,
            'ip_address' => $ipAdd,
            'geo_loc'    => $location,
            'started'    => time(),
            'expires'    => time() + $time
        ]);
    }

    public function beforeExecute() {
        $this->disableView(true);
        return parent::beforeExecute();
    }

}
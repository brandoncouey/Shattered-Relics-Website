<?php
use GuzzleHttp\Client;
class LoginController extends Controller {


    public function index() {
        $csrf = new AntiCSRF();

        if ($this->request->hasQuery("discord")) {
            $params = http_build_query([
                'client_id'     => discord['client_id'],
                'redirect_uri'  => discord['redirect_uri'],
                'response_type' => 'code',
                'scope'         => 'identify'
            ]);

            $auth_url = "https://discordapp.com/api/oauth2/authorize?".$params;
            $this->request->redirect($auth_url, false);
            exit;
        }

        if ($this->request->isPost() && $csrf->isValidPost()) {
            $email = $this->request->getPost("email", "email");
            $pass  = $this->request->getPost("password");
            $remember = $this->request->getPost("remember") == "on"; 

            
            $user = Users::where('email', $email)->first();

            if (!$user) {
                $this->set("error", "Invalid email or password.");
            } else {

                if (!password_verify($pass, $user->password)) {
                    $this->set("error", "Invalid email or password.");
                } else {
                    if ($user->mfa_secret) {
                        $this->cookies->set("authenticate", $user->id, 300);
                        $this->cookies->set("remember", $remember == "on" ?  true : false, 300);
                        $this->request->redirect("login/authenticate");
                        exit;
                    }

                    $ipAdd = $this->request->getAddress();
                    $token = Functions::generateString(25);
                    $time  = 86400 * ($remember ? 30 : 1);
                    $saved = $this->createSession($user, $token, $remember);

                    if ($saved) {
                        $this->cookies->set("user_key", $token, $time);

                        $user->last_ip    = $ipAdd;
                        $user->last_login = time();
                        $user->save();

                        $this->request->redirect("");
                        exit;
                    } else {
                        $this->set("error", "An error occured. Please try again.");
                    }
                }
            }
        }

        $this->set("csrf_token", $csrf->getToken());
        return true;
    }

    public function authenticate() {
        if (!$this->cookies->has("authenticate")) {
            $this->request->redirect("login");
            exit;
        }

        $csrf = new AntiCSRF();

        if ($this->request->isPost() && $csrf->isValidPost()) {
            $user_id  = Functions::filterInt($this->cookies->get("authenticate"));
            $remember = Functions::filterInt($this->cookies->get("remember"));
            $user     = Users::where('id', $user_id)->first();

            if (!$user) {
                $this->cookies->delete("authenticate");
                $this->request->redirect("login");
                exit;
            }

            $code   = $this->request->getPost("code", "int");
            $secret = $user['mfa_secret'];

            $tfa       = new RobThree\Auth\TwoFactorAuth(site_title);
            $verified  = $tfa->verifyCode($secret, $code);

            if ($verified) {
                $time  = $remember && $remember == "on" ? (86400 * 30) : 86400;
                $token = Functions::generateString(25);
                $saved = $this->createSession($user, $token, $remember && $remember == "on");

                if ($saved) {
                    $this->cookies->set("user_key", $token, $time);
                    $this->request->redirect("");
                    exit;
                }
            }
        }

        $this->set("csrf_token", $csrf->getToken());
        return true;
    }

    public function auth() {
        $this->setView("login/index");

        if (!$this->request->hasQuery("code")) {
            $this->request->redirect("");
            exit;
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
                exit;
            }

            $discord_id = $userInfo->id;
            $user = Users::where('discord_id', $discord_id)->first();

            if (!$user) {
                $this->request->redirect("login");
                exit;
            }

            $token = Functions::generateString(25);
            $saved = $this->createSession($user, $token, false);

            if ($saved) {
                $this->cookies->set("user_key", $token);

                $user->last_ip    = $this->request->getAddress();
                $user->last_login = time();
                $user->save();

                $this->set("success", "Login Succesful! Please wait while we redirect you.");
                $this->request->delayedRedirect("", 1, true);
            } else {
                $this->set("error", "An error occured. Please try again.");
            }
        } else {
            $this->set("error", $response->error_description);
        }

        return true;
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

        $session = new UsersSessions;

        $session->fill([
            'user_id'    => $user['id'],
            'access_key' => $token,
            'ip_address' => $ipAdd,
            'geo_loc'    => $location,
            'started'    => time(),
            'expires'    => time() + $time
        ]);

        return $session->save();
    }
}
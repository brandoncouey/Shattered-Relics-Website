<?php
class PagesController extends Controller {

    public function index() {
        return true;
    }

    public function download() {
        return true;
    }

    public function team() {
        return true;
    }

    public function media() {
        return true;
    }

    public function funding() {
        return true;
    }

    public function contact() {
        $csrf = new AntiCSRF;

        if ($this->request->isPost() && $csrf->isValidPost()) {
            $data = [
                'first_name' => $this->request->getPost("first_name", "string"),
                'last_name'  => $this->request->getPost("last_name", "string"),
                'email'      => $this->request->getPost("email", "email"),
                'reason'     => $this->request->getPost("reason", "string"),
                'message'    => $this->request->getPost("message", "string"),
                'time_sent'  => time()
            ];

            $validation = Messages::validate($data);

            if ($validation->fails()) {
                $errors = $validation->errors();
                $this->set("errors", $errors->firstOfAll());
            } else {
                $msg = (new Messages)->fill($data);
                $msg->save();
                $this->set("success", "Your message has been sent! Thank you!");
            }
        }

        $this->set("csrf_token", $csrf->getToken());
        return true;
    }

    public function terms() {
        return true;
    }

    public function privacy() {
        return true;
    }
    
    public function faq() {
        return true;
    }


}
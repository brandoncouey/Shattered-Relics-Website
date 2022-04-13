<?php
class SubscribeController extends Controller {
    
    public function index() {
        if ($this->request->hasPost("email")) {
            $email = $this->request->getPost("email", "email");

        }
    }

    public function unsubscribe() {
        if ($this->request->hasQuery("email")) {
            $email = $this->request->getQuery("email", "email");

        }
    }
}
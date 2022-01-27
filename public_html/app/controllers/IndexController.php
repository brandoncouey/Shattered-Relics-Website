<?php
class IndexController extends Controller {

    public function index($catId = null) {
        $csrf = new AntiCSRF();

        if ($this->request->hasPost("sub_email")) {
            $email = $this->request->getPost("sub_email", "email");
            $entry = EmailList::where("email", $email)->first();

            if ($entry) {
                $this->redirect("");
                exit;
            }

            $entry = (new EmailList)->fill(['email' => $email]);
            $entry->save();
            $this->setView("subscribe/index");
        }

        return true;
    }
    
}
<?php
class UsersController extends Controller {
    
    public function index() {
        if ($this->request->hasQuery("ban")) {
            $userId = $this->request->getQuery("ban", "int");
            $user   = Users::where("id", $userId)->first();

            if (!$user || $user->id == $this->user->id || $user->rank == "owner") {
                $this->redirect("admin/users");
                exit;
            }

            $user->rank = $user->rank == "banned" ? "member" : "banned";

            if ($user->update()) {
                $this->redirect($_SERVER['HTTP_REFERER'], false);
                exit;
            }

            echo 'saved?';
        }
        
        if ($this->request->hasQuery("rank")) {
            $userId = $this->request->getQuery("id", "int");
            $rank   = $this->request->getQuery("rank", "string");
            $user   = Users::where("id", $userId)->first();

            if (!$user 
                    || $user->id == $this->user->id 
                    || $user->rank == "owner"
                    || !Security::isValidRole($rank)) {
                $this->redirect("admin/users");
                exit;
            }

            $user->rank = $rank;

            if ($user->update()) {
                $this->redirect($_SERVER['HTTP_REFERER'], false);
                exit;
            }

        }

        if ($this->request->hasQuery("username")) {
            $username = $this->request->getQuery("username", "string");
            $users = Users::where("username", 'LIKE', "%$username%")    
                ->orderBy("created","DESC")->paginate(15);
        } else {
            $users = Users::orderBy("created","DESC")->paginate(15);
        }

        $this->set("users", $users);
        return true;
    }
    
}
<?php
use Fox\CSRF;
use Fox\Paginator;

class IndexController extends Controller {

    private static $valid_sorts = ['latest', 'top', 'newest', 'oldest'];

    public function index($catId = -1, $page = 1) {
        $categories = Categories::buildList($catId);

        $sort = strtolower($this->request->getQuery("sort", "string"));
        
        if (!$sort || !in_array($sort, self::$valid_sorts)) {
            $sort = "latest";
        } else {
            $this->set("sort", $sort);
        }

        if ($catId != -1) {
            $category = Categories::where('id', $catId)->first();

            if (!$category || !$category->active) {
                $this->setView("errors/show404");
                return false;
            }

            $view_perms = json_decode($category->view_perms);

            if ($view_perms == null) {
                $this->setView("errors/show401");
                return false;
            }

            $can_view = in_array($this->user ? $this->userRole->getName() : 'Guest', $view_perms);

            if (!$can_view) {
                $this->setView("errors/show401");
                return false;
            }

            $topics = Topics::getByCategory($category->id, $sort);
            $this->set("curcat", $category);
            
            if ($this->user) {
                $post_perms = json_decode($category->post_perms);
                $can_post   = in_array($this->userRole->getName(), $post_perms);
                $this->set("canpost", $can_post);
            } else {
                $this->set("canpost", false);
            }
        } else {
            $topics = Topics::getAllTopics($sort, $page);
            $this->set("canpost", false);
        }

        if ($topics) {
            $results = ReadTopics::getReadTopics($this->user, $topics);
            
            $this->set("topics", $results);
            $this->set("page", $page);
        }
        
        $this->set("categories", $categories);
        return true;
    }

    public function beforeExecute() {
        if ($this->getActionName() == "login") {
            $this->access = [
                'login_required' => true,
                'allowed_ranks'  => ['member', 'moderator', 'admin']
            ];
        }
        return parent::beforeExecute();
    }

    public function logout() {
        if ($this->cookies->has("user_key")) {
            $user_key = $this->cookies->get("user_key");
            $session  = UsersSessions::where('access_key', $user_key)->first();

            if ($session)
                $session->delete();
            
            $this->cookies->delete("user_key");
        }

        $this->request->redirect("");
        exit;
    }

}
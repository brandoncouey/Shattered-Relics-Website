<?php
use Fox\CSRF;
use Fox\Request;
use Fox\Paginator;

class TopicController extends Controller {

    public function view($topicId, $page = 1) {
        $topic = Topics::getById($topicId);

        if (!$topic) {
            $this->setView("errors/show404");
            return false;
        }

        $category = Categories::where('id', $topic->parent)->first();

        if ($category && $this->user) {
            $view_perms = json_decode($category->view_perms);
            $can_view   = in_array($this->userRole->getName(), $view_perms);
            
            if (!$can_view) {
                $this->setView("errors/show401");
                return false;
            }
        }

        $topic_url = Functions::friendlyTitle($topic->id.'-'.$topic->title);

        if ($this->user) {
            if ($this->user->isStaff()) {
                if ($this->request->hasQuery("sticky")) {
                    $topic->sticky = $topic->sticky == 0 ? 1 : 0;
                    $topic->save();
                    $this->redirect("topic/view/".$topic_url);
                    return false;
                }
                if ($this->request->hasQuery("lock")) {
                    $topic->state = $topic->state == 0 ? 1 : 0;
                    $topic->save();
                    $this->redirect("topic/view/".$topic_url);
                    return false;
                }
                if ($this->request->hasQuery("delreply")) {
                    $replyId = $this->request->getQuery("delreply", "int");
                    $reply   = Replies::where('id', $replyId)->first();
                    if ($reply) 
                        $reply->delete();
                    $this->redirect("topic/view/".$topic_url);
                    return false;
                }
            }

            $read = ReadTopics::where('user_id', $this->user->getId())->get();

            if (!in_array($topic->id, array_column($read->toArray(), 'topic_id'))) {
                ReadTopics::insert([
                    'user_id'  => $this->user->getId(),
                    'topic_id' => $topic->id
                ]);
            }
        }

        $category = Categories::where('id', $topic->parent)->first();

        if (!$category) {
            $this->set("error", "This topic is not in a valid category.");
        } else {
            $this->set("curcat", $category);
        }

        $purifier = $this->getPurifier();
        $replies  = Replies::getByTopic($topic->id, $page);

        if ($replies) {
            $this->set("replies", $replies);
            $this->set("page", $page);
        }
        
        $this->set("topic", $topic);
        $this->set("purifier", $purifier);
        return true;
    }

    public function edit($topicId) {
        $topic = Topics::getById($topicId);

        if (!$topic) {
            $this->setView("errors/show404");
            return false;
        }

        if ($this->user->id != $topic->author) {
            $this->setView("errors/show401");
            return false;
        }

        if ($this->request->isPost()) {
            $data = [
                'title'      => $this->request->getPost("title", "string"),
                'topic_body' => $this->purify($this->request->getPost("info")),
                'last_edit'  => time(),
                'edited_by'  => $this->user->getUsername()
            ];

            $validation = Topics::validate($data);

            if ($validation->fails()) {
                $errors = $validation->errors();
                $this->set("errors", $errors->firstOfAll());
            } else {
                $topic->fill($data);

                if ($topic->save()) {
                    $seo = Functions::friendlyTitle($topic->id.'-'.$topic->title);
                    $this->redirect("topic/view/".$seo);
                    exit;
                }
            }
        }

        $purifier = $this->getPurifier();

        $this->set("topic", $topic);
        $this->set("purifier", $purifier);
        return true;
    }

    public function deletetopic($id) {
        $topic = Topics::getById($id);

        if (!$topic) {
            $this->setView("errors/show404");
            return false;
        }

        $user = $this->user;

        if (!$user->isStaff()) {
            $this->setView("errors/show401");
            return false;
        }

        if ($this->request->isPost()) {
            try {
                $this->csrf->check('my_token', $_POST['_token']);
            } catch(Exception $e) {
                $this->set("errmsg", $e->getMessage());
                $this->setView("errors/show500");
                return false;
            }
            $replies = Replies::where("parent", $topic['id'])->get();

            foreach ($replies as $reply) {
                $reply->delete();
            }

            $topic->delete();
            $this->redirect("");
            return false;
        }
        
        $token = $this->csrf->generate('my_token');

        $this->set("topicId", $topic['id']);
        $this->set("csrf_token", $token);
        $this->set("topic", $topic);
        return true;
    }

    public function reply() {
        $body   = $this->request->getPost("body");
        $parent = $this->request->getPost("parent", "int");

        $topic  = Topics::getById($parent);

        if (!$topic) {
            return [
                'success' => false,
                'message' => 'This thread does not exist.'
            ];
        }
        
        $body = $this->purify($body);

        if (strlen($body) <= 13 || strlen($body) > 65535) {
            return [
                'success' => false,
                'message' => 'Content field is required. '
            ];
        }

        $data = [
            'parent'  => $topic['id'],
            'author'  => $this->user->getId(),
            'body'    => $body,
            'posted'  => time(),
        ];

        $validation = Replies::validate($data);

        if ($validation->fails()) {
            $errors = $validation->errors();
            return [
                'success' => false,
                'message' => $errors,
            ];
        } else {
            $reply = new Replies;
            $reply->fill($data);

            if ($reply->save()) {
                $topic->last_reply = time();
                $topic->save();

                return [
                    'success' => true,
                    'message' => 'Your reply has been posted!'
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to save reply.'
            ];
        }
    }

    public function new() {
        $title = $this->request->getPost("title", "string");
        $body  = $this->request->getPost("body");
        $catId = $this->request->getPost("category", "int");

        $category = Categories::where('id', $catId)->first();

        if (!$category) {
            return [
                'success' => false,
                'message' => ''
            ];
        }

        $body  = $this->purify($body);

        if (strlen($title) < 3 || strlen($title) > 120) {
            return [
                'success' => false,
                'message' => 'Title must be at least 3 characters.'
            ];
        }

        if (strlen($body) <= 13 || strlen($body) > 65535) {
            return [
                'success' => false,
                'message' => 'Content field is required. '.strlen($body)
            ];
        }

        $topics = new Topics;

        $topics->fill([
            'parent'     => $category['id'],
            'author'     => $this->user->getId(),
            'title'      => $title,
            'topic_body' => $body,
            'started'    => time(),
            'last_reply' => time(),
            'last_edit' => -1,
            'state'      => 0,
            'sticky'     => $this->request->getPost("sticky") ? 1 : 0
        ]);

        $inserted = $topics->save();

        return [
            'success' => $inserted, 
            'message' => $inserted ? "Your discussion has been posted!" : "Failed to post discussion."
        ];
    }

   public function beforeExecute() {
        if ($this->getActionName() == "new" || $this->getActionName() == "reply") {
            $this->disableView(true);
        }
        return parent::beforeExecute();
    }

    public function logout() {
        if ($this->cookies->has("user_key")) {
            $user_key = $this->cookies->get("user_key");
            UsersSessions::delete($user_key);
            $this->cookies->delete("user_key");
        }
        $this->request->redirect("");
    }

}
<?php
class RegisterController extends Controller {
    
    public function index() {
        $csrf = new AntiCSRF();

        if ($this->request->isPost() && $csrf->isValidPost()) {
            $data = [
                'username' => $this->request->getPost("username", "string"),
                'password' => $this->request->getPost("password"),
                'repeat'   => $this->request->getPost("repeat"),
                'email'    => $this->request->getPost("email", "email"),
            ];
            
            $errors = null;

            if (trim($data['username']) != $data['username']) {
                $errors[] = "Excessive spaces are not allowed in your display name.";
            } else if ($data['password'] != $data['repeat']) {
                $errors[] = "The passwords you've entered do not match.";
            } else {
                $user = Users::where('username', $data['username'])
                    ->orWhere("email", $data['email'])
                    ->first();

                if ($user) {
                    $errors[] = "Username or email already in use.";
                } else {
                    $validation = Users::validate($data);

                    if ($validation->fails()) {
                        $errors = $validation->errors();
                        $this->set("errors", $errors->firstOfAll());
                    } else {
                        $user = new Users;

                        $user->fill([
                            'username' => $data['username'],
                            'password' => password_hash($data['password'], PASSWORD_BCRYPT),
                            'email'    => $data['email'],
                            'rank'     => 'member',
                            'status'   => 'active',
                            'created'  => time()
                        ]);

                        if ($user->save()) {
                            $this->request->redirect("login");
                            exit;
                        }
                    }
                }
            }

            if (!empty($errors)) {
                $this->set("errors", $errors);
            }
        }

        $this->set("csrf_token", $csrf->getToken());
        return true;
    }
    
}
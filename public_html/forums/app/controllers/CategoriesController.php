<?php
class CategoriesController extends Controller {

    public function index($category = -1) {
        if ($category != -1) {
            $parent = Categories::where("id", $category)->first();

            if (!$parent) {
                $this->setView("errors/show404");
                return false;
            }

            $this->set("parent", $parent);
        }

        if ($category == -1) {
            $categories = Categories::buildList();
        } else {
            $categories = Categories::select("*")
                ->where("parent", $category)
                ->get();
        }

        $this->set("categories", $categories);
        return true;
    }

    public function add($parentId = -1) {
        if ($parentId != -1) {
            $parent = Categories::where("id", $parentId)->first();

            if (!$parent) {
                $this->setView("errors/show404");
                return false;
            }

            $this->set("parent", $parent);
        }

        if ($this->request->isPost()) {
            $visible = $this->request->getPost("visible", "string");
            $canView = $this->request->getPost("view_perms");
            $canPost = $this->request->getPost("post_perms");

            $data = [
                'title'    => $this->request->getPost("title", "string"),
                'icon'     => $this->request->getPost("icon", "string"),
                'parent'   => $this->request->getPost("parent", "int"),
                'view_perms' => json_encode($canView ? $canView : []),
                'post_perms' => json_encode($canPost ? $canPost : []),
                'visible'  => $visible && $visible == "on" ? 1 : 0,
            ];

            $validation = Categories::validate($data);

            if ($validation->fails()) {
                $errors = $validation->errors();
                print_r($errors->firstOfAll());
                $this->set("errors", $errors->firstOfAll());
            } else {
                $category = new Categories;
                $category->fill($data);

                if ($category->save()) {
                    $this->redirect("admin/categories");
                    exit;
                }
            }
        }
        
        $categories = Categories::where("parent", -1)->get();
        $this->set("categories", $categories);
        $this->set("roles", Security::getRoles());
        return true;
    }

    public function edit($catId) {
        $category = Categories::where("id", $catId)->first();

        if (!$category) {
            $this->setView("errors/show404");
            return false;
        }

        if ($this->request->isPost()) {
            $visible = $this->request->getPost("visible", "string");
            $canView = $this->request->getPost("view_perms");
            $canPost = $this->request->getPost("post_perms");

            $data = [
                'title'      => $this->request->getPost("title", "string"),
                'icon'       => $this->request->getPost("icon", "string"),
                'parent'     => $this->request->getPost("parent", "int"),
                'view_perms' => json_encode($canView ? $canView : []),
                'post_perms' => json_encode($canPost ? $canPost : []),
                'visible'    => $visible && $visible == "on" ? 1 : 0,
            ];

            $validation = Categories::validate($data);

            if ($validation->fails()) {
                $errors = $validation->errors();
                $this->set("errors", $errors->firstOfAll());
            } else {
                $category->fill($data);

                if ($category->save()) {
                    $this->redirect("admin/categories");
                    exit;
                }
            }

            $this->debug($this->request->getPost());
        }

        $this->set("categories", Categories::where("parent", -1)->get());
        $this->set("category", $category);
        $this->set("roles", Security::getRoles());
        return true;
    }

    public function delete($catId) {
        $category = Categories::where("id", $catId)->first();

        if (!$category) {
            $this->setView("errors/show404");
            return false;
        }

        $this->set("category", $category);

        if ($category->parent == -1) {
            $children = Categories::where("parent", $category->id)->get();

            if ($children && count($children) > 0) {
                $this->set("error", "This category has children and can not be removed. Delete the children first. (you monster)");
                return true;
            }
        }

        if ($this->request->isPost()) {
            $category->delete();
            $this->redirect("admin/categories");
            exit;
        }
        return true;
    }

}
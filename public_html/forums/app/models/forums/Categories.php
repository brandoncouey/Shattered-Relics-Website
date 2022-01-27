<?php
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Rakit\Validation\Validator;

class Categories extends EloquentModel {

    public $incrementing = true;
    public $timestamps   = false;
    public $primaryKey   = 'id';

    public $table = "forum_categories";

    protected $fillable = [
        'title',
        'parent',
        'active',
        'icon',
        'post_perms',
        'view_perms'
    ];

    ///#([a-f0-9]{3}){1,2}\b/i
    public static function validate($validate){
        $validator = new Validator;

        $validation = $validator->validate($validate, [
            'title'   => 'required|min:3|max:75',
            'icon'    => 'required|regex:/#([a-f0-9]{3}){1,2}\b/i',
            'parent'  => 'required|numeric',
            'visible' => 'required|numeric|min:0|max:1'
        ]);

        return $validation;
    }

    public static function buildList() {
        $categories = Categories::where('active', 1)
            ->where('parent', -1)
            ->get()
            ->toArray();

        $list = [];

        for($index = 0; $index < count($categories); $index++) {
            $category = $categories[$index];
            $category['children'] = [];
            $list[$category['id']] = $category;
        }
        
        $children = Categories::where('active', 1)
            ->where('parent', '!=', -1)
            ->get()
            ->toArray();

        foreach ($children as $child) {
            $list[$child['parent']]['children'][] = $child;
        }
        return array_values($list);
    }

}
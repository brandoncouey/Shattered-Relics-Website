<?php
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Rakit\Validation\Validator;

class EmailList extends EloquentModel {

    public $incrementing  = true;
    public $timestamps    = false;
    protected $primaryKey = 'id';

    protected $fillable = [
        'email'
    ];

    protected $table = "email_list";

    public static function validate($validate){
        $validation = (new Validator)->validate($validate, [
            'email' => 'required|email'
        ]);
        return $validation;
   }

}
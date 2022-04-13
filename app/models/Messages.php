<?php
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Rakit\Validation\Validator;

class Messages extends EloquentModel {

    public $incrementing  = true;
    public $timestamps    = false;
    protected $primaryKey = 'id';

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'reason',
        'message',
        'time_sent'
    ];

    protected $table = "messages";

    public static function validate($validate) {

        $validation = (new Validator)->validate($validate, [
            'first_name' => 'required|min:0|max:255',
            'last_name'  => 'min:0|max:255',
            'email'      => 'required|email',
            'reason'     => 'required|min:0|max:255',
            'message'    => 'required|min:0|max:65535'
        ]);

        return $validation;
   }

}
<?php
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Rakit\Validation\Validator;

class UsersSessions extends EloquentModel {

    public $incrementing  = true;
    public $timestamps    = false;
    protected $primaryKey = 'id';

    protected $fillable = [
        'user_id',
        'access_key', 
        'ip_address', 
        'geo_loc',
        'deleted_by', 
        'started', 
        'last_visit', 
        'expires',
    ];

}
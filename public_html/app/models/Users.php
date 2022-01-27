<?php
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Rakit\Validation\Validator;

class Users extends EloquentModel {

    public $incrementing  = true;
    public $timestamps   = false;
    protected $primaryKey = 'id';

    protected $fillable = [
        'username',
        'password', 
        'email', 
        'rank',
        'discord_id', 
        'avatar_url', 
        'mfa_secret', 
        'status',
        'last_ip',
        'last_login',
        'created', 
    ];

    public static function validate($validate){
        $validator = new Validator;

        $validation = $validator->validate($validate, [
            'username'    => 'required',
            'email'       => 'required|email',
            'password'    => 'required|min:6|max:30',
            'avatar_url'  => 'url:http,https',
        ]);

        return $validation;
   }

    public function isRole($search) {
        return strtolower($search) == strtolower($this->rank);
    }

    public function isOwner() {
        return strtolower($this->rank) == "owner";
    }

    public function isAdmin() {
        return strtolower($this->rank) == "admin" || $this->isOwner();
    }

    public function isModerator() {
        return strtolower($this->rank) == "moderator" || $this->isAdmin() || $this->isAdmin();
    }

    public function isStaff() {
        return $this->isModerator();
    }

    public function getId() {
        return $this->id;
    }

    public function getUsername() {
        return $this->username;
    }

    public function getEmail() {
        return $this->email;
    }

    public function getRank() {
        return $this->rank;
    }

    public function getDiscordId() {
        return $this->id;
    }

    public function getAvatarUrl() {
        return $this->avatar_url;
    }

    public function getMfaSecret() {
        return $this->mfa_secret;
    }

    public function getStatus() {
        return $this->status;
    }

    public function getLastIp() {
        return $this->last_ip;
    }

    public function getLastLogin() {
        return $this->last_login;
    }

    public function getCreated() {
        return $this->created;
    }

}
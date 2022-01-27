<?php
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Rakit\Validation\Validator;
use Illuminate\Pagination\Paginator;

class Replies extends EloquentModel {

    public $timestamps   = false;
    protected $table = "forum_replies";

    protected $fillable = [
        'author',
        'parent', 
        'body',
        'visible', 
        'posted', 
    ];

    public static function validate($validate){
        $validator = new Validator;

        $validation = $validator->validate($validate, [
            'author'     => 'required',
            'parent'     => 'required|numeric',
            'body'       => 'required|min:8',
            'visible'    => 'numeric',
            'posted'     => 'numeric'
        ]);

        return $validation;
    }

    public static function getByTopic($topicId, $page) {
        Paginator::currentPageResolver(function() use ($page) {
            return $page;
        });
        
        $curTime = time();

        return self::query()
            ->select([
                'forum_replies.id',
                'forum_replies.parent',
                'forum_replies.author',
                'forum_replies.body',
                'forum_replies.posted',
                'users.username',
                'users.rank',
                'users.status',
                'users.last_login',
                'users.avatar_url',
                'users.created'
            ])
            ->selectRaw("(SELECT us.last_visit
                FROM users_sessions us 
                WHERE us.user_id = forum_replies.author 
                    AND $curTime - us.last_visit < 600 
                LIMIT 1) AS last_visit")
            ->where('forum_replies.parent', $topicId)
            ->leftjoin("users", "users.id", '=', "forum_replies.author")
            ->paginate(10);
    }

    public static function getRepliesChartData($data) {
        $query = Replies::select("posted")
            ->where('posted', '>=', $data['start'])
            ->orderby("posted", "ASC")
            ->get();

        foreach ($query as $topic) {
            $date = date($data['format'], $topic->posted);
            $data['chart'][$date]++;
        }

        return array_values($data['chart']);
    }
}
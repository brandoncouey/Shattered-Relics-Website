<?php
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Rakit\Validation\Validator;
use Illuminate\Pagination\Paginator;

class Topics extends EloquentModel {

    public $primaryKey   = 'id';
    public $incrementing = true;
    public $timestamps   = false;
    
    public $table = "forum_topics";

    protected $fillable = [
        'parent',
        'author', 
        'title', 
        'topic_body',
        'started', 
        'last_reply', 
        'last_edit', 
        'edited_by',
        'state',
        'sticky',
    ];

    public static function validate($validate){
        $validator = new Validator;

        $validation = $validator->validate($validate, [
            'title'      => 'required|min:6|max:150',
            'topic_body' => 'required|min:8',
        ]);

        return $validation;
    }

    public static function getById($id) {
        $curTime = time();

        return self::query()
            ->select([
                'forum_topics.id',
                'forum_topics.parent',
                'forum_topics.author',
                'forum_topics.title',
                'forum_topics.topic_body',
                'forum_topics.started',
                'forum_topics.state',
                'forum_topics.sticky',
                'users.username',
                'users.rank',
                'users.status',
                'users.last_login',
                'users.avatar_url',
                'users.created'
            ])
            ->selectRaw("(SELECT us.last_visit
                FROM users_sessions us 
                WHERE us.user_id = forum_topics.author 
                    AND $curTime - us.last_visit < 600 
                LIMIT 1) AS last_visit")
            ->where('forum_topics.id', $id)
            ->leftjoin("users", "users.id", '=', "forum_topics.author")
            ->first();
    }

    public static function getAllTopics($sort = "latest", $page) {
        Paginator::currentPageResolver(function() use ($page) {
            return $page;
        });

        return self::query()
            ->select([
                'forum_topics.id',
                'forum_topics.parent',
                'forum_topics.author',
                'forum_topics.title',
                'forum_topics.started',
                'forum_topics.state',
                'forum_topics.sticky',
                'users.username',
                'users.rank',
                'users.status',
                'users.last_login',
                'users.avatar_url',
                'users.created'
            ])
            ->selectRaw('(SELECT COUNT(*) FROM forum_replies WHERE forum_replies.parent = forum_topics.id) AS replies')
            ->leftjoin("users", "users.id", '=', "forum_topics.author")
            ->orderByRaw(self::getOrderBy($sort))
            ->paginate(15);
    }

    public static function getByCategory($id, $sort = "latest") {
        return self::query()
            ->select([
                'forum_topics.id',
                'forum_topics.parent',
                'forum_topics.author',
                'forum_topics.title',
                'forum_topics.started',
                'forum_topics.state',
                'forum_topics.sticky',
                'users.username',
                'users.rank',
                'users.status',
                'users.last_login',
                'users.avatar_url',
                'users.created'
            ])
            ->selectRaw('(SELECT COUNT(*) FROM forum_replies WHERE forum_replies.parent = forum_topics.id) AS replies')
            ->where('forum_topics.parent', $id)
            ->leftjoin("users", "users.id", '=', "forum_topics.author")
            ->orderByRaw(self::getOrderBy($sort))
            ->paginate(15);
    }

    private static function getOrderBy($sort) {
        $sort = strtolower($sort);

        if ($sort == "latest") {
            $orderBy = "forum_topics.sticky DESC, forum_topics.last_reply DESC";
        } else if ($sort == "newest") {
            $orderBy = "forum_topics.sticky DESC, forum_topics.started DESC";
        } else if ($sort == "top") {
            $orderBy = "forum_topics.sticky DESC, replies DESC";
        } else if ($sort == "oldest") {
            $orderBy = "forum_topics.sticky DESC, forum_topics.started ASC";
        } else {
            $orderBy = "forum_topics.sticky DESC, forum_topics.last_reply DESC";
        }

        return $orderBy;
    }

    public static function getTopicsChartData($data) {
        $query = Topics::select("started")
            ->where('started', '>=', $data['start'])
            ->orderby("started", "ASC")
            ->get();

        foreach ($query as $topic) {
            $date = date($data['format'], $topic->started);
            $data['chart'][$date]++;
        }

        return array_values($data['chart']);
    }

    public static function debug($array) {
        echo "<pre>".htmlspecialchars(json_encode($array, JSON_PRETTY_PRINT))."</pre>";
    }


}
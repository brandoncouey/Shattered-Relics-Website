<?php
use Illuminate\Database\Eloquent\Model as EloquentModel;

class ReadTopics extends EloquentModel {

    public $incrementing = true;
    public $timestamps   = false;
    public $primaryKey   = 'id';
    public $table = "forum_read_topics";

    protected $fillable = [
        'user_id',
        'topic_id',
    ];

    public static function getReadTopics($user, $results) {
        if ($user) {
            $read = ReadTopics::where('user_id', $user->getId())
                ->get()
                ->toArray();

            $values  = array_column($read, 'topic_id');
        } else {
            $values = [];
        }

        foreach ($results->items() as $topic) {
            $topic->is_read = in_array($topic->id, $values);
        }
        return $results;
    }
    
}
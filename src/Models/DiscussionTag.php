<?php

namespace FoF\Webhooks\Models;

use Flarum\Database\AbstractModel;

class DiscussionTag extends AbstractModel
{

    protected $table = 'discussion_tag';

    public static function getTagsNamesByDiscussionId(int $discussionId)
    {

        //TODO: this should be chunked
        return self::where('discussion_id', $discussionId)
            ->join('tags', 'discussion_tag.tag_id', '=', 'tags.id')
            ->pluck('tags.name')
            ->toArray();
    }
}

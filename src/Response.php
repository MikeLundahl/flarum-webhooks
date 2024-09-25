<?php

/*
 * This file is part of fof/webhooks.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\Webhooks;

use Carbon\Carbon;
use Flarum\Http\UrlGenerator;

use Flarum\Post\Event\Posted;
use Flarum\Post\Event\Deleted;
use Flarum\Post\Event\Restored;
use Flarum\Post\Event\Hidden;
use Flarum\Post\Event\Revised;

use Flarum\Discussion\Event\Started;
use Flarum\Discussion\Event\Deleted as DiscussionDeleted;
use Flarum\Discussion\Event\Hidden as DiscussionHidden;
use Flarum\Discussion\Event\Renamed as DiscussionRenamed;
use Flarum\Discussion\Event\Restored as DiscussionRestored;



use Flarum\User\User;
use FoF\Webhooks\Actions\Discussion\Renamed;
use FoF\Webhooks\Models\Webhook;
use FoF\Webhooks\Models\DiscussionTag;

class Response
{
    /**
     * @var string
     */
    public $title;

    /**
     * @var string
     */
    public $url;

    /**
     * @var string
     */
    public $description;

    /**
     * @var string
     */
    public $color;

    /**
     * @var string
     */
    public $tags;

    /**
     * @var string
     */
    public $timestamp;

    /**
     * @var User
     */
    public $author;

    public $event;

    /**
     * @var UrlGenerator
     */
    private $urlGenerator;

    /**
     * @var Webhook
     */
    protected $webhook;

    //TODO: If this is not used, remove it
    /**
     * @var DiscussionTag
     */
    protected $discussionTag;

    /**
     * Response constructor.
     *
     * @param $event
     */
    public function __construct($event)
    {
        $this->event = $event;
        $this->urlGenerator = resolve(UrlGenerator::class);
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function setURL(string $name, array $data = null, ?string $extra = null): self
    {
        $url = $this->urlGenerator->to('forum')->route($name, $data);

        if (isset($extra)) {
            $url = $url.$extra;
        }

        $this->url = $url;

        return $this;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function setAuthor(User $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function setColor(?string $color): self
    {
        $this->color = $color;

        return $this;
    }

    public function setTimestamp(?string $timestamp): self
    {
        $this->timestamp = $timestamp ?: Carbon::now();

        return $this;
    }

    public function getColor()
    {
        return $this->color ? hexdec(substr($this->color, 1)) : null;
    }

    public static function build($event): self
    {
        return new self($event);
    }

    public function getAuthorUrl(): ?string
    {
        return $this->author->exists ? $this->urlGenerator->to('forum')->route('user', [
            'username' => $this->author->username,
        ]) : null;
    }

    public function getExtraText(): ?string
    {
        return $this->webhook->extra_text;
    }

    public function getIncludeTags(): bool
    {
        return $this->webhook->include_tags;
    }

    public function setTagsByDiscussionId(int $discussionId): self
    {
        $this->discussionTags = DiscussionTag::getTagsNamesByDiscussionId($discussionId);

        return $this;
    }

    public function withWebhook(Webhook $webhook): self
    {
        $this->setWebhook($webhook);

        //TODO: check if "include tags" are enabled in the webhook settings

        if (
            $this->event instanceof Posted ||
            $this->event instanceof Revised ||
            $this->event instanceof Restored ||
            $this->event instanceof Hidden ||
            $this->event instanceof Deleted
        ) {
            $discussionId = $this->event->post->discussion->id;

            //TODO: remove this debug line
            resolve('log')->info("Discussion ID: $discussionId");
            $this->setTagsByDiscussionId($discussionId);
        }

        if (
            $this->event instanceof Started ||
            $this->event instanceof DiscussionDeleted ||
            $this->event instanceof DiscussionHidden ||
            $this->event instanceof DiscussionRenamed ||
            $this->event instanceof DiscussionRestored
        ) {
            $discussionId = $this->event->discussion->id;
            resolve('log')->info("Discussion ID: $discussionId");
            $this->setTagsByDiscussionId($discussionId);
        }

        return $this;
    }

    protected function setWebhook(Webhook $webhook)
    {
        $this->webhook = $webhook;
    }

    public function __toString()
    {
        return "Response{title=$this->title,url=$this->url,author={$this->author->display_name}}";
    }
}

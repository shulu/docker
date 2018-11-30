<?php
namespace Lychee\Module\Post\Tests;

use Lychee\Component\Test\ModuleAwareTestCase;
use Lychee\Module\Comment\CommentEvent;
use Lychee\Module\Post\PostEvent;

class EventTest extends ModuleAwareTestCase {

    public function test() {
        $event = new CommentEvent(1);
        $ed = $this->container()->get('lychee.event_dispatcher_async');
        $ed->dispatch(CommentEvent::DELETE, $event);
    }
    
}
<?php

namespace Gush\Listener;

use Guzzle\Common\Event;
use Github\Client;
use Github\Exception\RuntimeException;

class AuthListener
{
    private $tokenOrLogin;

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function onRequestBeforeSend(Event $event)
    {
        $event['request']->setHeader('Authorization', 'token '.$this->tokenOrLogin);
    }
}

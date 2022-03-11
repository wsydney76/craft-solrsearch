<?php

namespace wsydney76\solrsearch\events;

use yii\base\Event;

class AfterIndexElementEvent extends Event
{

    public $count = 0;
    public $current = 0;
}

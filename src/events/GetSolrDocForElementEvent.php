<?php

namespace wsydney76\solrsearch\events;

use yii\base\Event;

class GetSolrDocForElementEvent extends Event
{
    public $element = null;
    public $cancel = false;
    public $doc = [];
}

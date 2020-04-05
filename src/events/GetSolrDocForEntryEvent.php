<?php

namespace wsydney76\solrsearch\events;

use yii\base\Event;

class GetSolrDocForEntryEvent extends Event
{
    public $entry = null;
    public $cancel = false;
    public $doc = [];
}

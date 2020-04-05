<?php

namespace wsydney76\solrsearch\events;

use yii\base\Event;

class GetAllEntriesForSolrSearchEvent extends Event
{
    public $entries = [];
}

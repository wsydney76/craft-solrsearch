<?php

namespace wsydney76\solrsearch\events;

use yii\base\Event;

class GetAllElementsForSolrSearchEvent extends Event
{
    public $elements = [];
}

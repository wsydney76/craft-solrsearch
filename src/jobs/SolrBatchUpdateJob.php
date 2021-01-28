<?php
/**
 * search module for Craft CMS 3.x
 *
 * Solr search
 *
 * @link      https://github.com/wsydney76
 * @copyright Copyright (c) 2020 wsydney76
 */

namespace wsydney76\solrsearch\jobs;

use Craft;
use craft\queue\BaseJob;
use Exception;
use putyourlightson\logtofile\LogToFile;
use wsydney76\solrsearch\events\AfterIndexElementEvent;
use wsydney76\solrsearch\SolrSearch;
use wsydney76\solrsearch\services\SearchService;

/**
 * @author    wsydney76
 * @package   SearchModule
 * @since     1.0.0
 */
class SolrBatchUpdateJob extends BaseJob
{
    // Public Properties
    // =========================================================================

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function execute($queue)
    {
        $service = SolrSearch::$services->search;
        $callback = function(AfterIndexElementEvent $e) use ($queue) {
            $this->setProgress($queue, (float)$e->current / $e->count, "Entry {$e->current} of {$e->count}");
        };

        $service->on(SearchService::EVENT_AFTER_INDEX_ELEMENT, $callback);
        try {
            SolrSearch::$services->search->performUpdateAll();
        } catch (Exception $e) {
            LogToFile::error($e->getMessage(), SolrSearch::LOG_CATEGORY);
        }
        $service->off(SearchService::EVENT_AFTER_INDEX_ELEMENT, $callback);
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): string
    {
        return 'Updating full search index';
    }
}

<?php
/**
 * search module for Craft CMS 3.x
 *
 * Solr search
 *
 * @link      https://github.com/wsydney76
 * @copyright Copyright (c) 2020 wsydney76
 */

namespace wsydney76\solrsearch\console\controllers;

use craft\helpers\Console;
use Exception;
use wsydney76\solrsearch\events\AfterIndexElementEvent;
use wsydney76\solrsearch\SolrSearch;
use wsydney76\solrsearch\services\SearchService;
use yii\console\Controller;
use yii\console\ExitCode;
use const PHP_EOL;

/**
 * Search Command
 *
 * @author    wsydney76
 * @package   SearchModule
 * @since     1.0.0
 */
class SearchController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * Create new index records for all relevant entries
     *
     * @return int
     */
    public function actionUpdateAll(): int
    {
        if (!$this->confirm('Update index?')) {
            return ExitCode::OK;
        }

        $service = SolrSearch::$services->search;
        $callback = function(AfterIndexElementEvent $e) {
            if ($e->current == 1) {
                Console::startProgress(1, $e->count, 'Indexing entries: ', 0.7);
            }
            Console::updateProgress($e->current, $e->count);
        };

        $service->on(SearchService::EVENT_AFTER_INDEX_ELEMENT, $callback);
        try {
            SolrSearch::$services->search->performUpdateAll();
        } catch (Exception $e) {
            $this->stderr($e->getMessage() . PHP_EOL);
            return ExitCode::UNAVAILABLE;
        }
        $service->off(SearchService::EVENT_AFTER_INDEX_ELEMENT, $callback);

        return ExitCode::OK;
    }

    /**
     * Delete all index records
     *
     * @return int
     */
    public function actionDeleteAll(): int
    {
        if (!$this->confirm('Delete index?')) {
            return ExitCode::OK;
        }

        try {
            SolrSearch::$services->search->deleteAll();
        } catch (Exception $e) {
            $this->stderr($e->getMessage() . PHP_EOL);
            return ExitCode::UNAVAILABLE;
        }

        return ExitCode::OK;
    }
}

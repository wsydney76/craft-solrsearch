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
use wsydney76\solrsearch\SolrSearch;

/**
 * @author    wsydney76
 * @package   SearchModule
 * @since     1.0.0
 */
class SolrCommandJob extends BaseJob
{
    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $command = '';
    public $commit = true;
    public $url = '';

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function execute($queue)
    {
        $client = Craft::createGuzzleClient();
        $url = $this->url;
        if ($this->commit) {
            $url .= '?commit=true';
        }
        try {
            $client->request('POST', $url, ['json' => $this->command]);
        } catch (Exception $e) {
            Craft::error($e->getMessage(), SolrSearch::LOG_CATEGORY);
        }
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): string
    {
        return 'Executing Solr Command';
    }
}

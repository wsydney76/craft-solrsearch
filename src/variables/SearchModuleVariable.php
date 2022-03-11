<?php
/**
 * search module for Craft CMS 3.x
 *
 * Solr search
 *
 * @link      https://github.com/wsydney76
 * @copyright Copyright (c) 2020 wsydney76
 */

namespace wsydney76\solrsearch\variables;

use wsydney76\solrsearch\SolrSearch;

/**
 * @author    wsydney76
 * @package   SearchModule
 * @since     1.0.0
 */
class SearchModuleVariable
{
    // Public Methods
    // =========================================================================

    /**
     * @param $q
     * @return array
     * @throws \yii\web\BadRequestHttpException
     */
    public function search($searchParamsModel)
    {
        return SolrSearch::$services->search->search($searchParamsModel);
    }
}

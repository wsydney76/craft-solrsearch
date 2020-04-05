<?php
/**
 * search module for Craft CMS 3.x
 *
 * Solr search
 *
 * @link      https://github.com/wsydney76
 * @copyright Copyright (c) 2020 wsydney76
 */

namespace wsydney76\solrsearch\services;

use Craft;
use craft\base\Component;
use craft\helpers\Json;
use GuzzleHttp\Client;
use wsydney76\solrsearch\jobs\SolrCommandJob;
use wsydney76\solrsearch\models\SearchParamsModel;
use yii\web\BadRequestHttpException;

/**
 * @author    wsydney76
 * @package   SearchModule
 * @since     1.0.0
 */
class SolrService extends Component
{

    private $_client = null;
    private $_queryUrl = '';
    private $_updateUrl = '';

    // Public Methods
    // =========================================================================

    // http://localhost:8989/solr/filmdb/select?version=2.2&mm=1%3C-2%206%3C70%25&facet.limit=500&qt=edismax&hl=on&spellcheck.build=false&fl=score%2Ckey%2Cid%2Ctype%2Cslug%2Ctitle%2Cname%2Cfilm%2Cactress%2Cseriestitle%2Cprodyear%2Cimagefile&hl.fl=title%2Cname%2Cfilm%2Cactress%2Cseriestitle%2Cprodyear&spellcheck.collate=true&facet.field=prodyear&facet.field=seriestitle_exact&facet.field=actress_exact&debugQuery=false&facet.sort=index&facet=on&hl.simple.post=%3C%2Fi%3E%3C%2Fb%3E&q=Anja&hl.simple.pre=%3Cb%3E%3Ci%3E&f.titel.qf=title&qf=title%5E10.0%20name%5E10.0%20film%5E1.0%20actress%201%5E0%20seriestitle%5E1.0%20prodyear%5E1.0&pf=title%5E10.0%20name%5E10.0%20film%5E1.0%20actress%201%5E0%20seriestitle%5E1.0%20prodyear%5E1.0&sort=score%20desc%2Cseriestitle%20asc%2Ctitle_sort%20asc&wt=json&spellcheck.extendedResults=true&rows=999&spellcheck=true&f.wer.qf=name

    /**
     * @inheritDoc
     */
    public function init() {
        $this->_queryUrl = Craft::$app->config->general->solrSearch['queryUrl'];
        $this->_updateUrl = Craft::$app->config->general->solrSearch['updateUrl'];
        parent::init();
    }

    /**
     * @param SearchParamsModel $params
     * @return mixed
     * @throws BadRequestHttpException
     */
    public function query(SearchParamsModel $params)
    {

        $client = $this->_getClient();
        $url = $this->_queryUrl;

        $response = $client->request('GET', $url, ['query' => $this->_getQueryString($params)]);

        if ($response->getStatusCode() != 200) {
            throw new BadRequestHttpException('Query exited with status ' . $response->getStatusCode());
        }

        return Json::decodeIfJson($response->getBody()->getContents());
    }

    public function command($cmd, $async = false, $commit = true, $description = 'Execution Solr Command')
    {
        $client = $this->_getClient();
        $url = $this->_updateUrl;

        if ($async) {
            Craft::$app->queue->push(new SolrCommandJob([
                'command' => $cmd,
                'description' => $description,
                'url' => $url,
                'commit' => $commit]));
        } else {
            if ($commit) {
                $url .= '?commit=true';
            }
            $client->request('POST', $url, ['json' => $cmd]);
        }

        return true;
    }

    public function addDoc($doc, $async = true, $commit = true)
    {
        $this->command(['add' => ['doc' => $doc]], $async, $commit, 'Updating Solr Index');
    }

    public function deleteDoc($id)
    {
        $this->command(['delete' => ['query' => "id:{$id}"]]);
    }

    public function deleteAll()
    {
        $this->command(['delete' => ['query' => '*:*']]);
    }

    public function commit()
    {
        $this->command('commit');
    }

    protected function _getClient(): Client
    {
        if (!$this->_client) {
            $this->_client = Craft::createGuzzleClient();
        }
        return $this->_client;
    }

    protected function _getQueryString(SearchParamsModel $params)
    {
        // https://stackoverflow.com/questions/13929075/sending-array-via-query-string-in-guzzle
        $query = http_build_query($params->getParams(), null, '&');

        return preg_replace('/%5B(?:[0-9]|[1-9][0-9]+)%5D=/', '=', $query);
    }

}

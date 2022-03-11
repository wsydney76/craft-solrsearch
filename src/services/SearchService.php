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
use craft\base\ElementInterface;
use craft\elements\Category;
use craft\elements\Entry;
use craft\helpers\ElementHelper;
use craft\commerce\elements\Product;
use putyourlightson\logtofile\LogToFile;
use wsydney76\solrsearch\events\AfterIndexElementEvent;
use wsydney76\solrsearch\events\GetAllElementsForSolrSearchEvent;
use wsydney76\solrsearch\events\GetSolrDocForElementEvent;
use wsydney76\solrsearch\jobs\SolrBatchUpdateJob;
use wsydney76\solrsearch\models\SearchParamsModel;
use wsydney76\solrsearch\SolrSearch;
use yii\base\Exception;
use function array_key_exists;

/**
 * @author    wsydney76
 * @package   SearchModule
 * @since     1.0.0
 */
class SearchService extends SolrService
{

    const EVENT_AFTER_INDEX_ELEMENT = 'afterIndexElement';
    const EVENT_GET_ALL_ELEMENTS_FOR_SOLR_SEARCH = 'getAllElementsForSolrSearch';
    const EVENT_GET_SOLR_DOC_FOR_ELEMENT = 'getSolrDocForElement';

    // Public Methods
    // =========================================================================

    /**
     * @param $q
     * @return array
     * @throws \yii\web\BadRequestHttpException
     */
    public function search(SearchParamsModel $searchParamsModel, $format = true)
    {
        $result = $this->query($searchParamsModel);
        return $format ? $this->formatResult($result) : $result;
    }

    /**
     * @param Entry $element
     * @throws Exception
     */
    public function updateElement(ElementInterface $element)
    {
        if (!($element instanceof Entry ||
            $element instanceof Category ||
            $element instanceof Product )) {
            return;
        }

        if ($element instanceof Entry && ElementHelper::isDraftOrRevision($element)) {
            return;
        }

        if ($element instanceof Entry && $element->status != 'live') {
            $this->deleteDoc($this->_getKey($element));
            return;
        }

        if ($element instanceof Category && $element->status != 'enabled') {
            $this->deleteDoc($this->_getKey($element));
            return;
        }

        $doc = $this->getSolrDocForElement($element);

        if (!$doc) {
            return;
        }

        $this->addDoc($doc, false);

        LogToFile::log("Indexed {$element->id}: ", SolrSearch::LOG_CATEGORY, 'index');
    }

    public function deleteElement(ElementInterface $element)
    {
        if ($element instanceof Entry && ElementHelper::isDraftOrRevision($element)) {
            return;
        }
        $this->deleteDoc($this->_getKey($element));
        return;
    }

    /**
     * @param ElementInterface $entry
     * @return array|bool
     * @throws Exception
     */
    public function getSolrDocForElement(ElementInterface $entry)
    {
        if (!$this->hasEventHandlers(self::EVENT_GET_SOLR_DOC_FOR_ELEMENT)) {
            return [];
            //throw new Exception('No event handler configured for getting solr doc');
        }

        $event = new GetSolrDocForElementEvent(['element' => $entry]);
        $this->trigger(self::EVENT_GET_SOLR_DOC_FOR_ELEMENT, $event);

        if ($event->cancel) {
            return false;
        }
        $doc = $event->doc;
        $doc['key'] = $this->_getKey($entry);
        return $doc;
    }

    public function updateAll()
    {
        // This job will call performUpdateAll
        Craft::$app->queue->push(new SolrBatchUpdateJob());
    }

    /**
     * @throws Exception
     */
    public function performUpdateAll()
    {

        if (!$this->hasEventHandlers(self::EVENT_GET_ALL_ELEMENTS_FOR_SOLR_SEARCH)) {
            throw new Exception('No event handler specified for defining entries to be indexed');
        }

        $event = new GetAllElementsForSolrSearchEvent();
        $this->trigger(self::EVENT_GET_ALL_ELEMENTS_FOR_SOLR_SEARCH, $event);

        $elements = $event->elements;

        $c = count($elements);
        $i = 0;

        $docs = [];

        foreach ($elements as $element) {

            $doc = $this->getSolrDocForElement($element);
            if (!$doc) {
                continue;
            }
            $i++;
            $docs[] = $doc;
            if ($this->hasEventHandlers(self::EVENT_AFTER_INDEX_ELEMENT)) {
                $this->trigger(self::EVENT_AFTER_INDEX_ELEMENT, new AfterIndexElementEvent(['count' => $c, 'current' => $i]));
            }
            LogToFile::log("Preparing ID {$element->id}", SolrSearch::LOG_CATEGORY, 'indexAll');
        }
        $this->command($docs);
        LogToFile::log("Update Command run", SolrSearch::LOG_CATEGORY, 'indexAll');
    }

    protected function formatResult($result)
    {
        // \Craft::dd($result);
        $rc = [
            'rcode' => 'OK',
            'recordcount' => $result['response']['numFound'],
            'time' => $result['responseHeader']['QTime'],
            'docs' => $result['response']['docs']
        ];

        if (array_key_exists('highlighting', $result)) {
            $highlights = $result['highlighting'];
            $i = 0;
            foreach ($rc['docs'] as $doc) {
                if (array_key_exists($doc['key'], $highlights)) {
                    $rc['docs'][$i]['highlighting'] = $highlights[$doc['key']];
                    $i++;
                }
            }
        }

        if (array_key_exists('facet_counts', $result)) {
            $facets = $result['facet_counts']['facet_fields'];
            $rc['facets'] = [];
            foreach ($facets as $field => $values) {
                $tmp = [];
                $c = count($values);
                for ($i = 0; $i < $c; $i += 2) {
                    if ($values[$i + 1]) {
                        $tmp[] = ['name' => $facets[$field][$i], 'count' => $facets[$field][$i + 1]];
                    }
                }
                $rc['facets'][$field] = $tmp;
            }
        }

        if (array_key_exists('spellcheck', $result)) {
            $rc['spellcheck'] = $result['spellcheck'];
        }

        return $rc;
    }

    protected function _getKey(ElementInterface $entry): string
    {
        return "{$entry->id}_{$entry->site->handle}";
    }
}

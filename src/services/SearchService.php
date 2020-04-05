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
use craft\elements\Entry;
use craft\helpers\ElementHelper;
use wsydney76\solrsearch\events\AfterIndexElementEvent;
use wsydney76\solrsearch\events\GetAllEntriesForSolrSearchEvent;
use wsydney76\solrsearch\events\GetSolrDocForEntryEvent;
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
    const EVENT_GET_ALL_ENTRIES_FOR_SOLR_SEARCH = 'getAllEntriesForSolrSearch';
    const EVENT_GET_SOLR_DOC_FOR_ENTRY = 'getSolrDocForEntry';

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
     * @param Entry $entry
     * @throws Exception
     */
    public function updateEntry(Entry $entry)
    {

        if (ElementHelper::isDraftOrRevision($entry)) {
            return;
        }

        if ($entry->status != 'live') {
            $this->deleteDoc($entry->id);
            return;
        }

        $doc = $this->getSolrDocForEntry($entry);
        if (!$doc) {
            return;
        }

        $this->addDoc($doc, false);

        Craft::info("Indexed {$entry->id}: ", SolrSearch::LOG_CATEGORY);


    }

    public function deleteEntry(Entry $entry) {
        if (ElementHelper::isDraftOrRevision($entry)) {
            return;
        }
        $this->deleteDoc($entry->id);
        return;
    }

    /**
     * @param Entry $entry
     * @return array|bool
     * @throws Exception
     */
    public function getSolrDocForEntry(Entry $entry)
    {
        if (! $this->hasEventHandlers(self::EVENT_GET_SOLR_DOC_FOR_ENTRY)) {
            throw new Exception('No event handler configured for getting solr doc');
        }

        $event = new GetSolrDocForEntryEvent(['entry' => $entry]);
        $this->trigger(self::EVENT_GET_SOLR_DOC_FOR_ENTRY, $event);
        if ($event->cancel) {
            return false;
        }
        return $event->doc;
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

        if (!$this->hasEventHandlers(self::EVENT_GET_ALL_ENTRIES_FOR_SOLR_SEARCH)) {
            throw new Exception('No event handler specified for defining entries to be indexed');
        }

        $event = new GetAllEntriesForSolrSearchEvent();
        $this->trigger(self::EVENT_GET_ALL_ENTRIES_FOR_SOLR_SEARCH, $event);

        $entries = $event->entries;

        $c = count($entries);
        $i = 0;

        foreach ($entries as $entry) {

            $doc = $this->getSolrDocForEntry($entry);
            if (! $doc) {
                continue;
            }
            $i++;
            $this->addDoc($doc, false, false);
            if ($this->hasEventHandlers(self::EVENT_AFTER_INDEX_ELEMENT)) {
                $this->trigger(self::EVENT_AFTER_INDEX_ELEMENT, new AfterIndexElementEvent(['count' => $c, 'current' => $i]));
            }
        }
        $this->commit();
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
                if (array_key_exists($doc['id'], $highlights)) {
                    $rc['docs'][$i]['highlighting'] = $highlights[$doc['id']];
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
}

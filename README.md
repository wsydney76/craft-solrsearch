# SolrSearch Plugin

## How to set up your project 

### Prerequisites

* Solr is installed
* A Solr core is created and configured for your project, especially `schema.xml`
* The schema must contain a field 'key' with type string. This key is handled internally. 

### Configure the Solr Base Url

The URL is likely to look like ` http://localhost:8989/solr/REPLACEWITHCORENAME`

* Use the plugin settings page to set the URL to your Solr Core. This can (and should) 
be set to an environment variable or alias. 
* You can also use a config file named `solrconfig.php`. This setting can be environment
specific. 

````
<?php
return [
    'solrBaseUrl' => 'your solr base url here'
];

````

### Add a SearchParamsModel to a module

This model defines the search parameters used to query Solr. 
It should specify the default parameters, the dynamic parameters can be set when creating an instance.

The fl parameter (fields to be retrieved) must contain the `key` field, if formatting is enabled (see below).

Example:

````
<?php

namespace modules\main\models;

class MySearchParamsModel extends \wsydney76\solrsearch\models\SearchParamsModel
{

    public $facet = 'on';
    public $facetField = ['prodyear', 'seriestitle_exact', 'actress_exact'];
    public $fl = 'score,key,id,type,slug,url,title,name,film,actress,seriestitle,prodyear,imagefile,station,remark';
    public $hl = 'on';
    public $hlFl = 'title,name,film,actress,seriestitle,prodyear,content,station,remark';
    public $hlSimplePost = '</i></b>';
    public $hlSimplePre = '<b><i>';
    public $mm = '1<-2 6<70%';
    public $pf = 'title^10.0 name^10.0 content^2.0 remark^1.0 film^1.0 actress 1^0 seriestitle^1.0 prodyear^1.0';
    public $q = '';
    public $qf = 'title^10.0 name^10.0 content^2.0 remark^1.0 film^1.0 actress 1^0 seriestitle^1.0 prodyear^1.0';
    public $sort = 'score desc,seriestitle asc,title_sort asc';
    public $spellcheck = 'true';
    public $spellcheckCollate = 'true';
    public $spellcheckExtendedResults = 'true';
}
````

In case you are using additional parameters to those defined in the base class, you will 
need to overwrite the `getParams()` method to pass those parameters.

This can also be necessary if defaults are set in the `solrconfig.xml` file and should not be
set on every single request. However this is not recommended, this file should only contain system 
settings and avoid project specifics.

### Search in Twig

Example:

````
{% set q = craft.app.request.getParam('q') %}
{% set searchParamsModel = create({class:'modules\\main\\models\\MySearchParamsModel', q:q}) %}
{% set results = craft.solrsearch.search(searchParamsModel) %}
````

### Search in Php

Example:

````
 $result = SolrSearch::$services->search->search(new MySearchParamsModel(['q' => 'searchterm']));
````

### Search Results Formatting

By default, Solr responses are formatted for easier use, highlighting results are merged into the single docs, 
and factes results are cleaned up.

Example:
````
[
    'rcode' => 'OK'
    'recordcount' => 2
    'time' => 15
    'docs' => [
        0 => [
            'id' => 1504
            'title' => 'Erna Klawuppke'
            'type' => 'person'       
            'url' => 'http://filmdb.local/erna-klawuppke'
            'imagefile' => 'erna-klawuppke.jpg'        
            'score' => 1.5163089
            'highlighting' => [
                'title' => [
                    0 => 'Erna <b><i>Klawuppke</i></b>'
                ]
            ]
        ]
        ...
    ]
    'facets' => [
        'prodyear' => [
            0 => [
                'name' => '2018'
                'count' => 1
            ]
        ]
        ...
    ]
    'spellcheck' => [
        'suggestions' => []
        'correctlySpelled' => true
        'collations' => []
    ]
]
````

You can get the raw Solr response by setting a second parameter to the search method: `search(model, false)`

Example: 
````
[
    'responseHeader' => [
        'status' => 0
        'QTime' => 13
        'params' => [
            'mm' => '1<-2 6<70%'
            ... all params echoed here
        ]
    ]
    'response' => [
        'numFound' => 2
        'start' => 0
        'maxScore' => 1.5163089
        'docs' => [
            0 => [
                id' => 1504
                'title' => 'Erna Klawuppke'
                'type' => 'person'       
                'url' => 'http://filmdb.local/erna-klawuppke'
                'imagefile' => 'erna-klawuppke.jpg'        
                'score' => 1.5163089
               
            ]
           ...
        ]
    ]
    'facet_counts' => [
        'facet_queries' => []
        'facet_fields' => [
            'prodyear' => [
                0 => '1993'
                1 => 0
               ...
                50 => '2018'
                51 => 1
                52 => '2019'
                53 => 0
            ]
            ...
        ]
        'facet_dates' => []
        'facet_ranges' => []
        'facet_intervals' => []
        'facet_heatmaps' => []
    ]
    'highlighting' => [
        1504 => [
            'title' => [
                0 => 'Erna <b><i>Klawuppke</i></b>'
            ]
        ]
       
    ]
    'spellcheck' => [
        'suggestions' => []
        'correctlySpelled' => true
        'collations' => []
    ]
]
````

### Create the Solr doc for an element

The plugin uses an event to retrieve the data to be indexed. The event returns 
an array that can be passed to the Solr `add` command. It has to match the fields specified
 in Solr's `schema.xml` and may look like
 
 ````
$doc = [
    'id' => $entry->id,
    'title' => $entry->title,
    'content' => $this->_getContent($entry),
    'type' => $entry->section->handle,
    'url' => $entry->url,
];
````

The doc must not contain a field called `key`. This is used internally as a unique key.

Example: 

````
use wsydney76\solrsearch\events\GetSolrDocForElementEvent;
use wsydney76\solrsearch\services\SearchService;


Event::on(
    SearchService::class,
    SearchService::EVENT_GET_SOLR_DOC_FOR_ENTRY, function(GetSolrDocForElementEvent $event) {

    
    $element = $event->element;

    if (... do not index this entry)) {
        $event->cancel = true;
        return;
    }

    // Return the Solr doc, using a method that fits best for your project
    $element->attachBehavior('solrBehavior', SolrSearchEntryBehavior::class);
    $event->doc = $element->getSolrDoc();
    if (!$event->doc) {
        $event->cancel = true;
    }
}
);
````

The entry passed by the event may or may not have eager loaded related elements (see below);

### Events handled by the plugin

The Plugins listens to the following events for Entry/Product element types:

* `EVENT_AFTER_SAVE` (adds / deletes the solr record depending on an entries status)
* `EVENT_AFTER_DELETE`
* `EVENT_AFTER_RESTORE` (Entry only)


### Specify the elements to be used for 'Update All'

The plugin uses an event to let the project specify the elements to be indexed when 'Update all' is called
either via the Solr Search Utility in the Control Panel or via the CLI command.

````
use wsydney76\solrsearch\events\GetAllElementsForSolrSearchEvent;
use wsydney76\solrsearch\services\SearchService;

...

if (Craft::$app->plugins->isPluginEnabled('solrsearch')) {
    Event::on(
        SearchService::class,
        SearchService::EVENT_GET_ALL_ENTRIES_FOR_SOLR_SEARCH, function(GetAllElementsForSolrSearchEvent $event) {
        // Get entries, eg. via a service
        $event->elements = $this->content->getEntriesForSolrSearch();
    }
    );
}
````

Eager loading related elements and matrix field should be used for better performance, the getDoc event has to be aware that
related elements are eager loaded or not (when called if a single entry is saved.)

## Multi Site

The plugin has no specific functionality for handling multi site content besides setting a unique `key` value.

The events will always be fired for all sites.

So if you save an entry that belongs to three sites, three events will be fired, referencing the entry for that 
specific site.

What you can do in your project:

* Define a Solr field for the site handle
* Set the field value when returning the Solr Doc for entry
* Add a filter query parameter to your search, e.g. `fq:'site:en'`

## CLI commands    

````
./craft solrsearch/search/delete-all
./craft solrsearch/search/update-all
````

## Raw Solr Commands

Raw update commands can be executed via the `SolrService::command`  method.

The signature is 

`command($cmd, $async = false, $commit = true, $description = 'Execution Solr Command')`

where

* $cmd is the command to execute, either as string or array that can be converted to a valid
Solr json command
* $async = true pushes a background job
* $commit = false does not perform a commit. You can call `SolrService::commit` when finished.
* $description = Description for the background job, that will be displayd in queue manager.

Returns null if $async = true, else returns http response as instance of `GuzzleHttp\Psr7\Response`.
You can extract the Solr response with `\craft\helpers\Json::decodeIfJson($returnValue->getBody()->getContents())`

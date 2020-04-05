# SolrSearch Plugin

## How to set up your project 

### Prerequisites

* Solr is installed
* A Solr core is created and configured for your project, especially `schema.xml`

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

Example:

````
<?php

namespace modules\main\models;

class SearchParamsModel extends \wsydney76\solrsearch\models\SearchParamsModel
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
{% set searchParamsModel = create({class:'modules\\main\\models\\SearchParamsModel', q:q}) %}
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

### Create a behavior for updating an entries record 

The plugin retrieves the data to be indexed by calling a `getSolrDoc()` method on an entry.
This means there has to be a behavior class attached that implements this method and returns 
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

### Specify the entries to be used for 'Update All'

The plugin uses an event to let the project specify the entries to be indexed when 'Update all' is called
either via the Solr Search Utility in the Control Panel or via the CLI command.

````
use wsydney76\solrsearch\events\GetAllEntriesForSolrSearchEvent;
use wsydney76\solrsearch\services\SearchService;

...

if (Craft::$app->plugins->isPluginEnabled('solrsearch')) {
    Event::on(
        SearchService::class,
        SearchService::EVENT_GET_ALL_ENTRIES_FOR_SOLR_SEARCH, function(GetAllEntriesForSolrSearchEvent $event) {
        // Get entries, eg. via a service
        $event->entries = $this->content->getEntriesForSolrSearch();
    }
    );
}
````

Eager loading related elements and matrix field should be used for better performance, the behavior method has to be aware that
related elements are eager loaded or not (when called if a single entry is saved.)

## CLI commands    

````
./craft solrsearch/search/delete-all
./craft solrsearch/search/update-all
````

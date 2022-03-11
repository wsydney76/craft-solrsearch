<?php

namespace wsydney76\solrsearch\models;

use craft\base\Model;

class SearchParamsModel extends Model
{
    public $debugQuery = 'false';
    public $facet = 'off';
    public $facetField = [];
    public $facetLimit = 500;
    public $facetSort = 'index';
    public $fl = 'score,id';
    public $fq = '';
    public $hl = 'off';
    public $hlFl = '';
    public $hlSimplePost = '</i></b>';
    public $hlSimplePre = '<b><i>';
    public $mm = '';
    public $pf = '';
    public $q = '';
    public $qf = '';
    public $qt = 'edismax';
    public $rows = 999;
    public $sort = 'score desc,id';
    public $spellcheck = 'false';
    public $spellcheckBuild = 'false';
    public $spellcheckCollate = 'false';
    public $spellcheckExtendedResults = 'false';
    public $version = '2.2';
    public $wt = 'json';

    public function getParams()
    {
        return [
            'debugQuery' => $this->debugQuery,
            //'f.titel.qf' => $this->fTitleQf,
            // 'f.wer.qf' => $this->fWerQf,
            'facet' => $this->facet,
            'facet.field' => $this->facetField,
            'facet.limit' => $this->facetLimit,
            'facet.sort' => $this->facetSort,
            'fl' => $this->fl,
            'fq' => $this->fq,
            'hl' => $this->hl,
            'hl.fl' => $this->hlFl,
            'hl.simple.post' => $this->hlSimplePost,
            'hl.simple.pre' => $this->hlSimplePre,
            'mm' => $this->mm,
            'pf' => $this->pf,
            'q' => $this->q,
            'qf' => $this->qf,
            'qt' => $this->qt,
            'rows' => $this->rows,
            'sort' => $this->sort,
            'spellcheck' => $this->spellcheck,
            'spellcheck.build' => $this->spellcheckBuild,
            'spellcheck.collate' => $this->spellcheckCollate,
            'spellcheck.extendedResults' => $this->spellcheckExtendedResults,
            'version' => $this->version,
            'wt' => $this->wt
        ];
    }

}

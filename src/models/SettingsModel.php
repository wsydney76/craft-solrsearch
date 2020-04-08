<?php

namespace wsydney76\solrsearch\models;

use craft\base\Model;

class SettingsModel extends Model
{
    public $solrBaseUrl;
    public $solrCore;

    public function rules()
    {
        $rules = parent::rules();
        $rules[] = ['solrBaseUrl', 'required'];
        $rules[] = ['solrCore', 'required'];

        return $rules;
    }
}

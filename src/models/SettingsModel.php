<?php

namespace wsydney76\solrsearch\models;

use Craft;
use craft\base\Model;
use yii\base\Exception;

class SettingsModel extends Model
{
    public $solrBaseUrl = '@SOLR_BASE_URL';
    public $solrCore = '@SOLR_CORE';

    public function rules(): array
    {
        $rules = parent::rules();
        $rules[] = ['solrBaseUrl', 'required'];
        $rules[] = ['solrCore', 'required'];

        return $rules;
    }

    /**
     * @return bool|string|null
     * @throws Exception
     */
    public function getBaseUrl()
    {
        if (!$this->solrBaseUrl) {
            throw new Exception('Solr Base Url setting missing');
        }
        return Craft::parseEnv($this->solrBaseUrl);
    }

    /**
     * @return bool|string|null
     * @throws Exception
     */
    public function getCore()
    {
        if (!$this->solrBaseUrl) {
            throw new Exception('Solr Core setting missing');
        }
        return Craft::parseEnv($this->solrCore);
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getCoreUrl()
    {
        return $this->getBaseUrl() . '/' . $this->getCore();
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getQueryUrl()
    {
        return $this->getCoreUrl() . '/select';
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getUpdateUrl()
    {
        return $this->getCoreUrl() . '/update';
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getReloadUrl()
    {
        return $this->getBaseUrl() . '/admin/cores?action=RELOAD&core=' . $this->getCore();
    }

    public function getSchemaUrl()
    {
        return "{$this->getBaseUrl()}/#/{$this->getCore()}/files?file=schema.xml";
    }

}

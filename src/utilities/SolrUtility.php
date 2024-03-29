<?php

namespace wsydney76\solrsearch\utilities;

use Craft;
use craft\base\Utility;
use wsydney76\solrsearch\SolrSearch;

class SolrUtility extends Utility
{
    public static function id(): string
    {
        return 'solr';
    }

    public static function displayName(): string
    {
        return 'Solr Search';
    }

    /**
     * @return string
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     * @throws \yii\base\Exception
     */
    public static function contentHtml(): string
    {
        $settings = SolrSearch::getInstance()->getSettings();
        return Craft::$app->view->renderTemplate('solrsearch/solr_utility.twig', ['settings' => $settings]);
    }

    public static function iconPath(): ?string
    {
        return Craft::parseEnv('@solrsearch/icon-mask.svg');
    }
}

<?php
/**
 * search module for Craft CMS 3.x
 *
 * Solr search
 *
 * @link      https://github.com/wsydney76
 * @copyright Copyright (c) 2020 wsydney76
 */

namespace wsydney76\solrsearch;

use Craft;
use craft\base\Element;
use craft\base\Plugin;
use craft\console\Application as ConsoleApplication;
use craft\elements\Entry;
use craft\commerce\elements\Product;
use craft\events\ModelEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterTemplateRootsEvent;
use craft\i18n\PhpMessageSource;
use craft\services\Utilities;
use craft\web\twig\variables\CraftVariable;
use craft\web\View;
use wsydney76\solrsearch\models\SettingsModel;
use wsydney76\solrsearch\services\SearchService as SearchService;
use wsydney76\solrsearch\services\SolrService as SolrService;
use wsydney76\solrsearch\utilities\SolrUtility;
use wsydney76\solrsearch\variables\SearchModuleVariable;
use yii\base\Event;
use yii\base\Module;

/**
 * Class SearchModule
 *
 * @author    wsydney76
 * @package   SearchModule
 * @since     1.0.0
 *
 * @property  SearchService $search
 * @property  SolrService $solr
 */
class SolrSearch extends Plugin
{

    const LOG_CATEGORY = 'solrsearch';
    // Static Properties
    // =========================================================================

    /**
     * @var SolrSearch
     */
    public static $services;

    public $hasCpSettings = true;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function __construct($id, $parent = null, array $config = [])
    {
        Craft::setAlias('@solrsearch', $this->getBasePath());
        $this->controllerNamespace = 'wsydney76\solrsearch\controllers';

        // Translation category
        $i18n = Craft::$app->getI18n();
        /** @noinspection UnSafeIsSetOverArrayInspection */
        if (!isset($i18n->translations[$id]) && !isset($i18n->translations[$id . '*'])) {
            $i18n->translations[$id] = [
                'class' => PhpMessageSource::class,
                'sourceLanguage' => 'en-US',
                'basePath' => '@solrsearch/translations',
                'forceTranslation' => true,
                'allowOverrides' => true,
            ];
        }

        // Base template directory
        Event::on(View::class, View::EVENT_REGISTER_CP_TEMPLATE_ROOTS, function(RegisterTemplateRootsEvent $e) {
            if (is_dir($baseDir = $this->getBasePath() . DIRECTORY_SEPARATOR . 'templates')) {
                $e->roots['solrsearch'] = $baseDir;
            }
        });

        // Set this as the global instance of this module class
        static::setInstance($this);

        Event::on(
            Element::class,
            Element::EVENT_AFTER_SAVE, function(ModelEvent $event) {
            /** @var Entry $entry */
            $entry = $event->sender;
            $this->search->updateElement($entry);
        }
        );

        Event::on(
            Entry::class,
            Entry::EVENT_AFTER_RESTORE, function(Event $event) {
            /** @var Entry $entry */
            $entry = $event->sender;
            $this->search->updateElement($entry);
        }
        );

        Event::on(
            Element::class,
            Element::EVENT_AFTER_DELETE, function(Event $event) {
            /** @var Entry $entry */
            $entry = $event->sender;
            $this->search->deleteElement($entry);
        }
        );



        Event::on(
            Utilities::class,
            Utilities::EVENT_REGISTER_UTILITY_TYPES, function(RegisterComponentTypesEvent $event) {
            $event->types[] = SolrUtility::class;
        }
        );

        parent::__construct($id, $parent, $config);
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        self::$services = $this;

        $this->setComponents([
            'search' => SearchService::class,
            'solr' => SolrService::class
        ]);

        if (Craft::$app instanceof ConsoleApplication) {
            $this->controllerNamespace = 'wsydney76\solrsearch\console\controllers';
        }

        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function(Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('solrsearch', SearchModuleVariable::class);
            }
        );

        Craft::info(
            Craft::t(
                'solrsearch',
                '{name} module loaded',
                ['name' => 'search']
            ),
            __METHOD__
        );
    }

    protected function createSettingsModel()
    {
        return new SettingsModel();
    }

    protected function settingsHtml()
    {
        return Craft::$app->getView()->renderTemplate('solrsearch/settings', [
            'settings' => $this->getSettings(),
            'config' => Craft::$app->getConfig()->getConfigFromFile('solrsearch'),
        ]);
    }


    // Protected Methods
    // =========================================================================
}

<?php
/**
 * search module for Craft CMS 3.x
 *
 * Solr search
 *
 * @link      https://github.com/wsydney76
 * @copyright Copyright (c) 2020 wsydney76
 */

namespace wsydney76\solrsearch\controllers;

use Craft;
use craft\elements\Entry;
use craft\web\Controller;
use Exception;
use wsydney76\solrsearch\SolrSearch;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;

/**
 * @author    wsydney76
 * @package   SearchModule
 * @since     1.0.0
 */
class SearchController extends Controller
{

    // Protected Properties
    // =========================================================================

    /**
     * @var    bool|array Allows anonymous access to this controller's actions.
     *         The actions must be in 'kebab-case'
     * @access protected
     */
    // protected $allowAnonymous = ['index', 'redirect'];

    // Public Methods
    // =========================================================================


    /**
     * @return \yii\web\Response
     * @throws BadRequestHttpException
     * @throws \yii\web\ForbiddenHttpException
     */
    public function actionDeleteAll()
    {
        $this->requireAdmin();

        try {
            SolrSearch::$services->search->deleteAll();
        } catch (Exception $e) {
            Craft::$app->session->setError('Error connecting to Search Service: ' . $e->getMessage());
            return $this->redirectToPostedUrl();
        }
        Craft::$app->session->setNotice('All Index Records Deleted');
        return $this->redirectToPostedUrl();
    }

    /**
     * @return \yii\web\Response
     * @throws BadRequestHttpException
     * @throws \yii\web\ForbiddenHttpException
     */
    public function actionUpdateAll()
    {
        $this->requireAdmin();

        SolrSearch::$services->search->updateAll();
        Craft::$app->session->setNotice('Batch job started');
        return $this->redirectToPostedUrl();
    }
}

<?php

namespace app\base;

use yii\base\Application;
use yii\base\BootstrapInterface;

class RestApiBootstrap implements BootstrapInterface
{

    public function bootstrap($app)
    {

        $urlManager = $app->urlManager;
        if($urlManager instanceof RestUrlManager)
        {
            //$urlManager->registerRulesFromController($urlManager->controllerRules);
        }
        // TODO: Implement bootstrap() method.
    }
}
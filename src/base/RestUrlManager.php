<?php

namespace app\base;

use app\attributes\Route;
use app\base\model\ActionRoutes;
use app\controllers\BaseController;
use Yii;
use yii\base\InvalidConfigException;
use yii\web\UrlManager;

class RestUrlManager extends UrlManager
{

    public $controllerRules = [];

    public function registerRulesFromController(array $controllers)
    {
        foreach ($controllers as $controller)
        {
            $reflectionController = new \ReflectionClass($controller);

            $actionRoute = new ActionRoutes(['autoLoad' => true]);
            $actions = $actionRoute->getActions($reflectionController);
            foreach ($reflectionController->getMethods() as $method)
            {

                var_dump($actionRoute->getRouteName($controller,$method->name));die;
                $attributes = $method->getAttributes(Route::class);
                foreach ($attributes as $attribute)
                {
                    $route = $attribute->newInstance();
//                    $this->addRules([
//                       'pattern' => $route->route
//                    ]);
                }
            }


        }
    }
}
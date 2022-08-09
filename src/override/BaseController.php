<?php

namespace app\controllers;
use yii\base\Action;
use yii\base\InlineAction;
use yii\base\InvalidRouteException;
use yii\base\Model;
use yii\helpers\Json;
use yii\rest\Controller;
use yii\web\Application;
use yii\web\BadRequestHttpException;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

class BaseController extends Controller
{
    /** @var array */
    private $errorContext = [];

    /**
     * @return array
     */
    public function behaviors()
    {
        return [
            [
                'class' => 'yii\filters\ContentNegotiator',
                'formats' => [
                    'application/json' => Response::FORMAT_JSON,
                ],
            ],
        ];
    }

    /**
     * Binds the parameters to the action.
     * This method is invoked by [[\yii\base\Action]] when it begins to run with the given parameters.
     * This method will check the parameter names that the action requires and return
     * the provided parameters according to the requirement. If there is any missing parameter,
     * an exception will be thrown.
     * @param \yii\base\Action $action the action to be bound with parameters
     * @param array $params the parameters to be bound to the action
     * @return array the valid parameters that the action can run with.
     * @throws BadRequestHttpException if there are missing or invalid parameters.
     */
    public function bindActionParams($action, $params)
    {

        if ($action instanceof InlineAction) {
            $reflectionMethod = new \ReflectionMethod($this, $action->actionMethod);
        } else {
            $reflectionMethod = new \ReflectionMethod($action, 'run');
        }

        $reflectionParameters = $reflectionMethod->getParameters();

        if (!empty($reflectionParameters)) {
            $reflectionParameter = $reflectionParameters[0];

            if (
                PHP_VERSION_ID >= 80000
                && ($type = $reflectionParameter->getType()) !== null
                && !$type->allowsNull()
            )
            {


                $typeName = PHP_VERSION_ID >= 70100 ? $type->getName() : (string)$type;
                // Check
                $requestClass = $typeName;
                if(class_exists($requestClass) &&
                    $typeName != 'int' &&
                    $typeName != 'float' &&
                    $typeName != 'bool'
                )
                {
                    // Allow only one Request Type Model param
                    if(count($reflectionParameters) > 1)
                    {
                        throw new ServerErrorHttpException('Only allow one Request Model param type');
                    }
                    /** @var Model $request */
                    $request = new $requestClass();
                    $data = Json::decode(\Yii::$app->getRequest()->getRawBody());
                    $request->load($data, '');
                    if (!$request->validate()) {
                        $this->errorContext = $request->getErrors();
                        throw new BadRequestHttpException('Bad request');
                    }
                    $params = [$reflectionParameter->getName() => $request];
                }
            }
        }

        return parent::bindActionParams($action, $params);
    }

    /**
     * @param string $id
     * @param array $params
     * @return array|mixed
     */
    public function runAction($id, $params = [])
    {
        try {
            return $this->prepareAction($id, $params);
        } catch (HttpException $e) {
            \Yii::$app->response->setStatusCode($e->statusCode);
            \Yii::$app->response->statusText = $e->getMessage();
            $this->errorJson($this->errorContext);
        } catch (\Throwable $e) {
            \Yii::$app->response->setStatusCode(500);
            $this->errorContext[] = $e->getMessage();
            $this->errorJson($this->errorContext);
        }
    }

    /**
     * @param $id
     * @param $params
     * @return mixed
     * @throws NotFoundHttpException
     */
    private function prepareAction($id, $params)
    {
        try {
            return parent::runAction($id, $params);
        } catch (InvalidRouteException $e) {
            throw new NotFoundHttpException($e->getMessage());
        }
    }

    /**
     * @param array $data
     * @return array
     */
    protected function success(mixed $data)
    {
        return $this->pack($data, 'OK');
    }

    /**
     * @param array $data
     * @return array
     */
    protected function error(mixed $data)
    {
        return $this->pack($data, 'FAIL');
    }

    /**
     * @param array $data
     * @throws \yii\base\ExitException
     */
    protected function errorJson(array $data)
    {
        echo json_encode(
            $this->error($data),
            JSON_UNESCAPED_UNICODE
        );
        \Yii::$app->state = Application::STATE_END;
        \Yii::$app->end();
    }

    /**
     * @param $data
     * @param $status
     * @return array
     */
    protected function pack($data, $status) {
        return [
            'request_id' =>  \Yii::$app->request->getHeaders()->get('X-Request-Id'),
            'code' => \Yii::$app->response->statusCode,
            'message' => \Yii::$app->response->statusText,
            'status' => $status,
            'data' => $data
        ];
    }

}
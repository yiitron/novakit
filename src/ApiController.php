<?php

namespace yiitron\novakit;

use Yii;
use yii\filters\Cors;
use yii\filters\auth\HttpBearerAuth;
use yiitron\novakit\behaviors\TenantBehavior;

class ApiController extends \yii\rest\Controller
{
    public $enableCsrfValidation = false;

    public function behaviors()
    {
        $behavior = [
            'class' => Cors::class,
            'cors'  => [
                'Origin'                           => Yii::$app->params['allowedDomains'],
                'Access-Control-Allow-Origin'      => Yii::$app->params['allowedDomains'],
                'Access-Control-Request-Headers'   => ['*'],
                'Access-Control-Request-Method'    => ['POST', 'PUT', 'PATCH', 'GET', 'DELETE', 'HEAD'],
                'Access-Control-Allow-Credentials' => false,
                'Access-Control-Max-Age'           => 3600,
            ],
        ];
        $behaviors = parent::behaviors();
        unset($behaviors['authenticator']);
        $behaviors['corsFilter'] = $behavior;
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
        ];
        $behaviors['authenticator']['except'] = Yii::$app->params['safeEndpoints'];
        $behaviors['tenant'] = [
            'class' => TenantBehavior::class,
        ];

        return $behaviors;
    }
    /**
     * error response
     */
    public function errorResponse($errors, $acidErrors = false, $message = false)
    {
        if (!is_array($errors)) {
            Yii::$app->response->statusCode = $errors;
            return $this->errors($errors);
        }
        Yii::$app->response->statusCode = 422;
        foreach ($errors as $key => $value) {
            $errors[$key] = $value[0];
        }
        if (is_array($acidErrors)) {
            foreach ($acidErrors['acidErrorModel'] as $key => $value) {
                foreach ($value->getErrors() as $k => $value) {
                    $error[$acidErrors['errorKey']][$key][$k] = $value[0];
                }
            }
        }
        if (isset($error)) {
            $errors = array_merge($errors, $error);
        }

        $array['errorPayload']['errors'] = $errors;

        if ($message) {
            $array['errorPayload'] = array_merge($array['errorPayload']['errors'], $this->alertifyResponse(
                [
                    'statusCode' => 422,
                    'message' => $message ? $message : 'Some data could not be validated',
                    'theme' => 'danger'
                ]
            )['alertifyPayload']);
        }
        return $array;
    }
    /**
     * payload response
     */
    public function payloadResponse($data, $options = [])
    {
        $options = array_merge(['statusCode' => 200, 'oneRecord' => true, 'message' => false], $options);
        Yii::$app->response->statusCode = $options['statusCode'];
        if (!$options['oneRecord']) {
            $array = [
                'dataPayload' => [
                    'data'              => !empty($data->models) ? $data->models : 'No records available',
                    'countOnPage'       => $data->count,
                    'totalCount'        => $data->totalCount,
                    'perPage'           => $data->pagination->pageSize,
                    'totalPages'        => $data->pagination->pageCount,
                    'currentPage'       => $data->pagination->page + 1,
                    'paginationLinks'   => $data->pagination->links,
                ]
            ];
        } else {
            $array = [
                'dataPayload' => [
                    'data'              => $data,
                ]
            ];
            if ($options['message']) {
                $alertifyArray = [
                    'statusCode' => $options['statusCode'],
                    'message' => $options['message'],
                    'theme' => 'success',
                ];
                if (isset($options['alertifyOptions'])) {
                    $alertifyArray['alertifyOptions'] = $options['alertifyOptions'];
                }


                $array = array_merge($array, $this->alertifyResponse($alertifyArray));
            }
            //$array['dataPayload'] = $model;
        }
        return $array;
    }
    /**
     * Alert response
     */
    public function alertifyResponse($options = [])
    {
        $options = array_merge(['statusCode' => 200, 'theme' => false, 'message' => false], $options);
        Yii::$app->response->statusCode = $options['statusCode'];
        $array = [
            'alertifyPayload' => [
                'message'  => $options['message'] ? $options['message'] : 'Hello alert',
                'theme'    => $options['theme'] ? $options['theme'] : 'info',
                'type'    => $options['type'] ? $options['type'] : 'alert',
            ]
        ];
        return $array;
    }
    /**
     * Query parameters cleanup
     */
    public function queryParameters($query, $modelId)
    {
        if (!$query) {
            $data = null;
        }
        foreach ($query as $key => $value) {
            if (substr($key, 0, 1) == '_') {
                $data[$modelId][ltrim($key, "_")] = $value;
            } else {
                $data[$key] = $value;
            }
        }
        return $data;
    }
    public function debugCode($data = null, $tipe = false, $die = true)
    {
        echo '<pre>';
        $tipe ? var_dump($data) : print_r($data);
        echo '</pre>';
        $die ? die() : '';
    }
    public function baseUrl()
    {
        return \yii\helpers\Url::base(true);
    }
    public function xMlToJson($data)
    {
        $fileContents = ($data);
        $fileContents = str_replace(array("\n", "\r", "\t"), '', $fileContents);
        $fileContents = trim(str_replace('"', "'", $fileContents));
        $simpleXml    = simplexml_load_string($fileContents);
        return $simpleXml;
    }
    protected function errors($code)
    {
        (string) $code;
        $codes = [
            '440' => ['statusCode' => 440, 'message' => 'Session has expired!'],
            '500' => ['statusCode' => 500, 'message' => 'Internal Server Error'],
        ];
        if (array_key_exists($code, $codes)) {
            return $payload['errorPayload']['errors'] = $codes[$code];
        }
        return $payload['errorPayload']['errors'] = $codes['500'];
    }
}

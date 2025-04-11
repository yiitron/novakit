<?php

namespace yiitron\novakit;

use Yii;
use yii\filters\Cors;
use yii\filters\auth\HttpBearerAuth;
use yii\rest\Controller;

class ApiController extends Controller
{
    public $enableCsrfValidation = false;

    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        unset($behaviors['authenticator']);

        $behaviors['corsFilter'] = [
            'class' => Cors::class,
            'cors'  => [
                'Origin'                           => Yii::$app->params['allowedDomains'],
                'Access-Control-Allow-Origin'      => Yii::$app->params['allowedDomains'],
                'Access-Control-Request-Headers'   => ['*'],
                'Access-Control-Request-Method'    => ['POST', 'PUT', 'PATCH', 'GET', 'DELETE', 'HEAD'],
                'Access-Control-Allow-Credentials' => true,
                'Access-Control-Allow-Headers'     => ['Content-Type', 'Authorization'],
                'Access-Control-Max-Age'           => 3600,
            ],
        ];

        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
            'except' => Yii::$app->params['safeEndpoints'],
        ];
        return $behaviors;
    }

    public function errorResponse($errors, $acidErrors = false, $message = false): array
    {
        if (!is_array($errors)) {
            Yii::$app->response->statusCode = $errors;
            return $this->getErrorPayload($errors);
        }

        Yii::$app->response->statusCode = 422;
        $errors = array_map(fn($value) => $value[0], $errors);

        if (is_array($acidErrors)) {
            foreach ($acidErrors['acidErrorModel'] as $key => $value) {
                foreach ($value->getErrors() as $k => $error) {
                    $errors[$acidErrors['errorKey']][$key][$k] = $error[0];
                }
            }
        }

        $errorPayload = ['errors' => $errors];

        if ($message) {
            $errorPayload = array_merge($errorPayload, $this->alertifyResponse([
                'statusCode' => 422,
                'message' => $message ?: 'Some data could not be validated',
                'theme' => 'danger'
            ])['alertifyPayload']);
        }

        return ['errorPayload' => $errorPayload];
    }

    public function payloadResponse($data, array $options = []): array
    {
        $options = array_merge(['statusCode' => 200, 'oneRecord' => true, 'message' => false], $options);
        Yii::$app->response->statusCode = $options['statusCode'];

        if (!$options['oneRecord']) {
            return [
                'dataPayload' => [
                    'data' => $data->models ?: 'No records available',
                    'countOnPage' => $data->count,
                    'totalCount' => $data->totalCount,
                    'perPage' => $data->pagination->pageSize,
                    'totalPages' => $data->pagination->pageCount,
                    'currentPage' => $data->pagination->page + 1,
                    'paginationLinks' => $data->pagination->links,
                ]
            ];
        }

        $response = ['dataPayload' => ['data' => $data]];

        if ($options['message']) {
            $alertifyOptions = [
                'statusCode' => $options['statusCode'],
                'message' => $options['message'],
                'type' => isset($options['type']) ? $options['type'] : 'alert',
                'theme' => 'success',
            ];

            /* if (isset($options['alertifyOptions'])) {
                $alertifyOptions['alertifyOptions'] = $options['alertifyOptions'];
            } */

            $response = array_merge($response, $this->alertifyResponse($alertifyOptions));
        }

        return $response;
    }

    public function alertifyResponse(array $options = [])
    {
        $options = array_merge(['statusCode' => 200, 'theme' => 'info', 'type' => 'alert'], $options);
        Yii::$app->response->statusCode = $options['statusCode'];
        if (is_array($options['type'])) {
            $alert = [
                'alertifyPayload' => [
                    'type' => $options['type'],
                ]
            ];
            if (isset($options['message'])) {
                $alert['alertifyPayload']['type']['message'] = $options['message'];
            }
            return $alert;
        }
        return [
            'alertifyPayload' => [
                'message' => $options['message'],
                'theme' => $options['theme'],
                'type' => $options['type'],
            ]
        ];
    }

    public function queryParameters(array $query, string $modelId): ?array
    {
        $data = null;

        foreach ($query as $key => $value) {
            if (substr($key, 0, 1) === '_') {
                $data[$modelId][ltrim($key, "_")] = $value;
            } else {
                $data[$key] = $value;
            }
        }

        return $data;
    }

    public function debugCode($data = null, bool $type = false, bool $die = true): void
    {
        echo '<pre>';
        $type ? var_dump($data) : print_r($data);
        echo '</pre>';
        if ($die) {
            die();
        }
    }

    public function baseUrl(): string
    {
        return \yii\helpers\Url::base(true);
    }

    public function xmlToJson(string $data): \SimpleXMLElement
    {
        $fileContents = str_replace(["\n", "\r", "\t"], '', trim(str_replace('"', "'", $data)));
        return simplexml_load_string($fileContents);
    }

    protected function getErrorPayload(string $code): array
    {
        $codes = [
            '440' => ['statusCode' => 440, 'message' => 'Session has expired!'],
            '500' => ['statusCode' => 500, 'message' => 'Internal Server Error'],
        ];

        return ['errors' => $codes[$code] ?? $codes['500']];
    }
}

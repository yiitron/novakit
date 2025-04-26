<?php

namespace yiitron\novakit\traits;

use Yii;
use yii\helpers\Url;

trait ServiceConsumer
{
    /**
     * Example Request:
     * 
     * This function can be used to send a request to an external service.
     * Below is an example of how to use the `sendRequest` method:
     * 
     * ```php
     * $response = $this->sendRequest([
     *     'method' => 'POST',
     *     'url' => '/api/example-endpoint',
     *     'data' => [
     *         'key1' => 'value1',
     *         'key2' => 'value2',
     *     ],
     *     'options' => [
     *         'timeout' => 60,
     *     ],
     * ]);
     * 
     * Ensure that the request includes the necessary headers and payload
     * as required by the API specification.
     */
    public function sendRequest($request = [])
    {
        $client = new \yii\httpclient\Client([
            'baseUrl' => rtrim($this->getBaseUrl(), '/'),
            'transport' => 'yii\httpclient\CurlTransport',
        ]);

        $method = strtolower($request['method'] ?? 'GET');
        $url = '/' . ltrim($request['url'] ?? '/', '/'); // Ensure the URL starts with a single slash
        $data = $request['data'] ?? null;
        $options = $this->mergeOptions($request['options'] ?? []);

        $response = $client->$method($url, $data, $this->getHeaders(), $options)->send();

        return $response->isOk ? $response->data['dataPayload']['data'] : null;
    }

    private function getBaseUrl(): string
    {
        return $_SERVER['APP_BASE_URL'] ?? Url::base(true);
    }

    private function getHeaders(): array
    {
        $authorization = Yii::$app->request->headers->get('authorization', '');
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => $authorization,
        ];
    }

    private function mergeOptions(array $options): array
    {
        return array_merge(['timeout' => 30, 'maxRedirects' => 0], $options);
    }
}

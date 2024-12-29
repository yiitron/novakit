<?php

namespace yiitron\novakit;

use Yii;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use yii\helpers\Url;

trait ServiceConsumer
{
    public function client()
    {
        return new Client(['base_uri' => Url::base(true)]);
    }
    private static function options($data, $query)
    {
        return [
            'json' => $data,
            'query' => $query,
            'headers' => [
                'Accept' => 'application/json',
                'Content-type' => 'application/json',
                'authorization' => Yii::$app->request->headers['authorization']
            ]
        ];
    }
    public function StatusCodeHandling($e)
    {
        return json_decode($e->getResponse()->getBody(true)->getContents());
    }
    public function sendRequest($method, $request = [])
    {
        if (isset($request['url'])) {
            $request_url = (substr($request['url'], 0, 1) != '/') ? '/' . $request['url'] : $request['url'];
        } else {
            $request_url = '/';
        }
        $data = (isset($request['data'])) ? $request['data'] : [];
        $query = (isset($request['query'])) ? $request['query'] : [];
        try {
            $method = strtolower($method);
            $response = $this->client()->$method($request_url, $this->options($data, $query));
            $result = $response->getBody()->getContents();
            return json_decode($result);
        } catch (RequestException $e) {
            $response = $this->StatusCodeHandling($e);
            return $response;
        }
    }
}

<?php
namespace yiitron\novakit;

use Yii;
use yii\web\Response;
use yii\base\Exception;
use yii\web\HttpException;
use yii\base\UserException;

class ErrorHandler extends \yii\base\ErrorHandler
{  
    /**
     * Renders the exception.
     * @param \Exception|\Error $exception the exception to be rendered.
     */
    protected function renderException($exception)
    {
        if (Yii::$app->has('response')) {
            $response = Yii::$app->getResponse();
            // reset parameters of response to avoid interference with partially created response data
            // in case the error occurred while sending the response.
            $response->isSent = false;
            $response->stream = null;
            $response->data = null;
            $response->content = null;
        } else {
            $response = new Response();
        }
        $response->data = $this->convertExceptionToArray($exception);
        $response->send();
    }

    /**
     * Converts an exception into an array.
     * @param \Exception|\Error $exception the exception being converted
     * @return array the array representation of the exception.
     */
    protected function convertExceptionToArray($exception)
    {
        if (!YII_DEBUG && !$exception instanceof UserException && !$exception instanceof HttpException) {
            $exception = new HttpException(500, Yii::t('yii', 'An internal server error occurred.'));
        }

        $array = [
            'statusCode'=> $exception->statusCode ? $exception->statusCode : 500,
            'errorMessage' => $exception->getMessage(),
            //'code' => $exception->getCode(),
        ];
        if (($prev = $exception->getPrevious()) !== null) {
            $array['previous'] = $this->convertExceptionToArray($prev);
        }

        Yii::$app->response->statusCode = $array['statusCode'];
        return ['errorPayload'=>$array];
    }

    
}

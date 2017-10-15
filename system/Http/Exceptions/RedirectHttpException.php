<?php

namespace System\Http\Exceptions;

use System\Http\Exceptions\HttpException;


class RedirectHttpException extends HttpException
{
    protected $url;

    public function __construct($url, $statusCode, $message = null, Exception $previous = null, $code = 0)
    {
        $this->url = $url;

        parent::__construct($statusCode, $message, $previous, $code);
    }

    public function getUrl()
    {
        return $this->url;
    }
}

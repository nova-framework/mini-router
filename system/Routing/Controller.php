<?php

namespace System\Routing;


class Controller
{

    public function before()
    {
        //
    }

    public function after($response)
    {
        return $response;
    }

    public function callAction($method, array $parameters)
    {
        if (! is_null($response = $this->before())) {
            return $response;
        }

        $response = call_user_func_array(array($this, $method), $parameters);

        return $this->after($response);
    }
}

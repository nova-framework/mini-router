<?php

namespace App\Exceptions;

use System\Foundation\Exceptions\Handler as BaseHandler;
use System\Http\Exceptions\HttpException;
use System\View\View;

use Exception;


class Handler extends BaseHandler
{

    public function report(Exception $e)
    {
        $message = $e->getMessage();

        $code = $e->getCode();
        $file = $e->getFile();
        $line = $e->getLine();

        $trace = $e->getTraceAsString();

        $date = date('M d, Y G:iA');

        $message = "Exception information:\n
    Date: {$date}\n
    Message: {$message}\n
    Code: {$code}\n
    File: {$file}\n
    Line: {$line}\n
    Stack trace:\n
{$trace}\n
---------\n\n";

        //
        $path = STORAGE_PATH .'logs' .DS .'errors.log';

        file_put_contents($path, $message, FILE_APPEND);
    }

    /**
     * Render an exception as an HTTP response and send it.
     *
     * @param  \Exception  $e
     * @return void
     */
    public function render(Exception $e)
    {
        // Http Error Pages.
        if ($e instanceof HttpException) {
            $code = $e->getStatusCode();

            $view = View::make('Layouts/Default')
                ->shares('title', 'Error ' .$code)
                ->nest('content', 'Errors/' .$code, array('exception' => $e));

            echo $view->render();

            return;
        }

        parent::render($e);
    }
}

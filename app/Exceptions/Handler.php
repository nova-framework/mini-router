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
        if ($e instanceof HttpException) {
            $code = $e->getStatusCode();

            $view = View::make('Layouts/Default')
                ->shares('title', 'Error ' .$code)
                ->nest('content', 'Errors/' .$code, array('exception' => $e));

            echo $view->render();

            return;
        }

        if (! $this->debug) {
            $content = '<h2 class="text-center"><strong>An application error occurred.</strong></h2>';
        } else {
            $content = sprintf('<p>%s in %s on line %d</p><br><pre>%s</pre>',
                $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString()
            );
        }

        $view = View::make('Layouts/Default')
            ->shares('title', 'Whoops!')
            ->nest('content', 'Default', compact('content'));

        echo $view->render();
    }
}

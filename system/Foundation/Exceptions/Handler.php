<?php

namespace System\Foundation\Exceptions;

use System\Config\Config;
use System\Foundation\Exceptions\FatalThrowableError;
use System\Http\Exceptions\HttpException;
use System\View\View;

use ErrorException;
use Exception;
use Throwable;


class Handler
{
    /**
     * The current Handler instance.
     *
     * @var \System\Foundation\Exceptions\Handler
     */
    protected static $instance;

    /**
     * Whether or not we are in DEBUG mode.
     */
    protected $debug = false;


    /**
     * Create a new Exceptions Handler instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->debug = Config::get('app.debug', true);
    }

    /**
     * Bootstrap the Exceptions Handler.
     *
     * @return void
     */
    public static function initialize()
    {
        static::$instance = $instance = new static();

        // Setup the Exception Handlers.
        set_error_handler(array($instance, 'handleError'));

        set_exception_handler(array($instance, 'handleException'));

        register_shutdown_function(array($instance, 'handleShutdown'));
    }

    /**
     * Convert a PHP error to an ErrorException.
     *
     * @param  int  $level
     * @param  string  $message
     * @param  string  $file
     * @param  int  $line
     * @param  array  $context
     * @return void
     *
     * @throws \ErrorException
     */
    public function handleError($level, $message, $file = '', $line = 0, $context = array())
    {
        if (error_reporting() & $level) {
            throw new ErrorException($message, 0, $level, $file, $line);
        }
    }

    /**
     * Handle an uncaught exception from the application.
     *
     * @param  \Throwable  $e
     * @return void
     */
    public function handleException($e)
    {
        if (! $e instanceof Exception) {
            $e = new FatalThrowableError($e);
        }

        if (! $e instanceof HttpException) {
            $this->report($e);
        }

        $this->render($e);
    }

    /**
     * Report or log an exception.
     *
     * @param  \Exception  $e
     * @return void
     */
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
    protected function render($e)
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

    /**
     * Handle the PHP shutdown event.
     *
     * @return void
     */
    public function handleShutdown()
    {
        if (! is_null($error = error_get_last()) && $this->isFatal($error['type'])) {
            $this->handleException($this->fatalExceptionFromError($error));
        }
    }

    /**
     * Create a new fatal exception instance from an error array.
     *
     * @param  array  $error
     * @param  int|null  $traceOffset
     * @return \ErrorException
     */
    protected function fatalExceptionFromError(array $error)
    {
        return new ErrorException(
            $error['message'], $error['type'], 0, $error['file'], $error['line']
        );
    }

    /**
     * Determine if the error type is fatal.
     *
     * @param  int  $type
     * @return bool
     */
    protected function isFatal($type)
    {
        return in_array($type, array(E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE));
    }
}

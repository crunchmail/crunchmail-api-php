<?php
/**
 * Exception class for Crunchmail classes
 *
 * @license MIT
 * @copyright (C) 2015 Oasis Work
 * @author Yannick Huerre <dev@sheoak.fr>
 *
 * @link https://github.com/crunchmail/crunchmail-client-php
 */

namespace Crunchmail\Exception;

/**
 * ApiException class
 */
class ApiException extends \Exception
{
    /**
     * Custom ApiException constructor
     */
    public function __construct($message, $code = 0, $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Output exception
     */
    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }

    /**
     * Return the last error as an html string
     *
     * @param boolean $showErrorKey Show the key of each error
     * @return string
     */
    public function toHtml($showErrorKey=true)
    {
        $body = $this->getDetail();

        if (false === $body)
        {
            return $body;
        }

        return self::formatResponseOutput($body, $showErrorKey);
    }

    /**
     * Extract error details
     *
     * @return stdClass
     */
    public function getDetail()
    {
        // guzzle exception
        $previous = $this->getPrevious();

        // in case we have a response, we try to format it as a string
        if ($previous->hasResponse())
        {
            $Response = $previous->getResponse();
            $Body     = $Response->getBody();
            $msg      = json_decode($Response->getBody());
        }

        // if body was empty, we need to return the exception message instead
        if (!isset($msg) || count( (array) $msg ) === 0)
        {
            $msg = new \stdClass();
            $msg->error = [$previous->getMessage()];
        }

        return $msg;
    }

    /**
     * Format a body response as a unique HTML string
     * This is mainly a debugging function, you should probably generate your
     * own HTML output.
     *
     * @param object $body Guzzle Response
     * @param boolean $showErrorKey show error keys in output
     * @return string
     *
     * @todo add string sanitize
     */
    public static function formatResponseOutput($body, $showErrorKey=true)
    {
        // invalid error, it's a string, we handle the error
        if (is_string($body))
        {
            return $body;
        }

        // neither a string, nor a stdClass, we cannot handle the error
        if (!is_object($body) || get_class($body) !== 'stdClass')
        {
            throw new \RuntimeException('Invalid error format');
        }

        // build a string from the complex response
        $out = "";
        foreach ((array) $body as $k => $v)
        {
            // list of error fields with error messages
            $out .= '<p>';
            if ($showErrorKey)
            {
               $out .= $k . ' : ';
            }
            foreach ($v as $str)
            {
                $out .= htmlentities($str) . "<br>";
            }
            $out .= '</p>';
        }

        $out = empty($out) ? 'Unknow error' : $out;

        return $out;
    }
}

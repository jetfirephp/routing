<?php

namespace JetFire\Routing;


/**
 * Class Response
 * @package JetFire\Routing
 */
class Response implements ResponseInterface
{

    /**
     * @var array
     */
    public $headers;

    /**
     * @var string
     */
    protected $content;

    /**
     * @var string
     */
    protected $version;

    /**
     * @var int
     */
    protected $statusCode;

    /**
     * @var string
     */
    protected $statusText;

    /**
     * @var string
     */
    protected $charset;

    /**
     * Status codes translation table.
     *
     * The list of codes is complete according to the
     * {@link http://www.iana.org/assignments/http-status-codes/ Hypertext Transfer Protocol (HTTP) Status Code Registry}
     * (last updated 2015-05-19).
     *
     * Unless otherwise noted, the status code is defined in RFC2616.
     *
     * @var array
     */
    public static $statusTexts = array(
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',            // RFC2518
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',          // RFC4918
        208 => 'Already Reported',      // RFC5842
        226 => 'IM Used',               // RFC3229
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',    // RFC7238
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Payload Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',                                               // RFC2324
        422 => 'Unprocessable Entity',                                        // RFC4918
        423 => 'Locked',                                                      // RFC4918
        424 => 'Failed Dependency',                                           // RFC4918
        425 => 'Reserved for WebDAV advanced collections expired proposal',   // RFC2817
        426 => 'Upgrade Required',                                            // RFC2817
        428 => 'Precondition Required',                                       // RFC6585
        429 => 'Too Many Requests',                                           // RFC6585
        431 => 'Request Header Fields Too Large',                             // RFC6585
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates (Experimental)',                      // RFC2295
        507 => 'Insufficient Storage',                                        // RFC4918
        508 => 'Loop Detected',                                               // RFC5842
        510 => 'Not Extended',                                                // RFC2774
        511 => 'Network Authentication Required',                             // RFC6585
    );

    /**
     * Constructor.
     *
     * @param mixed $content The response content, see setContent()
     * @param int $status The response status code
     * @param array $headers An array of response headers
     *
     * @throws \InvalidArgumentException When the HTTP status code is not valid
     */
    public function __construct($content = '', $status = 200, $headers = array())
    {
        $this->headers = $headers;
        $this->setContent($content);
        $this->setStatusCode($status);
        $this->setProtocolVersion('1.0');
    }


    /**
     * Returns the Response as an HTTP string.
     *
     * The string representation of the Response is the same as the
     * one that will be sent to the client only if the prepare() method
     * has been called before.
     *
     * @return string The Response as an HTTP string
     *
     * @see prepare()
     */
    public function __toString()
    {
        return
            sprintf('HTTP/%s %s %s', $this->version, $this->statusCode, $this->statusText) . "\r\n" .
            $this->headers . "\r\n" .
            $this->getContent();
    }

    /**
     * Sends content for the current web response.
     *
     * @return Response
     */
    public function sendContent()
    {
        echo $this->content;

        return $this;
    }

    /**
     * @return $this
     */
    public function sendHeaders()
    {
        foreach ($this->headers as $key => $content) {
            header($key . ' : ' . $content);
        }
        http_response_code($this->getStatusCode());
        return $this;
    }

    /**
     * Sends HTTP headers and content.
     *
     * @return Response
     */
    public function send()
    {
        $this->sendHeaders();
        $this->sendContent();

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        return $this;
    }

    /**
     * @param array $headers
     */
    public function setHeaders($headers = [])
    {
        $this->headers = $headers;
    }

    /**
     * Sets the response content.
     *
     * Valid types are strings, numbers, null, and objects that implement a __toString() method.
     *
     * @param mixed $content Content that can be cast to string
     *
     * @return Response
     *
     * @throws \UnexpectedValueException
     */
    public function setContent($content)
    {
        if (null !== $content && !is_string($content) && !is_numeric($content) && !is_callable(array($content, '__toString'))) {
            throw new \UnexpectedValueException(sprintf('The Response content must be a string or object implementing __toString(), "%s" given.', gettype($content)));
        }

        $this->content = (string)$content;

        return $this;
    }

    /**
     * Gets the current response content.
     *
     * @return string Content
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Sets the HTTP protocol version (1.0 or 1.1).
     *
     * @param string $version The HTTP protocol version
     *
     * @return Response
     */
    public function setProtocolVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Gets the HTTP protocol version.
     *
     * @return string The HTTP protocol version
     */
    public function getProtocolVersion()
    {
        return $this->version;
    }

    /**
     * Sets the response status code.
     *
     * @param int $code HTTP status code
     * @param mixed $text HTTP status text
     *
     * If the status text is null it will be automatically populated for the known
     * status codes and left empty otherwise.
     *
     * @return Response
     *
     * @throws \InvalidArgumentException When the HTTP status code is not valid
     */
    public function setStatusCode($code, $text = null)
    {
        $this->statusCode = $code = (int)$code;
        if ($this->isInvalid()) {
            throw new \InvalidArgumentException(sprintf('The HTTP status code "%s" is not valid.', $code));
        }

        if (null === $text) {
            $this->statusText = isset(self::$statusTexts[$code]) ? self::$statusTexts[$code] : 'unknown status';

            return $this;
        }

        if (false === $text) {
            $this->statusText = '';

            return $this;
        }

        $this->statusText = $text;

        return $this;
    }

    /**
     * Retrieves the status code for the current web response.
     *
     * @return int Status code
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * Sets the response charset.
     *
     * @param string $charset Character set
     *
     * @return Response
     */
    public function setCharset($charset)
    {
        $this->charset = $charset;

        return $this;
    }

    /**
     * Retrieves the response charset.
     *
     * @return string Character set
     */
    public function getCharset()
    {
        return $this->charset;
    }


    // http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html

    /**
     * Is response invalid?
     *
     * @return bool
     */
    public function isInvalid()
    {
        return $this->statusCode < 100 || $this->statusCode >= 600;
    }

    /**
     * Is response informative?
     *
     * @return bool
     */
    public function isInformational()
    {
        return $this->statusCode >= 100 && $this->statusCode < 200;
    }

    /**
     * Is response successful?
     *
     * @return bool
     */
    public function isSuccessful()
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    /**
     * Is the response a redirect?
     *
     * @return bool
     */
    public function isRedirection()
    {
        return $this->statusCode >= 300 && $this->statusCode < 400;
    }

    /**
     * Is there a client error?
     *
     * @return bool
     */
    public function isClientError()
    {
        return $this->statusCode >= 400 && $this->statusCode < 500;
    }

    /**
     * Was there a server side error?
     *
     * @return bool
     */
    public function isServerError()
    {
        return $this->statusCode >= 500 && $this->statusCode < 600;
    }

    /**
     * Is the response OK?
     *
     * @return bool
     */
    public function isOk()
    {
        return 200 === $this->statusCode;
    }

    /**
     * Is the response forbidden?
     *
     * @return bool
     */
    public function isForbidden()
    {
        return 403 === $this->statusCode;
    }

    /**
     * Is the response a not found error?
     *
     * @return bool
     */
    public function isNotFound()
    {
        return 404 === $this->statusCode;
    }

    /**
     * Is the response a redirect of some form?
     *
     * @param string $location
     *
     * @return bool
     */
    public function isRedirect($location = null)
    {
        return in_array($this->statusCode, array(201, 301, 302, 303, 307, 308), true) && (null === $location ?: $location == $this->headers->get('Location'));
    }

    /**
     * Is the response empty?
     *
     * @return bool
     */
    public function isEmpty()
    {
        return in_array($this->statusCode, array(204, 304), true);
    }

}

<?php

namespace Devsrealm\TonicsRouterSystem;

use Devsrealm\TonicsRouterSystem\Events\OnRequestProcess;
use Devsrealm\TonicsRouterSystem\Interfaces\TonicsRouterRequestInputInterface;
use InvalidArgumentException;
use JsonSerializable;

class Response
{
    protected OnRequestProcess $onRequestProcess;
    private TonicsRouterRequestInputInterface $requestInput;

    public function __construct(OnRequestProcess $onRequestProcess = null, TonicsRouterRequestInputInterface $requestInput = null)
    {
        if ($onRequestProcess){
            $this->onRequestProcess = $onRequestProcess;
        }

        if ($requestInput){
            $this->requestInput = $requestInput;
        }
    }

    /**
     * Set the HTTP response code
     * @param int $code
     * @return $this
     */
    public function httpResponseCode(int $code): self
    {
        http_response_code($code);
        return $this;
    }

    /**
     * Refresh the current url
     */
    public function refresh(): void
    {
        $this->redirect($this->getOnRequestProcess()->getRequestURL());
    }

    /**
     * Redirect the response
     *
     * @param string $url
     * @param ?int $httpCode
     */
    public function redirect(string $url, ?int $httpCode = null): void
    {
        if ($httpCode !== null) {
            $this->httpResponseCode($httpCode);
        }

        $this->header('location: ' . $url);
        exit(0);
    }
    /**
     * Redirect the response
     *
     * @param string $url
     * @param ?int $httpCode
     */
/*    public function redirectBack(?int $httpCode = null): void
    {
        if ($httpCode !== null) {
            $this->httpResponseCode($httpCode);
        }

        $this->header("Location: javascript:history.back()");
        exit(0);
    }*/

    /**
     * DELETE THIS METHOD: IT IS CURRENTLY USELESS
     *
     * This is similar to the `redirect()` method, the only difference is you can specify a reason for rejection, and
     * it would flash it to cookie using the key `FlashMessage`
     * @param string $url
     * @param int|null $httpCode
     * @param string $reason
     */
    public function reject(string $url = '/404', ?int $httpCode = null, string $reason = 'Unknown Request')
    {
        setcookie("FlashMessage", $reason, time() + 10);
        ## Force the cookie to set on current request
        $_COOKIE["FlashMessage"] = $reason;
        $this->redirect($url, $httpCode);
    }

    /**
     * Add http authorisation
     * @param string $name
     * @return static
     */
    public function auth(string $name = ''): self
    {
        $this->headers([
            'WWW-Authenticate: Basic realm="' . $name . '"',
            'HTTP/1.0 401 Unauthorized',
        ]);

        return $this;
    }

    /**
     * Json encode
     * @param array|JsonSerializable $value
     * @param ?int $options JSON options Bitmask consisting of JSON_HEX_QUOT, JSON_HEX_TAG, JSON_HEX_AMP, JSON_HEX_APOS, JSON_NUMERIC_CHECK, JSON_PRETTY_PRINT, JSON_UNESCAPED_SLASHES, JSON_FORCE_OBJECT, JSON_PRESERVE_ZERO_FRACTION, JSON_UNESCAPED_UNICODE, JSON_PARTIAL_OUTPUT_ON_ERROR.
     * @param int $dept JSON debt.
     * @throws InvalidArgumentException
     */
    public function json(array|JsonSerializable $value, ?int $options = null, int $dept = 512): void
    {
        if (!$value instanceof JsonSerializable && !is_array($value)){
            throw new InvalidArgumentException('`value` must be of type array or object that is an instance of JsonSerializable Interface.');
        }
        $this->header('Content-Type: application/json; charset=utf-8');
        echo json_encode($value, $options, $dept);
        exit(0);
    }

    /**
     * @param $data
     * @param string $message
     * @param int $code
     * @param null $more
     */
    public function onSuccess($data, string $message = '', int $code = 200, $more = null)
    {
        $this->httpResponseCode($code)->json([
            'status' => $code,
            'message' => $message,
            'data' => $data,
            'more' => $more
        ], JSON_PRETTY_PRINT);
    }

    /**
     * @param int $code
     * @param string $message
     */
    public function onError(int $code, string $message = '')
    {
        $this->httpResponseCode($code)->json([
            'status' => $code,
            'message' => $message,
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Add Raw header to response
     * @param string $value
     * @return static
     */
    public function header(string $value): self
    {
        header($value);
        return $this;
    }

    /**
     * Add multiple Raw headers to response
     * @param array $headers
     * @return static
     */
    public function headers(array $headers): self
    {
        foreach ($headers as $header) {
            $this->header($header);
        }

        return $this;
    }

    /**
     * @return OnRequestProcess
     */
    public function getOnRequestProcess(): OnRequestProcess
    {
        return $this->onRequestProcess;
    }

    /**
     * @param OnRequestProcess $onRequestProcess
     */
    public function setOnRequestProcess(OnRequestProcess $onRequestProcess): void
    {
        $this->onRequestProcess = $onRequestProcess;
    }

    /**
     * @return TonicsRouterRequestInputInterface
     */
    public function getRequestInput(): TonicsRouterRequestInputInterface
    {
        return $this->requestInput;
    }

    /**
     * @param TonicsRouterRequestInputInterface $requestInput
     */
    public function setRequestInput(TonicsRouterRequestInputInterface $requestInput): void
    {
        $this->requestInput = $requestInput;
    }
}
<?php
namespace DominikJanak\Tools\Exceptions;

use Throwable;

/**
 * Rozšíření Exception o data používaná při generování HTML/JSON errorů
 */
abstract class RuntimeException extends \RuntimeException
{
    /** @var string message */
    /** @var int code */

    /**
     * @var string Speciální zpráva pro JSON, pokud není definováno, použije se message
     */
    protected string $jsonMessage = '';

    /**
     * @var string Upřesňující informace pro exception
     */
    protected string $detail = '';

    /**
     * @var string|null Název cesty (Symfony path name) kam přesměrovat při odchycení
     */
    protected ?string $redirect = null;

    /**
     * @var array parametry (url query) pro přesměrování
     */
    protected array $query = [];

    /**
     * @param string $message
     * @param string $jsonMessage
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(string $message, int $code = 500, string $jsonMessage = '', Throwable $previous = null)
    {
        $this->jsonMessage = $jsonMessage;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Speciální zpráva pro JSON
     * @return string
     */
    public function getJsonMessage() :string
    {
        if(!empty($this->jsonMessage)) {
            return $this->jsonMessage;
        }
        return $this->message;
    }

    /**
     * @param string $message
     * @return void
     */
    public function setJsonMessage(string $message = '') :self
    {
        $this->jsonMessage = $message;
        return $this;
    }

    /**
     *
     * @return string
     */
    public function getDetail(): string
    {
        return $this->detail;
    }

    /**
     * @param string $detail
     * @return $this
     */
    public function setDetail(string $detail): self
    {
        $this->detail = $detail;
        return $this;
    }

    /**
     * @param string $redirect
     * @param array $query
     * @return $this
     */
    public function enableRedirect(string $redirect, array $query = []): self
    {
        $this->redirect = $redirect;
        $this->query = $query;
        return $this;
    }

    /**
     * @return $this
     */
    public function disableRedirect(): self
    {
        $this->redirect = null;
        $this->query = [];
        return $this;
    }

    /**
     * @return string|null
     */
    public function getRedirectPath(): ?string
    {
        return $this->redirect;
    }

    /**
     * @return array
     */
    public function getQuery(): array
    {
        return $this->query;
    }

    /**
     * @param $query
     * @return $this
     */
    public function setQuery($query = []): self
    {
        $this->query = $query;
        return $this;
    }

    /**
     * @param string $key
     * @param string $value
     * @return $this
     */
    public function addQuery(string $key, string $value): self
    {
        $this->query[$key] = $value;
        return $this;
    }

    /**
     * @param string $key
     * @param bool $throw
     * @return $this
     */
    public function removeQuery(string $key, bool $throw = true): self
    {
        if(array_key_exists($key, $this->query)) {
            unset($this->query[$key]);
        }
        return $this;
    }
}

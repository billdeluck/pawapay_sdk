<?php
namespace Myzuwa\PawaPay\Exception;

/**
 * Payment Gateway Exception
 * 
 * Custom exception class for PawaPay SDK payment gateway errors
 */
class PaymentGatewayException extends \Exception
{
    /**
     * @var array Additional error data
     */
    protected $errorData = [];

    /**
     * Constructor
     *
     * @param string $message Error message
     * @param int $code Error code
     * @param array $errorData Additional error data
     */
    public function __construct(string $message = "", int $code = 0, array $errorData = [])
    {
        parent::__construct($message, $code);
        $this->errorData = $errorData;
    }

    /**
     * Get additional error data
     *
     * @return array
     */
    public function getErrorData(): array
    {
        return $this->errorData;
    }
}
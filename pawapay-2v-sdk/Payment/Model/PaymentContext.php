<?php
namespace Myzuwa\PawaPay\Payment\Model;

/**
 * Payment Context Value Object
 * 
 * Immutable class that encapsulates all necessary information
 * for processing a payment transaction.
 */
final class PaymentContext
{
    /** @var string Unique transaction reference */
    private string $transactionRef;

    /** @var Money Payment amount */
    private Money $amount;

    /** @var string Payment type (product, membership, promotion) */
    private string $paymentType;

    /** @var string Customer phone number */
    private string $phoneNumber;

    /** @var string MNO provider code */
    private string $providerCode;

    /** @var string Customer email */
    private string $customerEmail;

    /** @var array Additional metadata */
    private array $metadata;

    /** @var \DateTimeImmutable Creation timestamp */
    private \DateTimeImmutable $createdAt;

    /**
     * @param array $data Payment context data
     */
    private function __construct(array $data)
    {
        $this->transactionRef = $data['transaction_ref'];
        $this->amount = $data['amount'];
        $this->paymentType = $data['payment_type'];
        $this->phoneNumber = $data['phone_number'];
        $this->providerCode = $data['provider_code'];
        $this->customerEmail = $data['customer_email'] ?? '';
        $this->metadata = $data['metadata'] ?? [];
        $this->createdAt = new \DateTimeImmutable();
    }

    /**
     * Create a new payment context
     *
     * @param array $data
     * @return self
     * @throws \InvalidArgumentException
     */
    public static function create(array $data): self
    {
        self::validateData($data);
        return new self($data);
    }

    /**
     * Create payment context for product purchase
     *
     * @param Money $amount
     * @param string $phoneNumber
     * @param string $providerCode
     * @param array $metadata
     * @return self
     */
    public static function forProduct(
        Money $amount,
        string $phoneNumber,
        string $providerCode,
        array $metadata = []
    ): self {
        return self::create([
            'transaction_ref' => self::generateTransactionRef('PRD'),
            'amount' => $amount,
            'payment_type' => 'product',
            'phone_number' => $phoneNumber,
            'provider_code' => $providerCode,
            'metadata' => $metadata
        ]);
    }

    /**
     * Create payment context for membership
     *
     * @param Money $amount
     * @param string $phoneNumber
     * @param string $providerCode
     * @param array $metadata
     * @return self
     */
    public static function forMembership(
        Money $amount,
        string $phoneNumber,
        string $providerCode,
        array $metadata = []
    ): self {
        return self::create([
            'transaction_ref' => self::generateTransactionRef('MEM'),
            'amount' => $amount,
            'payment_type' => 'membership',
            'phone_number' => $phoneNumber,
            'provider_code' => $providerCode,
            'metadata' => $metadata
        ]);
    }

    /**
     * Create payment context for promotion
     *
     * @param Money $amount
     * @param string $phoneNumber
     * @param string $providerCode
     * @param array $metadata
     * @return self
     */
    public static function forPromotion(
        Money $amount,
        string $phoneNumber,
        string $providerCode,
        array $metadata = []
    ): self {
        return self::create([
            'transaction_ref' => self::generateTransactionRef('PRM'),
            'amount' => $amount,
            'payment_type' => 'promotion',
            'phone_number' => $phoneNumber,
            'provider_code' => $providerCode,
            'metadata' => $metadata
        ]);
    }

    /**
     * Get transaction reference
     *
     * @return string
     */
    public function getTransactionRef(): string
    {
        return $this->transactionRef;
    }

    /**
     * Get payment amount
     *
     * @return Money
     */
    public function getAmount(): Money
    {
        return $this->amount;
    }

    /**
     * Get payment type
     *
     * @return string
     */
    public function getPaymentType(): string
    {
        return $this->paymentType;
    }

    /**
     * Get phone number
     *
     * @return string
     */
    public function getPhoneNumber(): string
    {
        return $this->phoneNumber;
    }

    /**
     * Get provider code
     *
     * @return string
     */
    public function getProviderCode(): string
    {
        return $this->providerCode;
    }

    /**
     * Get customer email
     *
     * @return string
     */
    public function getCustomerEmail(): string
    {
        return $this->customerEmail;
    }

    /**
     * Get metadata
     *
     * @return array
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Get creation timestamp
     *
     * @return \DateTimeImmutable
     */
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Generate unique transaction reference
     *
     * @param string $prefix
     * @return string
     */
    private static function generateTransactionRef(string $prefix): string
    {
        return sprintf(
            '%s-%s-%s',
            $prefix,
            date('Ymd'),
            bin2hex(random_bytes(4))
        );
    }

    /**
     * Validate payment context data
     *
     * @param array $data
     * @throws \InvalidArgumentException
     */
    private static function validateData(array $data): void
    {
        $required = ['amount', 'payment_type', 'phone_number', 'provider_code'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new \InvalidArgumentException("Missing required field: {$field}");
            }
        }

        if (!$data['amount'] instanceof Money) {
            throw new \InvalidArgumentException('Amount must be a Money object');
        }

        if (!in_array($data['payment_type'], ['product', 'membership', 'promotion'])) {
            throw new \InvalidArgumentException('Invalid payment type');
        }
    }
}
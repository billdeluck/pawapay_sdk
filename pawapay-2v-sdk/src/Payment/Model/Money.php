<?php
namespace Myzuwa\PawaPay\Payment\Model;

/**
 * Value Object representing a monetary amount
 * 
 * Immutable class that ensures precise handling of monetary values
 * using BCMath for accurate decimal arithmetic
 */
final class Money
{
    private string $amount;
    private string $currency;

    /**
     * @param string|int|float $amount
     * @param string $currency
     */
    /**
     * Create a new Money instance
     * 
     * @param string|int|float $amount The amount in major units (e.g. dollars)
     * @param string $currency The three-letter ISO currency code
     * @throws \InvalidArgumentException If amount is invalid or currency code is not 3 letters
     */
    public function __construct($amount, string $currency)
    {
        if (!is_numeric($amount)) {
            throw new \InvalidArgumentException('Amount must be numeric');
        }
        
        $this->amount = $this->normalizeAmount($amount);
        $this->currency = strtoupper((string) $currency);
        $this->validate();
    }

    /**
     * Create Money object from minor units (cents)
     *
     * @param int $minorUnits
     * @param string $currency
     * @return self
     */
    public static function fromMinorUnits(int $minorUnits, string $currency): self
    {
        $amount = bcdiv((string)$minorUnits, '100', 2);
        return new Money($amount, $currency);
    }

    /**
     * Get amount in minor units (cents)
     *
     * @return int
     */
    public function getMinorUnits(): int
    {
        return (int)bcmul($this->amount, '100', 0);
    }

    /**
     * Get amount as string with 2 decimal places
     *
     * @return string
     */
    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * Get currency code
     *
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * Add another Money object of the same currency
     *
     * @param Money $other
     * @return Money
     * @throws \InvalidArgumentException
     */
    public function add(Money $other): Money
    {
        $this->assertSameCurrency($other);
        return new Money(
            bcadd($this->amount, $other->getAmount(), 2),
            $this->currency
        );
    }

    /**
     * Subtract another Money object of the same currency
     *
     * @param Money $other
     * @return Money
     * @throws \InvalidArgumentException
     */
    public function subtract(Money $other): Money
    {
        $this->assertSameCurrency($other);
        return new Money(
            bcsub($this->amount, $other->getAmount(), 2),
            $this->currency
        );
    }

    /**
     * Multiply by a factor
     *
     * @param string|int|float $factor
     * @return Money
     */
    public function multiply($factor): Money
    {
        return new Money(
            bcmul($this->amount, (string)$factor, 2),
            $this->currency
        );
    }

    /**
     * Check if amount is zero
     *
     * @return bool
     */
    public function isZero(): bool
    {
        return bccomp($this->amount, '0', 2) === 0;
    }

    /**
     * Check if amount is positive
     *
     * @return bool
     */
    public function isPositive(): bool
    {
        return bccomp($this->amount, '0', 2) === 1;
    }

    /**
     * Check if amount is negative
     *
     * @return bool
     */
    public function isNegative(): bool
    {
        return bccomp($this->amount, '0', 2) === -1;
    }

    /**
     * Compare with another Money object
     *
     * @param Money $other
     * @return int -1 if less, 0 if equal, 1 if greater
     * @throws \InvalidArgumentException
     */
    public function compareTo(Money $other): int
    {
        $this->assertSameCurrency($other);
        return bccomp($this->amount, $other->getAmount(), 2);
    }

    /**
     * Check if equal to another Money object
     *
     * @param Money $other
     * @return bool
     */
    public function equals(Money $other): bool
    {
        return $this->currency === $other->getCurrency() 
            && $this->compareTo($other) === 0;
    }

    /**
     * Convert to string representation
     *
     * @return string
     */
    public function toString(): string
    {
        return sprintf('%s %s', $this->amount, $this->currency);
    }

    /**
     * Normalize amount to string with 2 decimal places
     *
     * @param string|int|float $amount
     * @return string
     */
    private function normalizeAmount($amount): string
    {
        return number_format((float)$amount, 2, '.', '');
    }

    /**
     * Validate money object
     *
     * @throws \InvalidArgumentException
     */
    private function validate(): void
    {
        if (!is_numeric($this->amount)) {
            throw new \InvalidArgumentException('Amount must be numeric');
        }

        if (strlen($this->currency) !== 3) {
            throw new \InvalidArgumentException('Currency must be a 3-letter ISO code');
        }
    }

    /**
     * Assert same currency
     *
     * @param Money $other
     * @throws \InvalidArgumentException
     */
    private function assertSameCurrency(Money $other): void
    {
        if ($this->currency !== $other->getCurrency()) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Cannot operate on different currencies (%s, %s)',
                    $this->currency,
                    $other->getCurrency()
                )
            );
        }
    }
}
<?php

namespace WebBook\Mall\Classes\Payments;

/**
 * This provider can be used for all webbook payments.
 *
 * The order's payment state will be marked as pending if
 * this provider is used.
 */
class WebBook extends PaymentProvider
{
    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return 'WebBook';
    }

    /**
     * {@inheritdoc}
     */
    public function identifier(): string
    {
        return 'webbook';
    }

    /**
     * {@inheritDoc}
     */
    public function validate(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function process(PaymentResult $result): PaymentResult
    {
        return $result->pending();
    }

    /**
     * {@inheritdoc}
     */
    public function settings(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function encryptedSettings(): array
    {
        return [];
    }
}

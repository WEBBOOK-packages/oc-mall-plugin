<?php

declare(strict_types=1);

namespace WebBook\Mall\Classes\Vat;

use WebBook\Mall\Models\Cart;

class IntraEuTaxDecisionService
{
    public const TREATMENT_STANDARD = 'standard';
    public const TREATMENT_INTRA_EU_EXEMPT = 'intra_eu_exempt';

    protected array $memoizedDecisions = [];

    public function __construct(
        protected ViesVatValidator $validator,
        protected array $config
    ) {
    }

    public function forCart(Cart $cart, bool $refreshDecision = false): array
    {
        $cart->loadMissing([
            'billing_address.country',
            'shipping_address.country',
        ]);

        $vatId = (string)(optional($cart->billing_address)->vat ?? '');
        $billingCountryCode = optional(optional($cart->billing_address)->country)->code;
        $shippingCountryCode = optional(optional($cart->shipping_address)->country)->code;
        $billingPostalCode = optional($cart->billing_address)->zip;
        $shippingPostalCode = optional($cart->shipping_address)->zip;
        $memoKey = hash('sha256', implode('|', [
            (string)$cart->id,
            $vatId,
            (string)$billingCountryCode,
            (string)$shippingCountryCode,
            (string)$billingPostalCode,
            (string)$shippingPostalCode,
        ]));

        if ($refreshDecision) {
            unset($this->memoizedDecisions[$memoKey]);
        } elseif (isset($this->memoizedDecisions[$memoKey])) {
            return $this->memoizedDecisions[$memoKey];
        }

        return $this->memoizedDecisions[$memoKey] = $this->decide(
            $vatId,
            $billingCountryCode,
            $shippingCountryCode,
            $billingPostalCode,
            $shippingPostalCode
        );
    }

    public function decide(
        ?string $vatId,
        ?string $billingCountryCode,
        ?string $shippingCountryCode,
        ?string $billingPostalCode = null,
        ?string $shippingPostalCode = null
    ): array {
        if (!($this->config['enabled'] ?? false)) {
            return $this->standardDecision('vies_disabled');
        }

        if (trim((string)$vatId) === '') {
            return $this->standardDecision('vat_missing');
        }

        $validation = $this->validator->validate((string)$vatId);

        if ($validation->isUnavailable()) {
            return $this->standardDecision('vies_unavailable', $validation);
        }

        if (!$validation->isValid()) {
            return $this->standardDecision('vies_invalid', $validation);
        }

        $vatCountryCode = strtoupper((string)$validation->countryCode);
        $billingCountryCode = $this->normalizeAddressCountryCode($billingCountryCode);
        $shippingCountryCode = $this->normalizeAddressCountryCode($shippingCountryCode);

        if ($billingCountryCode === null || $shippingCountryCode === null) {
            return $this->standardDecision('country_missing', $validation);
        }

        if ($vatCountryCode === 'CZ' || $shippingCountryCode === 'CZ') {
            return $this->standardDecision('domestic_delivery', $validation);
        }

        $supported = array_map(
            static fn ($code) => strtoupper((string)$code),
            (array)($this->config['automatic_eu_country_codes'] ?? [])
        );

        if (!in_array($vatCountryCode, $supported, true)) {
            return $this->standardDecision('country_not_supported', $validation);
        }

        if (
            $this->isExcludedTaxTerritory($billingCountryCode, $billingPostalCode)
            || $this->isExcludedTaxTerritory($shippingCountryCode, $shippingPostalCode)
        ) {
            return $this->standardDecision('tax_territory_not_supported', $validation);
        }

        if ($vatCountryCode !== $billingCountryCode || $vatCountryCode !== $shippingCountryCode) {
            return $this->standardDecision('country_mismatch', $validation);
        }

        return [
            'tax_treatment' => self::TREATMENT_INTRA_EU_EXEMPT,
            'reason' => 'valid_vat_and_matching_eu_delivery',
            'is_exempt' => true,
            'validation' => $validation,
        ];
    }

    public function auditData(array $decision): ?array
    {
        $validation = $decision['validation'] ?? null;

        if (!$validation instanceof VatValidationResult) {
            return null;
        }

        return [
            'vat_id' => $validation->normalizedVatId,
            'status' => $validation->status,
            'request_date' => $validation->requestDate,
            'request_identifier' => $validation->requestIdentifier,
            'checked_at' => $validation->checkedAt,
            'tax_treatment' => (string)$decision['tax_treatment'],
            'reason' => (string)$decision['reason'],
        ];
    }

    protected function normalizeAddressCountryCode(?string $countryCode): ?string
    {
        if ($countryCode === null || trim($countryCode) === '') {
            return null;
        }

        $countryCode = strtoupper(trim($countryCode));

        return $countryCode === 'GR' ? 'EL' : $countryCode;
    }

    protected function isExcludedTaxTerritory(string $countryCode, ?string $postalCode): bool
    {
        if ($postalCode === null || trim($postalCode) === '') {
            return false;
        }

        $postalCode = strtoupper(preg_replace('/[\s-]+/', '', trim($postalCode)) ?? '');
        $patterns = (array)($this->config['excluded_tax_territory_postal_patterns'][$countryCode] ?? []);

        foreach ($patterns as $pattern) {
            if (@preg_match((string)$pattern, $postalCode) === 1) {
                return true;
            }
        }

        return false;
    }

    protected function standardDecision(
        string $reason,
        ?VatValidationResult $validation = null
    ): array {
        return [
            'tax_treatment' => self::TREATMENT_STANDARD,
            'reason' => $reason,
            'is_exempt' => false,
            'validation' => $validation,
        ];
    }
}

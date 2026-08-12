<?php

declare(strict_types=1);

namespace WebBook\Mall\Tests\Classes\Vat;

use PHPUnit\Framework\TestCase;
use WebBook\Mall\Classes\Vat\IntraEuTaxDecisionService;
use WebBook\Mall\Classes\Vat\VatValidationResult;
use WebBook\Mall\Classes\Vat\ViesVatValidator;

class IntraEuTaxDecisionServiceTest extends TestCase
{
    /**
     * @dataProvider standardDecisionProvider
     */
    public function test_it_keeps_standard_tax_unless_all_exemption_conditions_match(
        VatValidationResult $validation,
        ?string $billingCountry,
        ?string $shippingCountry,
        string $expectedReason
    ): void {
        $service = $this->serviceReturning($validation);
        $decision = $service->decide(
            $validation->normalizedVatId,
            $billingCountry,
            $shippingCountry
        );

        $this->assertFalse($decision['is_exempt']);
        $this->assertSame(IntraEuTaxDecisionService::TREATMENT_STANDARD, $decision['tax_treatment']);
        $this->assertSame($expectedReason, $decision['reason']);
    }

    public function test_it_exempts_a_valid_vat_id_with_matching_foreign_eu_countries(): void
    {
        $validation = $this->validation(VatValidationResult::STATUS_VALID, 'DE');
        $decision = $this->serviceReturning($validation)->decide('DE123456789', 'de', 'DE');

        $this->assertTrue($decision['is_exempt']);
        $this->assertSame(
            IntraEuTaxDecisionService::TREATMENT_INTRA_EU_EXEMPT,
            $decision['tax_treatment']
        );
        $this->assertSame('valid_vat_and_matching_eu_delivery', $decision['reason']);
    }

    public function test_it_matches_greek_billing_and_shipping_to_the_el_vies_prefix(): void
    {
        $validation = $this->validation(VatValidationResult::STATUS_VALID, 'EL');
        $decision = $this->serviceReturning($validation)->decide('EL123456789', 'gr', 'GR');

        $this->assertTrue($decision['is_exempt']);
    }

    public function test_it_does_not_automatically_exempt_a_special_tax_territory(): void
    {
        $validation = $this->validation(VatValidationResult::STATUS_VALID, 'ES');
        $decision = $this->serviceReturning($validation)->decide(
            'ES123456789',
            'ES',
            'ES',
            '35001',
            '35001'
        );

        $this->assertFalse($decision['is_exempt']);
        $this->assertSame('tax_territory_not_supported', $decision['reason']);
    }

    public function test_it_keeps_standard_tax_when_the_vat_id_is_missing(): void
    {
        $validator = $this->createMock(ViesVatValidator::class);
        $validator->expects($this->never())->method('validate');

        $service = new IntraEuTaxDecisionService($validator, [
            'enabled' => true,
            'automatic_eu_country_codes' => ['DE'],
        ]);
        $decision = $service->decide('', 'DE', 'DE');

        $this->assertFalse($decision['is_exempt']);
        $this->assertSame('vat_missing', $decision['reason']);
    }

    public function test_it_does_not_call_vies_when_the_feature_is_disabled(): void
    {
        $validator = $this->createMock(ViesVatValidator::class);
        $validator->expects($this->never())->method('validate');

        $service = new IntraEuTaxDecisionService($validator, [
            'enabled' => false,
            'automatic_eu_country_codes' => ['DE'],
        ]);
        $decision = $service->decide('DE123456789', 'DE', 'DE');

        $this->assertFalse($decision['is_exempt']);
        $this->assertSame('vies_disabled', $decision['reason']);
    }

    public function test_it_builds_a_minimal_audit_snapshot(): void
    {
        $validation = $this->validation(VatValidationResult::STATUS_VALID, 'DE');
        $service = $this->serviceReturning($validation);
        $decision = $service->decide('DE123456789', 'DE', 'DE');
        $audit = $service->auditData($decision);

        $this->assertSame('DE123456789', $audit['vat_id']);
        $this->assertSame('REQUEST-123', $audit['request_identifier']);
        $this->assertSame('intra_eu_exempt', $audit['tax_treatment']);
        $this->assertArrayNotHasKey('name', $audit);
        $this->assertArrayNotHasKey('address', $audit);
    }

    public static function standardDecisionProvider(): array
    {
        return [
            'invalid VAT' => [
                self::makeValidation(VatValidationResult::STATUS_INVALID, 'DE'),
                'DE',
                'DE',
                'vies_invalid',
            ],
            'VIES unavailable' => [
                self::makeValidation(VatValidationResult::STATUS_UNAVAILABLE, 'DE'),
                'DE',
                'DE',
                'vies_unavailable',
            ],
            'Czech delivery' => [
                self::makeValidation(VatValidationResult::STATUS_VALID, 'DE'),
                'DE',
                'CZ',
                'domestic_delivery',
            ],
            'country mismatch' => [
                self::makeValidation(VatValidationResult::STATUS_VALID, 'DE'),
                'DE',
                'AT',
                'country_mismatch',
            ],
            'unsupported country' => [
                self::makeValidation(VatValidationResult::STATUS_VALID, 'XI'),
                'XI',
                'XI',
                'country_not_supported',
            ],
        ];
    }

    protected function serviceReturning(VatValidationResult $validation): IntraEuTaxDecisionService
    {
        $validator = $this->createMock(ViesVatValidator::class);
        $validator->method('validate')->willReturn($validation);

        return new IntraEuTaxDecisionService($validator, [
            'enabled' => true,
            'automatic_eu_country_codes' => ['AT', 'DE', 'EL', 'ES'],
            'excluded_tax_territory_postal_patterns' => [
                'ES' => ['/^(35|38)\d{3}$/'],
            ],
        ]);
    }

    protected function validation(string $status, string $countryCode): VatValidationResult
    {
        return self::makeValidation($status, $countryCode);
    }

    protected static function makeValidation(string $status, string $countryCode): VatValidationResult
    {
        return new VatValidationResult(
            $status,
            $countryCode . '123456789',
            $countryCode,
            '123456789',
            '2026-07-25T11:29:19.010Z',
            'REQUEST-123',
            '2026-07-25T11:29:20+00:00'
        );
    }
}

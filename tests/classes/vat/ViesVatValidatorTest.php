<?php

declare(strict_types=1);

namespace WebBook\Mall\Tests\Classes\Vat;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Request;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use WebBook\Mall\Classes\Vat\VatValidationResult;
use WebBook\Mall\Classes\Vat\ViesVatValidator;

class ViesVatValidatorTest extends TestCase
{
    public function test_it_normalizes_and_validates_a_vat_id_via_the_official_rest_payload(): void
    {
        $http = new HttpFactory();
        $options = [];

        $http->fake(function (Request $request, array $requestOptions) use (&$options) {
            $options = $requestOptions;

            return HttpFactory::response([
                'countryCode' => 'DE',
                'vatNumber' => '123B45678',
                'requestDate' => '2026-07-25T11:29:19.010Z',
                'valid' => true,
                'requestIdentifier' => 'REQUEST-123',
            ]);
        });

        $validator = $this->validator($http);
        $result = $validator->validate(' de-123 b.45678 ');

        $this->assertTrue($result->isValid());
        $this->assertSame('DE123B45678', $result->normalizedVatId);
        $this->assertSame('REQUEST-123', $result->requestIdentifier);
        $this->assertSame(3, $options['connect_timeout']);
        $this->assertSame(8, $options['timeout']);

        $http->assertSent(function (Request $request) {
            return $request->url() === 'https://example.test/check-vat-number'
                && $request['countryCode'] === 'DE'
                && $request['vatNumber'] === '123B45678'
                && $request['requesterMemberStateCode'] === 'CZ'
                && $request['requesterNumber'] === '63076331';
        });
    }

    public function test_it_uses_the_cached_result_for_repeated_validation(): void
    {
        $http = new HttpFactory();
        $http->fake([
            '*' => HttpFactory::response([
                'countryCode' => 'DE',
                'vatNumber' => '123456789',
                'valid' => true,
            ]),
        ]);

        $validator = $this->validator($http);

        $this->assertTrue($validator->validate('DE123456789')->isValid());
        $this->assertTrue($validator->validate('DE123456789')->isValid());
        $http->assertSentCount(1);
    }

    public function test_it_maps_a_negative_vies_response_to_invalid(): void
    {
        $http = new HttpFactory();
        $http->fake([
            '*' => HttpFactory::response([
                'countryCode' => 'DE',
                'vatNumber' => '123456789',
                'requestDate' => '2026-07-25T11:29:19.010Z',
                'valid' => false,
                'requestIdentifier' => 'REQUEST-123',
            ]),
        ]);

        $result = $this->validator($http)->validate('DE123456789');

        $this->assertTrue($result->isInvalid());
        $this->assertSame('REQUEST-123', $result->requestIdentifier);
    }

    public function test_it_maps_invalid_input_and_http_failures_without_throwing(): void
    {
        $http = new HttpFactory();
        $http->fake([
            '*' => HttpFactory::response([
                'errorWrappers' => [
                    ['error' => 'MS_UNAVAILABLE'],
                ],
            ], 500),
        ]);

        $validator = $this->validator($http);
        $invalid = $validator->validate('not-a-vat-id');
        $unavailable = $validator->validate('DE123456789');

        $this->assertSame(VatValidationResult::STATUS_INVALID, $invalid->status);
        $this->assertSame('INVALID_INPUT', $invalid->errorCode);
        $this->assertSame(VatValidationResult::STATUS_UNAVAILABLE, $unavailable->status);
        $this->assertSame('MS_UNAVAILABLE', $unavailable->errorCode);
        $http->assertSentCount(1);
    }

    public function test_it_maps_the_greek_country_code_to_the_vies_prefix(): void
    {
        $http = new HttpFactory();
        $http->fake(function (Request $request) {
            return HttpFactory::response([
                'countryCode' => 'EL',
                'vatNumber' => $request['vatNumber'],
                'valid' => true,
            ]);
        });

        $result = $this->validator($http)->validate('GR123456789');

        $this->assertTrue($result->isValid());
        $this->assertSame('EL123456789', $result->normalizedVatId);
        $http->assertSent(fn (Request $request) => $request['countryCode'] === 'EL');
    }

    public function test_it_fails_safe_when_the_request_times_out(): void
    {
        $http = new HttpFactory();
        $http->fake(static function () {
            throw new ConnectionException('Request timed out.');
        });

        $result = $this->validator($http)->validate('DE123456789');

        $this->assertTrue($result->isUnavailable());
        $this->assertSame('CONNECTION_ERROR', $result->errorCode);
    }

    protected function validator(HttpFactory $http): ViesVatValidator
    {
        return new ViesVatValidator(
            $http,
            new Repository(new ArrayStore()),
            new NullLogger(),
            [
                'endpoint' => 'https://example.test/check-vat-number',
                'connect_timeout_seconds' => 3,
                'timeout_seconds' => 8,
                'requester_country_code' => 'CZ',
                'requester_vat_number' => 'CZ63076331',
                'vies_country_codes' => ['CZ', 'DE', 'EL'],
                'cache' => [
                    'valid_minutes' => 1440,
                    'invalid_minutes' => 60,
                    'unavailable_minutes' => 5,
                ],
            ]
        );
    }
}

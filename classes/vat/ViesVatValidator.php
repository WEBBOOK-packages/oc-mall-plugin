<?php

declare(strict_types=1);

namespace WebBook\Mall\Classes\Vat;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Client\Factory as HttpFactory;
use Psr\Log\LoggerInterface;
use Throwable;

class ViesVatValidator
{
    public function __construct(
        protected HttpFactory $http,
        protected CacheRepository $cache,
        protected LoggerInterface $logger,
        protected array $config
    ) {
    }

    public function validate(string $vatId, bool $forceRefresh = false): VatValidationResult
    {
        $normalized = $this->normalize($vatId);

        if ($normalized === null) {
            return new VatValidationResult(
                VatValidationResult::STATUS_INVALID,
                $this->sanitize($vatId),
                null,
                null,
                null,
                null,
                gmdate(DATE_ATOM),
                'INVALID_INPUT'
            );
        }

        [$countryCode, $vatNumber, $normalizedVatId] = $normalized;
        $cacheKey = 'webbook.mall.vies.' . hash('sha256', $normalizedVatId);

        if (!$forceRefresh) {
            $cached = $this->cache->get($cacheKey);

            if (is_array($cached)) {
                return VatValidationResult::fromArray($cached);
            }
        }

        try {
            $response = $this->http
                ->acceptJson()
                ->connectTimeout((int)($this->config['connect_timeout_seconds'] ?? 3))
                ->timeout((int)($this->config['timeout_seconds'] ?? 8))
                ->post((string)$this->config['endpoint'], $this->requestPayload($countryCode, $vatNumber));

            $data = $response->json();

            if ($response->status() === 400) {
                $result = $this->result(
                    VatValidationResult::STATUS_INVALID,
                    $countryCode,
                    $vatNumber,
                    $normalizedVatId,
                    is_array($data) ? $data : [],
                    $this->responseErrorCode($data, 'INVALID_INPUT')
                );
            } elseif (!$response->successful() || !is_array($data) || !array_key_exists('valid', $data)) {
                $result = $this->result(
                    VatValidationResult::STATUS_UNAVAILABLE,
                    $countryCode,
                    $vatNumber,
                    $normalizedVatId,
                    is_array($data) ? $data : [],
                    $this->responseErrorCode($data, 'HTTP_' . $response->status())
                );
            } else {
                $result = $this->result(
                    $data['valid'] ? VatValidationResult::STATUS_VALID : VatValidationResult::STATUS_INVALID,
                    $countryCode,
                    $vatNumber,
                    $normalizedVatId,
                    $data
                );
            }
        } catch (Throwable $exception) {
            $this->logger->warning('VIES VAT validation is unavailable.', [
                'country_code' => $countryCode,
                'exception' => get_class($exception),
            ]);

            $result = $this->result(
                VatValidationResult::STATUS_UNAVAILABLE,
                $countryCode,
                $vatNumber,
                $normalizedVatId,
                [],
                'CONNECTION_ERROR'
            );
        }

        $this->cache->put($cacheKey, $result->toArray(), $this->cacheSeconds($result));

        return $result;
    }

    /**
     * @return array{string, string, string}|null
     */
    protected function normalize(string $vatId): ?array
    {
        $normalized = $this->sanitize($vatId);

        if (!preg_match('/^([A-Z]{2})([A-Z0-9]{2,12})$/', $normalized, $matches)) {
            return null;
        }

        $countryCode = $matches[1] === 'GR' ? 'EL' : $matches[1];
        $vatNumber = $matches[2];

        $supportedCountryCodes = array_map(
            static fn ($code) => strtoupper((string)$code),
            (array)($this->config['vies_country_codes'] ?? [])
        );

        if ($supportedCountryCodes !== [] && !in_array($countryCode, $supportedCountryCodes, true)) {
            return null;
        }

        return [$countryCode, $vatNumber, $countryCode . $vatNumber];
    }

    protected function sanitize(string $vatId): string
    {
        return preg_replace('/[^A-Z0-9]/', '', strtoupper(trim($vatId))) ?? '';
    }

    protected function requestPayload(string $countryCode, string $vatNumber): array
    {
        $requesterCountryCode = strtoupper((string)($this->config['requester_country_code'] ?? ''));
        $requesterNumber = $this->sanitize((string)($this->config['requester_vat_number'] ?? ''));

        if ($requesterCountryCode !== '' && str_starts_with($requesterNumber, $requesterCountryCode)) {
            $requesterNumber = substr($requesterNumber, 2);
        }

        return array_filter([
            'countryCode' => $countryCode,
            'vatNumber' => $vatNumber,
            'requesterMemberStateCode' => $requesterCountryCode,
            'requesterNumber' => $requesterNumber,
        ], static fn ($value) => $value !== '');
    }

    protected function result(
        string $status,
        string $countryCode,
        string $vatNumber,
        string $normalizedVatId,
        array $data,
        ?string $errorCode = null
    ): VatValidationResult {
        return new VatValidationResult(
            $status,
            $normalizedVatId,
            $countryCode,
            $vatNumber,
            isset($data['requestDate']) ? (string)$data['requestDate'] : null,
            isset($data['requestIdentifier']) && $data['requestIdentifier'] !== ''
                ? (string)$data['requestIdentifier']
                : null,
            gmdate(DATE_ATOM),
            $errorCode
        );
    }

    protected function responseErrorCode(mixed $data, string $fallback): string
    {
        if (!is_array($data)) {
            return $fallback;
        }

        return (string)($data['errorWrappers'][0]['error'] ?? $data['error'] ?? $fallback);
    }

    protected function cacheSeconds(VatValidationResult $result): int
    {
        $cache = (array)($this->config['cache'] ?? []);

        if ($result->isValid()) {
            return (int)($cache['valid_minutes'] ?? 1440) * 60;
        }

        if ($result->isInvalid()) {
            return (int)($cache['invalid_minutes'] ?? 60) * 60;
        }

        return (int)($cache['unavailable_minutes'] ?? 5) * 60;
    }
}

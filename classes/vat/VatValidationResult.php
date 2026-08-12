<?php

declare(strict_types=1);

namespace WebBook\Mall\Classes\Vat;

final class VatValidationResult
{
    public const STATUS_VALID = 'valid';
    public const STATUS_INVALID = 'invalid';
    public const STATUS_UNAVAILABLE = 'unavailable';

    public function __construct(
        public string $status,
        public string $normalizedVatId,
        public ?string $countryCode,
        public ?string $vatNumber,
        public ?string $requestDate,
        public ?string $requestIdentifier,
        public string $checkedAt,
        public ?string $errorCode = null
    ) {
    }

    public function isValid(): bool
    {
        return $this->status === self::STATUS_VALID;
    }

    public function isInvalid(): bool
    {
        return $this->status === self::STATUS_INVALID;
    }

    public function isUnavailable(): bool
    {
        return $this->status === self::STATUS_UNAVAILABLE;
    }

    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'normalized_vat_id' => $this->normalizedVatId,
            'country_code' => $this->countryCode,
            'vat_number' => $this->vatNumber,
            'request_date' => $this->requestDate,
            'request_identifier' => $this->requestIdentifier,
            'checked_at' => $this->checkedAt,
            'error_code' => $this->errorCode,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (string)($data['status'] ?? self::STATUS_UNAVAILABLE),
            (string)($data['normalized_vat_id'] ?? ''),
            isset($data['country_code']) ? (string)$data['country_code'] : null,
            isset($data['vat_number']) ? (string)$data['vat_number'] : null,
            isset($data['request_date']) ? (string)$data['request_date'] : null,
            isset($data['request_identifier']) ? (string)$data['request_identifier'] : null,
            (string)($data['checked_at'] ?? gmdate(DATE_ATOM)),
            isset($data['error_code']) ? (string)$data['error_code'] : null
        );
    }
}

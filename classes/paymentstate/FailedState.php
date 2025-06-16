<?php

namespace WebBook\Mall\Classes\PaymentState;

class FailedState extends PaymentState
{
    public static function getAvailableTransitions(): array
    {
        return [
            PendingState::class,
            PaidState::class,
        ];
    }

    public static function color(): string
    {
        return '#d30000';
    }
}

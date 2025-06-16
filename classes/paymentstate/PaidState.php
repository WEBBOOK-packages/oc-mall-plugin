<?php

namespace WebBook\Mall\Classes\PaymentState;

class PaidState extends PaymentState
{
    public static function getAvailableTransitions(): array
    {
        return [
            PendingState::class,
            RefundedState::class,
        ];
    }

    public static function color(): string
    {
        return '#189e51';
    }
}

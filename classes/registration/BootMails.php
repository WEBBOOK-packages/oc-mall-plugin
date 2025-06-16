<?php

namespace WebBook\Mall\Classes\Registration;

trait BootMails
{
    public function registerMailTemplates()
    {
        return [
            'webbook.mall::mail.customer.created',
            'webbook.mall::mail.order.state_changed',
            'webbook.mall::mail.order.shipped',
            'webbook.mall::mail.checkout.succeeded',
            'webbook.mall::mail.checkout.failed',
            'webbook.mall::mail.payment.failed',
            'webbook.mall::mail.payment.paid',
            'webbook.mall::mail.payment.refunded',
            'webbook.mall::mail.admin.checkout_succeeded',
            'webbook.mall::mail.admin.checkout_failed',
            'webbook.mall::mail.admin.payment_paid',
        ];
    }

    public function registerMailPartials()
    {
        return [
            'mall.order.table'         => 'webbook.mall::mail._partials.order.table',
            'mall.order.tracking'      => 'webbook.mall::mail._partials.order.tracking',
            'mall.order.addresses'     => 'webbook.mall::mail._partials.order.addresses',
            'mall.order.payment_state' => 'webbook.mall::mail._partials.order.payment_state',
            'mall.customer.address'    => 'webbook.mall::mail._partials.customer.address',
        ];
    }
}

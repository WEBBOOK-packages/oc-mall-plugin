<?php

declare(strict_types=1);

namespace WebBook\Mall\Updates\Seeders\Tables;

use October\Rain\Database\Updates\Seeder;
use WebBook\Mall\Models\Notification;

class NotificationTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * @param bool $useDemo
     * @return void
     */
    public function run(bool $useDemo = false)
    {
        if ($useDemo) {
            return;
        }

        Notification::create([
            'enabled'     => true,
            'code'        => 'webbook.mall::admin.checkout_succeeded',
            'name'        => trans('webbook.mall::demo.notifications.admin_checkout_succeeded.name'),
            'description' => trans('webbook.mall::demo.notifications.admin_checkout_succeeded.description'),
            'template'    => 'webbook.mall::mail.admin.checkout_succeeded',
        ]);

        Notification::create([
            'enabled'     => true,
            'code'        => 'webbook.mall::admin.checkout_failed',
            'name'        => trans('webbook.mall::demo.notifications.admin_checkout_failed.name'),
            'description' => trans('webbook.mall::demo.notifications.admin_checkout_failed.description'),
            'template'    => 'webbook.mall::mail.admin.checkout_failed',
        ]);

        Notification::create([
            'enabled'     => true,
            'code'        => 'webbook.mall::customer.created',
            'name'        => trans('webbook.mall::demo.notifications.customer_created.name'),
            'description' => trans('webbook.mall::demo.notifications.customer_created.description'),
            'template'    => 'webbook.mall::mail.customer.created',
        ]);

        Notification::create([
            'enabled'     => true,
            'code'        => 'webbook.mall::checkout.succeeded',
            'name'        => trans('webbook.mall::demo.notifications.checkout_succeeded.name'),
            'description' => trans('webbook.mall::demo.notifications.checkout_succeeded.description'),
            'template'    => 'webbook.mall::mail.checkout.succeeded',
        ]);

        Notification::create([
            'enabled'     => true,
            'code'        => 'webbook.mall::checkout.failed',
            'name'        => trans('webbook.mall::demo.notifications.checkout_failed.name'),
            'description' => trans('webbook.mall::demo.notifications.checkout_failed.description'),
            'template'    => 'webbook.mall::mail.checkout.failed',
        ]);

        Notification::create([
            'enabled'     => true,
            'code'        => 'webbook.mall::order.shipped',
            'name'        => trans('webbook.mall::demo.notifications.order_shipped.name'),
            'description' => trans('webbook.mall::demo.notifications.order_shipped.description'),
            'template'    => 'webbook.mall::mail.order.shipped',
        ]);

        Notification::create([
            'enabled'     => true,
            'code'        => 'webbook.mall::order.state.changed',
            'name'        => trans('webbook.mall::demo.notifications.order_state_changed.name'),
            'description' => trans('webbook.mall::demo.notifications.order_state_changed.description'),
            'template'    => 'webbook.mall::mail.order.state_changed',
        ]);

        Notification::create([
            'enabled'     => true,
            'code'        => 'webbook.mall::payment.paid',
            'name'        => trans('webbook.mall::demo.notifications.payment_paid.name'),
            'description' => trans('webbook.mall::demo.notifications.payment_paid.description'),
            'template'    => 'webbook.mall::mail.payment.paid',
        ]);

        Notification::create([
            'enabled'     => true,
            'code'        => 'webbook.mall::payment.failed',
            'name'        => trans('webbook.mall::demo.notifications.payment_failed.name'),
            'description' => trans('webbook.mall::demo.notifications.payment_failed.description'),
            'template'    => 'webbook.mall::mail.payment.failed',
        ]);

        Notification::create([
            'enabled'     => true,
            'code'        => 'webbook.mall::payment.refunded',
            'name'        => trans('webbook.mall::demo.notifications.payment_refunded.name'),
            'description' => trans('webbook.mall::demo.notifications.payment_refunded.description'),
            'template'    => 'webbook.mall::mail.payment.refunded',
        ]);
    }
}

<?php

namespace WebBook\Mall\Classes\Jobs;

use App;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use WebBook\Mall\Models\Order;

/**
 * This Job sends the order confirmation to the customer. It also generates
 * a PDF invoice if the payment method requires one.
 */
class SendOrderConfirmationToCustomer
{
    public function fire(Job $job, $input)
    {
        if ($job->attempts() > 10) {
            logger()->critical(
                '[WebBook.Mall] Failed to send checkout confirmation mail after 10 attempts.',
                ['input' => $input]
            );
            $job->delete();
        }

        $order = Order::with(['customer', 'products'])->find($input['id']);
        $data  = [
            'order'       => $order,
            'account_url' => $input['account_url'],
            'order_url'   => $input['order_url'],
        ];

        App::setLocale($order->lang);
        Mail::send(
            $input['template'],
            $data,
            function (Message $message) use ($order) {
                $message->to($order->customer->user->email, $order->customer->name);

                $pdf = $order->getPDFInvoice();

                if ($order->is_paid && $pdf instanceof \Barryvdh\DomPDF\PDF) {
                    $file_name = trans('webbook.mall::lang.order.order_file_name', ['order' => $order->order_number]);
                    $message->attachData($pdf->output(), sprintf('%s.pdf', $file_name));
                }
            }
        );

        $job->delete();
    }
}

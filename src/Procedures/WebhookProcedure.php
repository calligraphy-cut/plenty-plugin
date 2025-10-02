<?php

namespace PaymentWebhookNotifier\Procedures;

use PaymentWebhookNotifier\Services\WebhookService;
use Plenty\Modules\EventProcedures\Events\EventProceduresTriggered;

/**
 * Event procedure to send webhook notifications
 */
class WebhookProcedure
{
    /**
     * Execute the webhook notification
     * @param EventProceduresTriggered $event
     * @param WebhookService $webhookService
     */
    public function execute(EventProceduresTriggered $event, WebhookService $webhookService)
    {
        $order = $event->getOrder();
        $webhookService->sendWebhook($order->id);
    }
}

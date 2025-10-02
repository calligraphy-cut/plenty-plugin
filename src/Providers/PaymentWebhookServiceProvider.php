<?php

namespace PaymentWebhookNotifier\Providers;

use PaymentWebhookNotifier\Procedures\WebhookProcedure;
use Plenty\Modules\EventProcedures\Services\Entries\ProcedureEntry;
use Plenty\Modules\EventProcedures\Services\EventProceduresService;
use Plenty\Plugin\ServiceProvider;

/**
 * Service provider for PaymentWebhookNotifier plugin
 */
class PaymentWebhookServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider
     */
    public function register()
    {
        // No additional registration needed here
    }

    /**
     * Boot the service provider
     * @param EventProceduresService $eventProceduresService
     */
    public function boot(EventProceduresService $eventProceduresService)
    {
        // Register event procedure
        $eventProceduresService->registerProcedure(
            'PaymentWebhookNotifier',
            ProcedureEntry::EVENT_TYPE_ORDER,
            [
                'de' => 'Webhook mit Order ID senden',
                'en' => 'Send webhook with order ID'
            ],
            WebhookProcedure::class . '@execute'
        );
    }
}

<?php

namespace PaymentWebhookNotifier\Services;

use Plenty\Modules\Plugin\Libs\Contracts\LibraryCallContract;
use Plenty\Plugin\ConfigRepository;
use Plenty\Plugin\Log\Loggable;

/**
 * Service for sending webhook notifications
 */
class WebhookService
{
    use Loggable;

    /**
     * @var ConfigRepository
     */
    private $config;

    /**
     * @var LibraryCallContract
     */
    private $libCall;

    /**
     * WebhookService constructor.
     * @param ConfigRepository $config
     * @param LibraryCallContract $libCall
     */
    public function __construct(
        ConfigRepository $config,
        LibraryCallContract $libCall
    ) {
        $this->config = $config;
        $this->libCall = $libCall;
    }

    /**
     * Send webhook notification with order ID
     * @param int $orderId
     * @return bool
     */
    public function sendWebhook(int $orderId): bool
    {
        try {
            // Check if webhook is enabled
            $enabled = $this->config->get('PaymentWebhookNotifier.webhook.enabled', true);
            if (!$enabled) {
                $this->getLogger(__METHOD__)->info('PaymentWebhookNotifier::Webhook', 'Webhook is disabled in config');
                return false;
            }

            // Get webhook URL from config
            $webhookUrl = $this->config->get('PaymentWebhookNotifier.webhook.url');
            if (empty($webhookUrl)) {
                $this->getLogger(__METHOD__)->error('PaymentWebhookNotifier::Webhook', 'Webhook URL is not configured');
                return false;
            }

            // Get timeout from config
            $timeout = (int) $this->config->get('PaymentWebhookNotifier.webhook.timeout', 30);

            // Get secret from config
            $secret = $this->config->get('PaymentWebhookNotifier.webhook.secret', '');

            // Prepare payload
            $payload = [
                'order_id' => $orderId,
                'secret' => $secret
            ];

            // Send HTTP POST request
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $webhookUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'User-Agent: PlentyMarkets-PaymentWebhookNotifier/1.0'
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                $this->getLogger(__METHOD__)->error('PaymentWebhookNotifier::Webhook', [
                    'message' => 'Curl error',
                    'error' => $error,
                    'orderId' => $orderId
                ]);
                return false;
            }

            if ($httpCode >= 200 && $httpCode < 300) {
                $this->getLogger(__METHOD__)->info('PaymentWebhookNotifier::Webhook', [
                    'message' => 'Webhook sent successfully',
                    'orderId' => $orderId,
                    'httpCode' => $httpCode
                ]);
                return true;
            } else {
                $this->getLogger(__METHOD__)->error('PaymentWebhookNotifier::Webhook', [
                    'message' => 'Webhook failed with non-2xx status',
                    'orderId' => $orderId,
                    'httpCode' => $httpCode,
                    'response' => $response
                ]);
                return false;
            }
        } catch (\Exception $e) {
            $this->getLogger(__METHOD__)->error('PaymentWebhookNotifier::Webhook', [
                'message' => 'Exception while sending webhook',
                'orderId' => $orderId,
                'exception' => $e->getMessage()
            ]);
            return false;
        }
    }
}

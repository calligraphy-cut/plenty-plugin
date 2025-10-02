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
     * Send webhook notification with order ID to all configured URLs
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

            // Get webhook URLs from config
            $webhookUrls = [];
            $url1 = $this->config->get('PaymentWebhookNotifier.webhook.url1');
            $url2 = $this->config->get('PaymentWebhookNotifier.webhook.url2');
            $url3 = $this->config->get('PaymentWebhookNotifier.webhook.url3');

            if (!empty($url1)) {
                $webhookUrls[] = $url1;
            }
            if (!empty($url2)) {
                $webhookUrls[] = $url2;
            }
            if (!empty($url3)) {
                $webhookUrls[] = $url3;
            }

            if (empty($webhookUrls)) {
                $this->getLogger(__METHOD__)->error('PaymentWebhookNotifier::Webhook', 'No webhook URLs configured');
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

            $allSuccess = true;

            // Send to all configured webhook URLs
            foreach ($webhookUrls as $index => $webhookUrl) {
                $success = $this->sendToUrl($webhookUrl, $payload, $timeout, $orderId, $index + 1);
                if (!$success) {
                    $allSuccess = false;
                }
            }

            return $allSuccess;
        } catch (\Exception $e) {
            $this->getLogger(__METHOD__)->error('PaymentWebhookNotifier::Webhook', [
                'message' => 'Exception while sending webhook',
                'orderId' => $orderId,
                'exception' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send webhook to a specific URL
     * @param string $webhookUrl
     * @param array $payload
     * @param int $timeout
     * @param int $orderId
     * @param int $urlIndex
     * @return bool
     */
    private function sendToUrl(string $webhookUrl, array $payload, int $timeout, int $orderId, int $urlIndex): bool
    {
        try {
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
                    'orderId' => $orderId,
                    'url' => $webhookUrl,
                    'urlIndex' => $urlIndex
                ]);
                return false;
            }

            if ($httpCode >= 200 && $httpCode < 300) {
                $this->getLogger(__METHOD__)->info('PaymentWebhookNotifier::Webhook', [
                    'message' => 'Webhook sent successfully',
                    'orderId' => $orderId,
                    'httpCode' => $httpCode,
                    'url' => $webhookUrl,
                    'urlIndex' => $urlIndex
                ]);
                return true;
            } else {
                $this->getLogger(__METHOD__)->error('PaymentWebhookNotifier::Webhook', [
                    'message' => 'Webhook failed with non-2xx status',
                    'orderId' => $orderId,
                    'httpCode' => $httpCode,
                    'response' => $response,
                    'url' => $webhookUrl,
                    'urlIndex' => $urlIndex
                ]);
                return false;
            }
        } catch (\Exception $e) {
            $this->getLogger(__METHOD__)->error('PaymentWebhookNotifier::Webhook', [
                'message' => 'Exception while sending webhook',
                'orderId' => $orderId,
                'url' => $webhookUrl,
                'urlIndex' => $urlIndex,
                'exception' => $e->getMessage()
            ]);
            return false;
        }
    }
}

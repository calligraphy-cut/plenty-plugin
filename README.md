# Webhook Notifier

A simple plentymarkets plugin that sends webhook notifications with order IDs.

## Features

- Sends HTTP POST webhook with order ID to a configured URL
- Triggered via event procedures (you control when it fires)
- Configurable webhook URL and timeout
- Enable/disable webhook functionality
- Comprehensive logging for debugging

## Installation

1. Upload the plugin to your plentymarkets system
2. Deploy the plugin in the plugin set
3. Configure the webhook URL in the plugin settings

## Configuration

Configure the plugin in **Plugins → Plugin overview → PaymentWebhookNotifier → Configuration**:

- **Webhook URL**: The endpoint URL where webhook notifications will be sent (required)
- **Webhook Secret**: A secret key sent with each webhook for verification (optional)
- **Webhook Timeout**: Maximum time in seconds to wait for webhook response (default: 30)
- **Enable Webhook**: Toggle to enable or disable webhook notifications

## Setting Up Event Procedures

1. Go to **Setup → Orders → Events**
2. Create a new event procedure
3. Select your trigger event (e.g., "Payment: Fully paid", "Status change", etc.)
4. Add filters if needed
5. Add the procedure **"Send webhook with order ID"**
6. Save the event procedure

You can create multiple event procedures for different triggers (payment changes, status changes, etc.)

## Webhook Payload

The plugin sends a simple JSON payload:

```json
{
  "order_id": 12345,
  "secret": "your_configured_secret"
}
```

The secret can be used to verify that the webhook came from your plentymarkets system.

## Requirements

- plentymarkets 7.0 or higher
- PHP 8.0 or higher

## Support

For issues or questions, check the plugin logs in your plentymarkets system under **Data → Log**.

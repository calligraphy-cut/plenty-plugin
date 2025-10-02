# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

A simple plentymarkets plugin that sends webhook notifications with order IDs. Users configure event procedures in the plentymarkets admin to trigger the webhook. Written in PHP 8.0.

## Project Structure

```
plenty-plugin/
├── plugin.json                           # Plugin metadata and configuration
├── config.json                           # User-configurable settings (webhook URL, timeout)
├── src/
│   ├── Providers/
│   │   └── PaymentWebhookServiceProvider.php  # Registers event procedure
│   ├── Procedures/
│   │   └── WebhookProcedure.php              # Event procedure handler
│   └── Services/
│       └── WebhookService.php                 # Sends HTTP webhook notifications
└── README.md                             # User documentation
```

## Architecture

### Event Flow
1. **User Setup**: User creates event procedure in plentymarkets admin (Setup → Orders → Events)
2. **Event Trigger**: User-selected event fires (payment change, status change, etc.)
3. **Procedure Execution**: `WebhookProcedure::execute()` is called with order data
4. **Webhook Call**: Extracts order ID and calls `WebhookService::sendWebhook()`
5. **Logging**: All actions are logged for debugging

### Key Components

- **ServiceProvider**: Registers the event procedure "Send webhook with order ID"
- **WebhookProcedure**: Simple handler that extracts order ID from event and delegates to WebhookService
- **WebhookService**: Handles HTTP communication, configuration reading, error handling, and logging

### Event Procedures vs Event Listeners

This plugin uses **Event Procedures** (not Event Listeners):
- Event procedures are configured manually by users in the admin UI
- Users choose which events trigger the webhook (payment changes, status changes, etc.)
- More flexible - users control exactly when webhooks fire
- No hardcoded event listeners in the plugin code

## Development

### Plugin Configuration

The plugin uses plentymarkets' config.json for user settings:
- `webhook.url`: The HTTP endpoint to receive notifications (required)
- `webhook.secret`: A secret key for webhook verification (optional)
- `webhook.timeout`: Maximum seconds to wait for response (default: 30)
- `webhook.enabled`: Boolean to enable/disable webhooks (default: true)

### Webhook Payload

Sends simple JSON POST to configured URL:
```json
{
  "order_id": 12345,
  "secret": "configured_secret_value"
}
```

The secret allows the receiving backend to verify the webhook came from the configured plentymarkets system.

### Testing

To test the plugin in a plentymarkets environment:
1. Configure webhook URL in plugin settings
2. Deploy the plugin
3. Create an event procedure (Setup → Orders → Events)
4. Select trigger event and add procedure "Send webhook with order ID"
5. Trigger the event (e.g., create order, change status, add payment)
6. Check logs in Data → Log for webhook execution details
7. Verify webhook was received at your configured endpoint

## plentymarkets Plugin Conventions

- PHP namespace must match plugin name in plugin.json
- ServiceProvider must be registered in plugin.json
- Event procedures are registered using EventProceduresService in ServiceProvider's boot() method
- Use ProcedureEntry::EVENT_TYPE_ORDER for order-related procedures
- Use Loggable trait for logging
- Use ConfigRepository for reading plugin configuration
- EventProceduresTriggered event provides access to order data via `getOrder()`

# Forms To HubSpot Connector

**Seamlessly connect your Statamic forms to HubSpot** - Automatically create and update contacts in HubSpot with advanced field mapping and comprehensive error handling.

## 🔗 Related Connectors

This connector is part of the **Forms To Wherever** ecosystem. Check out our other connectors:

- **[Forms To Wherever](https://statamic.com/addons/stokoe/forms-to-wherever)** - Base package (required)
- **[Forms To Mailchimp](https://statamic.com/addons/stokoe/forms-to-mailchimp-connector)** - Mailchimp email marketing
- **[Forms To ConvertKit](https://statamic.com/addons/stokoe/forms-to-convertkit-connector)** - ConvertKit email marketing
- **[Forms To ActiveCampaign](https://statamic.com/addons/stokoe/forms-to-activecampaign-connector)** - ActiveCampaign automation
- **[Forms To Salesforce](https://statamic.com/addons/stokoe/forms-to-salesforce-connector)** - Salesforce CRM integration

## Features

- **Automatic contact creation/updates** in HubSpot CRM
- **Custom field mapping** to HubSpot contact properties
- **Duplicate handling** - Updates existing contacts by email
- **Comprehensive error handling** with detailed logging
- **Production-ready** with security and reliability built-in

## Requirements

- [Forms To Wherever](https://statamic.com/addons/stokoe/forms-to-wherever) base addon
- HubSpot account with private app access
- PHP 8.2+
- Statamic 5.0+

## Installation

1. Install the base Forms To Wherever addon:
```bash
composer require stokoe/forms-to-wherever
```

2. Install this HubSpot connector:
```bash
composer require stokoe/forms-to-hubspot-connector
```

## Configuration

### 1. Create a HubSpot Private App

1. In HubSpot, go to **Settings → Integrations → Private Apps**
2. Click **Create a private app**
3. Give it a name (e.g., "Statamic Forms Integration")
4. In the **Scopes** tab, select:
   - `crm.objects.contacts.read`
   - `crm.objects.contacts.write`
5. Click **Create app** and copy the access token

### 2. Configure Your Form

Add the `form_connectors` field to your form blueprint:

```yaml
fields:
  # Your existing form fields...
  -
    handle: connectors
    field:
      type: form_connectors
      display: Form Connectors
```

### 3. Enable HubSpot in Control Panel

1. Edit your form in the Statamic Control Panel
2. Navigate to the "Form Connectors" section
3. Enable the **HubSpot** connector
4. Configure the settings:
   - **Access Token**: Your HubSpot private app access token
   - **Email Field**: Form field containing email (default: `email`)
   - **Create Contact**: Enable to create/update contacts
   - **Field Mapping**: Map form fields to HubSpot properties

## Field Mapping

Map your form fields to HubSpot contact properties:

| Form Field | HubSpot Property | Description |
|------------|------------------|-------------|
| `first_name` | `firstname` | First name |
| `last_name` | `lastname` | Last name |
| `phone` | `phone` | Phone number |
| `company` | `company` | Company name |
| `website` | `website` | Website URL |
| `job_title` | `jobtitle` | Job title |

### Custom Properties

You can create custom properties in HubSpot and map them:

1. In HubSpot: **Settings → Properties → Contact properties**
2. Create your custom property and note the internal name
3. Map your form field to the custom property in the connector settings

## Example Form

```yaml
# resources/forms/contact.yaml
title: Contact Form
fields:
  -
    handle: email
    field:
      type: email
      display: Email Address
      validate: required|email
  -
    handle: first_name
    field:
      type: text
      display: First Name
      validate: required
  -
    handle: last_name
    field:
      type: text
      display: Last Name
      validate: required
  -
    handle: company
    field:
      type: text
      display: Company
  -
    handle: phone
    field:
      type: text
      display: Phone Number
  -
    handle: connectors
    field:
      type: form_connectors
      display: Form Connectors
```

## How It Works

1. **Contact Creation**: When a form is submitted, the connector attempts to create a new contact
2. **Duplicate Handling**: If a contact with the same email exists (409 error), it searches for and updates the existing contact
3. **Field Mapping**: Maps form fields to HubSpot properties based on your configuration
4. **Error Handling**: Logs all API responses and errors for debugging

## Error Handling

The connector includes comprehensive error handling:

- **Missing access tokens** - Logs warning and skips processing
- **Invalid email addresses** - Validates emails before sending
- **API failures** - Logs detailed error information with context
- **Network timeouts** - 10-second timeout with graceful failure
- **Duplicate contacts** - Automatically updates existing contacts

All errors are logged to your Laravel log files for debugging.

## Asynchronous Processing

By default, HubSpot API calls are processed asynchronously using Laravel queues to prevent form submission delays. Ensure your queue worker is running:

```bash
php artisan queue:work
```

To process synchronously (not recommended for production), disable async processing in the form connector settings.

## Troubleshooting

### Common Issues

**"Missing access token"**
- Verify your private app access token is correctly entered
- Ensure the private app has the required scopes

**"Invalid or missing email"**
- Check the "Email Field" setting matches your form field handle
- Ensure the email field contains a valid email address

**"API error 401"**
- Your access token may be invalid or expired
- Verify the private app is still active in HubSpot

**"API error 403"**
- The access token doesn't have required permissions
- Check the private app scopes include contact read/write access

### Debug Logging

Enable debug logging to see detailed API interactions:

```php
// In your .env file
LOG_LEVEL=debug
```

Check `storage/logs/laravel.log` for detailed connector activity.

## Security

- Access tokens are never logged or exposed in error messages
- All API communications use HTTPS
- Email addresses are validated before sending to HubSpot
- Comprehensive input sanitization and validation

## Available Connectors

- **[Mailchimp Connector](https://statamic.com/addons/stokoe/forms-to-mailchimp-connector)** - Connect forms to Mailchimp lists

## Support

- **Marketplace**: [Forms To HubSpot Connector](https://statamic.com/addons/stokoe/forms-to-hubspot-connector)
- **Base Addon**: [Forms To Wherever](https://statamic.com/addons/stokoe/forms-to-wherever)
- **HubSpot API**: [Official Documentation](https://developers.hubspot.com/docs/api/crm/contacts)

## License

MIT License - Build amazing things with it!

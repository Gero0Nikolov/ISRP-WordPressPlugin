# Security Review

This release audits input handling and hardens the Infinite Scroll Random Post plugin.

## Mitigations
- Enforced capability checks before database setup.
- Added nonce and same-host referrer validation for the `isrp_ll_get_post` AJAX endpoint.
- Sanitized and validated all incoming data, including server variables and post IDs.
- Escaped all dynamic output using `esc_url_raw` and WordPress JSON helpers.
- Replaced direct output with `wp_send_json` for safer responses.
- Introduced a localized nonce for JavaScript requests.

## Testing
- `php -l isrp.php`
- `node --check assets/public.js`

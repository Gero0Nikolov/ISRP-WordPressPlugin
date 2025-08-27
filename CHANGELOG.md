# Changelog

## Fixed
- Resolved `wp_localize_script` misuse by moving runtime data to inline JSON and limiting localization to strings.
- Prevented JSON parsing errors in frontend by consuming AJAX responses as native objects.

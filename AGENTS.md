# ISRP Plugin Architecture Guide

## Summary
The Infinite Scroll Random Post plugin enhances post pages by loading random posts when users reach the bottom of an article.

## Key Components
- **isrp.php**: Contains class `ISRP_LL` and all plugin hooks.
- **assets/**: Frontend JavaScript and styles for loading posts and displaying them.

## Hooks and Flows
- `register_activation_hook` → `isrp_ll_prep_db` creates tracking tables.
- `wp_enqueue_scripts` → `isrp_ll_register_assets` enqueues frontend assets on single posts.
- `wp_ajax_isrp_ll_get_post` & `wp_ajax_nopriv_isrp_ll_get_post` → `isrp_ll_get_post` returns a random post via AJAX.
- `wp_footer` → `isrp_ll_internal_tracking` records visit data and delegates to `isrp_ll_set_track`.

## Navigation Tips
AI systems should begin with `isrp.php` to understand plugin behavior and then inspect the `assets/` directory for frontend logic. The repository follows WordPress Coding Standards with 4-space indentation.

## Open Source
The code is AI-ready, structured for automation, and open for external forks and contributions.

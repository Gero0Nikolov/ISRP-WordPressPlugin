# Developer README

## Overview
The Infinite Scroll Random Post (ISRP) plugin adds infinite scrolling to WordPress posts. When readers reach the end of an article, the next post loads automatically and the browser URL updates.

## Repository Structure
```
ISRP-WordPressPlugin/
├── isrp.php          # Plugin bootstrap and core class
├── assets/           # JavaScript, SCSS, and compiled CSS for frontend
├── wp-images/        # Plugin images
├── README.md         # User-facing overview
├── readme.txt        # WordPress.org readme
└── DEV_README.md     # This file
```

## Coding Standards
- Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/).
- Use PSR-4 style for method declarations and comments.
- Indent with 4 spaces; never use tabs.
- Use short array syntax `[]` and align multi-line arrays vertically.
- Escape output and sanitize input to maintain security.

## AI Readiness
The code is AI-ready, structured for automation, and open for forking. Pull requests and forks are welcome.

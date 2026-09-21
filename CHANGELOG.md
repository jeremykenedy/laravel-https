# Changelog

## Unreleased

- Load published configuration while preserving the `LaravelHttps` config namespace and its precedence.
- Load published lowercase view and translation directories while retaining existing view overrides and translation keys.
- Return the configured HTTP denial when a matching error view is missing.
- Allow applications to opt into a denial status for HTML responses with `LARAVEL_HTTPS_HTML_STATUS`. The default remains 200 for compatibility.
- Register middleware aliases on Laravel 5.1, which predates middleware groups.
- Add regression tests and a CI matrix covering Laravel 5.1 through 13 and PHP 7.2 through 8.5.
- Add Pint, dependency auditing, and light and dark README banners.
- Update documentation and the license year.

The default 302 redirect, production environment check, JSON payload, existing error page, publish tag, and PHP requirement are unchanged. Published overrides that were previously ignored now take effect.

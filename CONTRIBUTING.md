# Contributing

Thanks for your interest in Laravel Swagger Routebook.

This project is public, but changes should go through pull requests so the package remains stable for users.

## Development Setup

```bash
composer install
```

Run the checks before opening a pull request:

```bash
composer validate --strict
vendor/bin/phpunit
```

You can also lint PHP files with:

```bash
git ls-files "*.php" | xargs -n 1 php -l
```

On Windows PowerShell:

```powershell
git ls-files *.php | ForEach-Object { php -l $_ }
```

## Pull Request Rules

- Keep pull requests focused on one change.
- Add or update tests for behavior changes.
- Update the README when public annotations, attributes, commands, or config change.
- Do not commit `vendor/`, local caches, generated files, IDE files, or secrets.
- Prefer backwards-compatible changes.
- Breaking changes must be clearly explained and should target a new major version.
- Pull requests must pass CI before they can be merged.
- Maintainer approval is required before merging.

## Commit Style

Use short, descriptive commit messages:

```text
Add Postman export route
Fix FormRequest schema generation
Document bearer auth config
```

## Maintainer Review

Maintainers may ask for changes before merging. A PR can be closed if it is inactive, too broad, or does not fit the package goals.

Repository protection recommendations are documented in [GITHUB_SETUP.md](GITHUB_SETUP.md).

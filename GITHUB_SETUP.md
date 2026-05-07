# GitHub Repository Setup

These settings help keep the public repository safe while still accepting community contributions.

## Branch Protection

Go to:

```text
Settings > Rules > Rulesets
```

Create a ruleset for:

```text
main
```

Recommended rules:

- Restrict deletions: enabled
- Require a pull request before merging: enabled
- Required approvals: 1
- Dismiss stale pull request approvals when new commits are pushed: enabled
- Require review from Code Owners: enabled
- Require conversation resolution before merging: enabled
- Require status checks to pass: enabled
- Require branches to be up to date before merging: enabled
- Block force pushes: enabled
- Require linear history: optional, but recommended

Required status checks:

```text
PHP 8.1 / Laravel 10
PHP 8.2 / Laravel 11
PHP 8.2 / Laravel 12
PHP 8.3 / Laravel 12
Dependency Review
```

If GitHub shows slightly different check names, choose the checks created by the `CI` workflow.

## Tag Protection

Create another ruleset for tags:

```text
v*
```

Recommended rules:

- Restrict deletions: enabled
- Block force pushes: enabled
- Restrict updates: enabled

Only maintainers should create release tags.

## Security Features

Go to:

```text
Settings > Code security and analysis
```

Enable:

- Dependency graph
- Dependabot alerts
- Dependabot security updates
- Secret scanning
- Push protection
- Private vulnerability reporting

## GitHub Actions

Go to:

```text
Settings > Actions > General
```

Recommended settings:

- Allow GitHub Actions and reusable workflows
- Allow actions created by GitHub
- Allow actions by verified creators
- Require approval for first-time contributors

## Packagist Auto Update

On Packagist, enable the GitHub hook so the package updates whenever you push commits or tags.

Package URL:

```text
https://packagist.org/packages/kadonix/laravel-swagger-routebook
```

## Maintainer Workflow

Use this flow for releases:

```bash
composer validate --strict
vendor/bin/phpunit
git tag v1.0.1
git push origin main
git push origin v1.0.1
```

Packagist should update automatically after the tag is pushed.


# Sylius Sandbox Payment Plugin

[![Build](https://github.com/steveboulay/SyliusSandboxPaymentPlugin/actions/workflows/build.yaml/badge.svg?branch=main)](https://github.com/steveboulay/SyliusSandboxPaymentPlugin/actions/workflows/build.yaml)

A simulated payment provider for Sylius 2. It lets you test the whole checkout flow without an account at a real payment provider: hosted payment page, signed webhooks and refunds.

> **Status:** under development, not released yet. This plugin is a testing tool: never use it in production.

## Features

Available now:

- **Sandbox payment method in the admin panel.** "Sandbox (simulated payments)" appears in the list of gateways when you create a payment method. Its configuration form has one required field, the webhook secret. Labels are translated into English and French.
- **Webhook signature.** `WebhookSignature` signs a payload with HMAC-SHA256 and verifies incoming signatures. The header has the form `t=<timestamp>,v1=<signature>`. The timestamp is part of the signed data, and a signature more than 5 minutes older or newer than the current time is rejected, which limits replay attacks. The tolerance can be changed. Signatures are compared with `hash_equals()`.
- **Redirect to a simulated payment page.** When a customer pays with Sandbox, they are redirected to a page that shows the amount to pay, like the hosted page of a real payment provider.

The next features are listed in the [roadmap](#roadmap).

## Design

The code is split by responsibility:

| Folder | Role | Depends on Sylius |
|---|---|---|
| `src/Core` | Business rules in plain PHP, such as the webhook signature | No |
| `src/Command`, `src/CommandProvider`, `src/CommandHandler` | Payment Request flow: commands, the providers that create them, and their handlers | Yes |
| `src/OrderPay` | HTTP responses sent to the customer during payment, such as the redirect to the simulated page | Yes |
| `src/Controller`, `templates/shop` | Simulated payment page | Yes |
| `src/Form`, `templates/admin` | Payment method configuration in the admin panel | Yes |
| `config`, `translations` | Service wiring, routes, Twig hooks and translations | Yes |

Keeping `src/Core` free of any Sylius dependency makes it simple to unit test, and it could be reused in a plugin for another e-commerce platform.

The payment flow uses the Payment Request system introduced in Sylius 2.0, rather than Payum.

## Roadmap

- [x] Test application and continuous integration
- [x] Webhook signature
- [x] Payment method in the admin panel
- [x] Redirect to a simulated payment page
- [ ] Simulated payment page: accept, refuse or cancel, then return to the shop (in progress)
- [ ] Payment status update and end-to-end scenarios
- [ ] Webhook handling: replayed, forged and out-of-order notifications
- [ ] First release (v0.1.0)
- [ ] Refunds from the admin panel
- [ ] Eligibility rules and payment in installments

## Compatibility

| | Versions |
|---|---|
| PHP | 8.3 |
| Sylius | 2.2, 2.3 |
| Symfony | 7.4 |

The CI runs on Sylius 2.2 and 2.3 with MySQL 8.4, and on Sylius 2.3 with PostgreSQL 17.

## Development

You need Docker with Docker Compose, and Make. PHP, Composer and Node.js run in containers, so you do not need to install them. On Windows, use WSL 2 and keep the project in the WSL file system rather than under `/mnt/c`, where Docker volumes are much slower.

```bash
make init
make database-init
make load-fixtures
docker compose exec php vendor/bin/console sylius:payment:generate-key
```

- The shop is available at http://localhost, and the admin panel at http://localhost/admin (login `sylius`, password `sylius`).
- Sylius encrypts the configuration of payment methods. The last command creates the key it uses, in `vendor/sylius/test-application/config/encryption/`. If the key disappears after reinstalling dependencies, run the command again.
- `make php-shell` opens a shell in the PHP container, to run `php`, `composer` or `vendor/bin/console`.

## Quality checks

| Command | What it checks |
|---|---|
| `make ecs` | Coding standard (Sylius Coding Standard) |
| `make phpstan` | Static analysis with PHPStan, at the maximum level |
| `make lint` | Syntax of Twig templates and YAML files |
| `make phpunit` | Unit and integration tests |
| `make behat` | End-to-end scenarios (none yet) |

GitHub Actions runs these checks, along with Composer and service container validation, on every pull request and on every push to `main`.

## Workflow

Each change is made on its own branch and checked by the CI in a pull request. Pull requests are merged into `main` with a merge commit rather than squashed, so that the history keeps each step of the work, for example a failing test followed by the code that makes it pass. Commit messages follow [Conventional Commits](https://www.conventionalcommits.org/).

## License

MIT. See [LICENSE](LICENSE).

# Sylius Sandbox Payment Plugin

A simulated payment gateway for Sylius 2. It lets you test checkout flows without a payment provider account: hosted payment page, signed webhooks and refunds.

> **Status:** early development, not usable yet. This plugin is a testing tool: never use it in production.

## Roadmap

- [x] Test application and CI
- [ ] Webhook signature
- [ ] Payment method in the admin panel
- [ ] Capture and hosted payment page
- [ ] Webhook handling
- [ ] Refunds
- [ ] Eligibility rules and payment in installments

## Development

You need Docker, Docker Compose and Make.

```bash
make init
make database-init
make load-fixtures
```

The test shop is then available at http://localhost.

```bash
make ecs
make phpstan
make phpunit
make behat
```

## License

MIT. See [LICENSE](LICENSE).

# 0002. Keep business rules in a Sylius-free `src/Core` folder

- Status: Accepted
- Date: 2026-11-03

## Context

The plugin contains two kinds of code:

- **Rules that do not need Sylius**, such as signing a webhook payload and checking an incoming signature. The roadmap adds more of them: replayed, forged and out-of-order notifications.
- **Code tied to Sylius**: commands, handlers and providers of the Payment Request flow, the configuration form, the Twig templates and the service wiring.

When rules are mixed with Sylius code, testing them means booting the test application, which is slow and hides the cases that matter behind setup code.

Options considered:

1. **Everything in Sylius services.** Simple at first, but each rule can only be tested through Sylius.
2. **A `src/Core` folder in plain PHP**, with no dependency on Sylius or Symfony. The rest of the plugin calls it.
3. **A separate Composer package for the rules.** Cleaner separation, but a second repository, a second CI and a release process, which is too much for a few classes.

## Decision

Business rules go in `src/Core`, written in plain PHP. A class in `src/Core` does not import any Sylius or Symfony class. The first one is `Core\Webhook\WebhookSignature`, with its exception `InvalidWebhookSignatureException`.

The other folders (`Command`, `CommandProvider`, `CommandHandler`, `OrderPay`, `Controller`, `Form`) adapt these rules to Sylius. The README describes this split.

## Consequences

- Core classes are tested with plain unit tests in `tests/Unit/Core`, which run in milliseconds and read like a list of cases.
- The rules could be reused in a plugin for another e-commerce platform.
- The boundary is only a convention: nothing in the CI stops a Core class from importing a Sylius class. A dependency check, for example with Deptrac, would enforce it. Not done yet.
- Some values have to cross the boundary as plain parameters. For example, `WebhookSignature::verify()` receives the current time as an integer instead of reading the clock (see [0003](0003-sign-webhooks-with-hmac-sha256-and-a-timestamp.md)).

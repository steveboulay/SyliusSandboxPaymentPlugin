# 0001. Use Payment Request instead of Payum

- Status: Accepted
- Date: 2026-11-09

## Context

The plugin must plug a simulated payment provider into the Sylius 2 checkout. Sylius 2 offers two ways to do this:

- **Payum**, the payment library Sylius has used for years, still available in Sylius 2 through `sylius/payum-bundle`. Payum works with gateways, actions and requests, and some of its flows need the HTTP request and the session, which makes them hard to use from the API.
- **Payment Request**, added in Sylius 2.0. Each payment action (capture, status, and so on) becomes a command sent on a Symfony Messenger bus and handled by a dedicated handler. The system is stateless, so the same flow can serve the shop pages and the API.

Options considered:

1. **A Payum gateway.** Many existing plugins and examples use it, but it brings Payum's own abstractions, and the API case stays awkward.
2. **Payment Request only.** Built on Symfony components the project already uses: Messenger, the state machine and the router.
3. **Both.** Twice the code to write and test for a testing tool, with no user asking for Payum support.

## Decision

The plugin uses Payment Request only.

For the capture action:

- `CapturePaymentRequestCommandProvider` turns a payment request into a `CapturePaymentRequest` command.
- `CapturePaymentRequestHandler` handles it on the `sylius.payment_request.command_bus` bus. It stores the URL of the simulated payment page in the response data and applies the `process` transition.
- `CaptureHttpResponseProvider` redirects the customer to that URL with a `303 See Other` response.

The services are registered for the `sandbox` gateway factory only, through `ActionsCommandProvider` and `ActionsHttpResponseProvider`, so other payment methods of the shop are not affected.

## Consequences

- Each step is a small class with a single job, unit tested without booting Sylius.
- The plugin relies on the payment system added in Sylius 2.0 rather than on Payum's abstractions.
- Because Payment Request is stateless, an API checkout is possible in principle. It is not tested yet.
- The plugin cannot run on Sylius 1.x. This matches the `sylius/sylius: ^2.2` requirement.
- Payment Request has fewer examples than Payum, so some parts were written by reading the Sylius source code.

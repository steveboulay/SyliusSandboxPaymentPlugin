# 0003. Sign webhooks with HMAC-SHA256 and a timestamp

- Status: Accepted
- Date: 2026-11-03

## Context

Like a real payment provider, the sandbox will notify the shop when a payment changes state, by sending webhooks. The shop must be able to check three things before trusting a notification:

- it comes from the sandbox and not from someone else;
- its content was not changed on the way;
- it is not an old notification sent again later.

Options considered:

1. **No check.** Acceptable for a demo, but the plugin is meant to show how a real integration must behave.
2. **A shared token in a header.** Proves the sender knows the secret, but does not protect the content, and anyone who sees one request can reuse the token.
3. **HMAC-SHA256 of the payload only.** Protects the content, but a captured request stays valid forever.
4. **HMAC-SHA256 of a timestamp and the payload.** Protects the content and limits replays to a time window. Stripe uses the same scheme, so many developers already know it.
5. **An asymmetric signature (Ed25519, RSA).** The shop would only need a public key, but key management is heavier than the needs of a testing tool.

## Decision

Webhooks are signed with option 4, in `Core\Webhook\WebhookSignature`:

- The signed data is `<timestamp>.<payload>`, with the timestamp in seconds.
- The header has the form `t=<timestamp>,v1=<signature>`, where the signature is the hexadecimal HMAC-SHA256. A header that does not match this exact form is rejected.
- A signature whose timestamp is more than 300 seconds away from the current time, before or after, is rejected. The tolerance can be changed through the constructor.
- Signatures are compared with `hash_equals()`, which takes the same time whatever the input, so the comparison does not leak information.
- An empty secret or a negative tolerance is refused when the object is built.
- `verify()` receives the current time as a parameter, so tests control the clock.

The secret is the required field of the Sandbox payment method in the admin panel. Sylius encrypts the configuration of payment methods, so the secret is not stored in clear text.

## Consequences

- A forged or modified notification is rejected, and so is an old one replayed outside the window.
- The `v1` prefix leaves room for another algorithm later without breaking existing shops.
- A notification can still be replayed within the 5-minute window. Rejecting duplicates by notification ID is planned in the roadmap ("replayed notifications").
- The clocks of the sandbox and the shop must be roughly in sync.
- Changing the secret makes the notifications already sent with the old one fail. Accepting two secrets during a change is not supported.

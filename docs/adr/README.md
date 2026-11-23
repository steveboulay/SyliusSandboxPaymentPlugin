# Architecture Decision Records

This folder records the main technical decisions of the plugin: what was decided, why, and what it costs. Each record is a short Markdown file, numbered in the order the decisions were made.

## Records

| # | Decision | Status |
|---|---|---|
| [0001](0001-use-payment-request-instead-of-payum.md) | Use Payment Request instead of Payum | Accepted |
| [0002](0002-keep-business-rules-in-a-sylius-free-core.md) | Keep business rules in a Sylius-free `src/Core` folder | Accepted |
| [0003](0003-sign-webhooks-with-hmac-sha256-and-a-timestamp.md) | Sign webhooks with HMAC-SHA256 and a timestamp | Accepted |
| [0004](0004-merge-pull-requests-with-a-merge-commit.md) | Merge pull requests with a merge commit | Accepted |

## Adding a record

1. Copy [template.md](template.md) to `NNNN-short-title.md`, with the next number.
2. Fill in the context, the options considered, the decision and its consequences.
3. Add the record to the table above, in the same pull request as the change it describes when possible.

An accepted record is not rewritten. If a decision changes, write a new record and set the old one to "Superseded by NNNN".

The format follows the one described by Michael Nygard in [Documenting Architecture Decisions](https://cognitect.com/blog/2011/11/15/documenting-architecture-decisions).

# Security Policy

## Reporting a Vulnerability

If you believe you have found a security issue in `luchaninov/nicnames-client` —
for example, a webhook signature-verification bypass, an authentication-handling
flaw, or anything that could allow an attacker to read or modify another
user's domain registration data — please report it privately rather than
opening a public GitHub issue.

Email **vladimir.luchaninov@gmail.com** with:

- A description of the issue and its impact.
- Steps to reproduce, or a minimal proof-of-concept.
- The version (commit SHA or tag) you tested against.
- Your name / handle if you'd like to be credited in the fix.

## Scope

In scope:

- Code in `src/` (transport, client, webhook helpers, DTOs, exceptions).
- The example scripts in `examples/` to the extent that they demonstrate
  unsafe usage by default.

Out of scope:

- Vulnerabilities in upstream dependencies (`symfony/http-client`, `psr/log`)
  — please report those to the relevant project. We'll bump the constraint
  once a fix is released.
- Issues in the Nicnames API itself — those belong to Nicnames.

## Hardening tips for users

- **Never log the full request body in production.** It can contain PII
  (registrant name, email, phone, postal address) and transfer auth codes.
- **Never disable the webhook timestamp freshness check** (`maxAgeSeconds: 0`)
  except in tests — it is the anti-replay guarantee.
- **Store your API key and webhook secret outside the repository** (env vars,
  secret manager). The included `.env.local.example` is gitignored on purpose.

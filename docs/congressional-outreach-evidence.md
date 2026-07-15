# Congressional outreach evidence

This foundation separates address evidence from sending. It does not schedule or send congressional outreach.

## Eligibility tiers

- **Eligible:** sourced, observed, redirected, replied, or manually confirmed addresses. A future sequence may use these subject to its cadence and office-level limits.
- **Limited:** guessed or manually entered addresses without stronger evidence. A future sequence may allow one capped, human-approved test message.
- **Blocked:** hard-bounced, unsubscribed, manually suppressed, or centrally suppressed addresses. No campaign or queued job may send to these addresses.

The eligibility service evaluates these rules at use time. Suppression is also checked when recipients are added to a campaign and immediately before Gmail delivery.

## Evidence semantics

- Gmail accepting a send records `send_accepted`; it does not prove delivery.
- Absence of a bounce can record `not_bounced`; it does not confirm identity.
- Clicks are weak evidence and do not promote an address by themselves.
- Human replies and manual confirmation are strong evidence.
- Hard bounces and direct unsubscribes create central suppressions.
- Newsletter subscriptions and newsletter-specific unsubscribes are separate events; topic-level preference enforcement will be added with sequences.
- Departure auto-replies provide evidence about staff status but do not automatically assign a replacement address to the departing profile.

## Next implementation increments

1. Connect outbound Gmail message IDs to address events and apply a seven-day `not_bounced` observation window.
2. Classify inbound replies, auto-replies, and unsubscribe requests into reviewable events.
3. Add topic/newsletter preferences without weakening the global suppression rule.
4. Create human-approved outreach sequences from congressional staff lists with low daily caps, office throttles, and pause controls.
5. Add privacy-conscious redirect links for click evidence; do not add open-tracking pixels.
6. Integrate newsletter subscription state from its source-of-truth provider.

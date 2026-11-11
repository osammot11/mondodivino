# Meta Pixel + Conversions API — HVCO USA

Implementation baseline: 23 August 2026. Default API version: Graph API `v26.0`.

## Event map

| Funnel action | Meta standard event | Browser Pixel | CAPI | Deduplication |
|---|---|---:|---:|---|
| Any rendered funnel page | `PageView` | After tracking consent | After tracking consent | Same generated `event_id` |
| Valid guide opt-in | `Lead` | On the destination page | After the successful database transaction | Same UUID |
| First valid Taste Profile completion | `CompleteRegistration` | On the destination page | After the first successful completion | Same UUID |
| Confirmed Cal.com booking | `Schedule` | When confirmed status is displayed | When the authenticated webhook confirms the booking | Stable `schedule_{booking_id}` ID |

Clicks and attempted submissions are deliberately not treated as conversions.

## Configuration

Set these production secrets outside version control:

```dotenv
META_PIXEL_ID=
META_CONVERSIONS_API_TOKEN=
META_GRAPH_API_VERSION=v26.0
META_PRIVACY_POLICY_URL=https://example.com/privacy
```

Optional validation and US privacy settings:

```dotenv
META_TEST_EVENT_CODE=
META_DATA_PROCESSING_OPTIONS=
META_DATA_PROCESSING_OPTIONS_COUNTRY=0
META_DATA_PROCESSING_OPTIONS_STATE=0
```

Use `META_TEST_EVENT_CODE` only while checking Test Events in Events Manager, then remove it. Configure Limited Data Use only after the business has determined the applicable state/privacy rules; it is not enabled by assumption.

## Consent behavior

- Meta scripts and cookies are not loaded before an affirmative choice.
- Declining does not block the guide, profile or booking.
- The choice can be reopened from **Privacy choices** on every funnel page.
- A later refusal clears `_fbp`/`_fbc` and, on signed lead pages, records revocation server-side so later webhook events are not sent.
- The public privacy page must explain Meta Business Tools, measurement/advertising purposes and available opt-out mechanisms before production launch.

## Data quality and minimization

- Pixel/CAPI pairs share both `event_name` and `event_id`.
- `_fbp`, `_fbc`, IP, user agent, source URL and referrer are supplied when legitimately available.
- Email, phone, name and address matching fields are normalized and SHA-256 hashed before entering the queued CAPI payload.
- Taste answers, wine memories, notes, budgets and Cal.com meeting URLs are never sent to Meta.
- Signed query parameters and opaque funnel tokens are stripped from `event_source_url`.
- The CAPI token is sent as a Bearer credential and is never exposed in Blade or JavaScript.

## Launch checklist

1. Create or select the correct Meta Dataset/Pixel in Events Manager.
2. Generate a Conversions API access token and store it in the deployment secret manager.
3. Publish and configure `META_PRIVACY_POLICY_URL`.
4. Run migrations and ensure a queue worker is active.
5. Temporarily set `META_TEST_EVENT_CODE` and complete one clean funnel journey.
6. Confirm Browser + Server reception and deduplication for all four event names.
7. Review Event Match Quality and Diagnostics; correct rejected parameters rather than adding unsupported data.
8. Clear `META_TEST_EVENT_CODE` before live traffic.
9. Verify domain ownership, Aggregated Event Measurement priorities and campaign conversion location in Meta Events Manager.
10. Reassess consent text and US state privacy settings with counsel/CMP configuration before launch.

## Primary references

- Meta Business SDK for PHP and current CAPI example: <https://github.com/facebook/facebook-php-business-sdk>
- Current SDK/API version source: <https://github.com/facebook/facebook-php-business-sdk/blob/main/src/FacebookAds/ApiConfig.php>
- Meta Business Tools Terms: <https://www.facebook.com/legal/terms/businesstools>
- Meta Business Tools explanation: <https://www.facebook.com/help/331509497253087/>

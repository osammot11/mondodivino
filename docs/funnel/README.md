# HVCO USA — milestones 1–3 runbook

Laravel/database is the source of truth for lead, taste-profile, and booking state. The funnel uses Blade and versioned static files in `public/css` and `public/js`; there is no Node or Vite build step.

## Local setup

1. Copy `.env.example` to `.env`, run `php artisan key:generate`, and configure only the values available for the current environment.
2. Run `php artisan migrate`.
3. Start the app with `php artisan serve` and a separate worker with `php artisan queue:work --tries=5`.
4. Open `/italy-beyond-famous-labels` and complete the opt-in and taste profile.

For safe local work, keep real provider secrets out of `.env` and do not run a queue worker. Database-backed jobs will remain queued and automated tests use mocked HTTP responses. Run the full suite with `php artisan test`.

## Cal.com embed configuration

- `CALCOM_EMBED_URL`: preferred full public event URL, for example `https://cal.com/username/private-wine-session`. This is the only setting required to render the inline calendar.
- `CALCOM_EVENT_TYPE_ID`: optional provider event-type ID, retained for environment documentation.
- `CALCOM_EVENT_TYPE_SLUG` plus `CALCOM_USERNAME`: alternative to the full URL for a personal event.
- `CALCOM_EVENT_TYPE_SLUG` plus `CALCOM_TEAM_SLUG`: alternative for a team event; generates `/team/{team}/{event}`.
- `CALCOM_WEBHOOK_SECRET`: long random signing secret configured identically on Cal.com.
- `CALCOM_API_VERSION`: webhook version, default `2021-10-20`.
- `CALCOM_SUPPORT_EMAIL`: monitored address displayed below the calendar.
- `CALCOM_BOOKING_DURATION`: approved display label such as `30 minutes`; leave empty until validated.

No Cal.com API key is needed at runtime. The browser loads Cal.com's official vanilla inline embed, prefills name/email, and passes the opaque `funnel_reference` metadata field. It never places PII in the fallback URL. A failed or blocked script exposes a direct, non-prefilled Cal.com link; missing configuration exposes a support fallback.

The client events `bookingSuccessfulV2` and `rescheduleBookingSuccessfulV2` only switch the page to “confirming” and start five status checks with increasing delay. They never update local funnel state. If confirmation does not arrive, the page tells the visitor to check their inbox.

## Cal.com webhook

Create an HTTPS webhook subscription pointing to:

`POST https://YOUR_HOST/webhooks/calcom`

Set the webhook secret to `CALCOM_WEBHOOK_SECRET`, keep the standard payload (do not configure a reduced custom template), and subscribe to the applicable provider triggers:

- `BOOKING_CREATED`, `BOOKING_RESCHEDULED`, `BOOKING_CANCELLED`, `BOOKING_REJECTED`;
- `BOOKING_NO_SHOW_UPDATED` and/or `AFTER_GUESTS_CAL_VIDEO_NO_SHOW`;
- `MEETING_ENDED` (or `BOOKING_COMPLETED` if exposed by the selected Cal.com API surface).

The endpoint verifies `x-cal-signature-256` as the lowercase hexadecimal HMAC-SHA256 of the exact raw request body. Missing secrets/signatures and mismatches return `401`; malformed JSON returns `400`; supported payloads missing a booking UID return `422`. Valid requests return `202` quickly and queue processing. Only this route is CSRF-exempt, and it is rate limited.

Replay protection uses a unique SHA-256 payload hash. The database stores a minimal audit row and an encrypted normalized subset needed by the worker—not the full webhook. That encrypted subset is cleared after processing, including for unmatched and stale events. Unknown but authentic event types are recorded as `ignored` without failing the provider delivery.

Correlation order is:

1. existing/current or previous Cal.com booking UID;
2. opaque `payload.metadata.funnel_reference` generated for the completed taste profile;
3. normalized attendee email as a compatibility fallback.

Reschedules update the same local booking and retain the previous UID. Older provider timestamps cannot overwrite newer state. Duplicate deliveries cannot duplicate local funnel events. A confirmed/rebooked session moves the lead to `CALL_BOOKED`; cancellation/rejection returns it to `TASTE_PROFILE_COMPLETE` unless another active booking exists; completion moves it to `CALL_COMPLETED`; no-show moves it to `NO_SHOW`.

## Booking data and privacy

The `bookings` table stores provider UID, local opaque reference, lifecycle state, UTC start/end, attendee timezone, provider timestamps, and only provider-supplied meeting/reschedule/cancel URLs. Attendee name, attendee email, and all actionable URLs use Laravel encrypted casts. No URL is derived or invented when Cal.com omits it.

The signed `/private-session/{token}/status` endpoint returns a minimal pending response until the webhook confirms the booking. After confirmation it returns time and only the available action URLs. Invalid/expired signatures and ineligible profiles do not expose booking data.

## Brevo lifecycle synchronization

After local processing succeeds, `SyncBookingToBrevo` asynchronously upserts these centrally mapped attributes:

- `FUNNEL_STATUS`, `BOOKING_STATUS`, `CALL_START_UTC`, `CALL_TIMEZONE`, `CALCOM_BOOKING_UID`.

It then tracks one of `call_booked`, `call_rescheduled`, `call_cancelled`, `call_show`, or `call_no_show`. Event properties contain only an idempotency key, status, UTC start, and timezone—never meeting links, attendee PII, or free-text taste answers. The job retries with backoff and logs only local IDs/event names on final failure.

Create the booking attributes in Brevo before production. Use the `call_booked` event to stop Sequence B and enter Sequence C. Use `call_rescheduled` to refresh all calendar-derived times, `call_cancelled` to stop scheduled reminders and return to the booking path, `call_show` to end Sequence C, and `call_no_show` to enter the C4/C5 recovery branch. Avoid duplicating Cal.com reminders when equivalent provider notifications are enabled.

## Existing opt-in and profile settings

The existing `BREVO_*`, `HVCO_SERVICEABLE_STATES`, `HVCO_MINIMUM_SET_BUDGET`, consent, signed-link, and guide settings remain as documented in `.env.example`. Brevo opt-in and profile jobs continue to omit free-text profile details. Under-21 and explicitly unserviceable profiles never reach the booking page.

Before production, validate the real Cal.com event URL, duration, availability, notice/buffer rules, supported US states, monitored support/reply-to addresses, legal copy, and Sequence C ownership. No live Cal.com/Brevo resources, credentials, webhooks, or email automations are created by this repository.

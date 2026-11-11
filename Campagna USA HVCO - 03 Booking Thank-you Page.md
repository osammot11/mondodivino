# MondoDiVino USA — Booking Thank-you Page

## Funzione della pagina

Seconda thank-you page. Il profilo è stato salvato; l'unica azione è scegliere uno slot sul calendario.

## Copy pagina

### Confirmation bar

**Your taste profile has been saved. One final step remains.**

### Headline

# Choose Your Private Session with Luca

### Subheadline

Select a convenient time below. Luca will review your answers before the call so you can spend the conversation exploring wines, regions and producers that make sense for you.

### Three reassurance bullets

- **Personal:** Luca reviews the profile you just completed.
- **Practical:** Leave with a clearer direction for your Italian wine selection.
- **No pressure:** The session is complimentary and there is no obligation to purchase.

### Calendar heading

## Select your date and time

`[EMBED CAL.COM CALENDAR]`

### Calendar setup

- Event name: `Private Italian Wine Selection Session with Luca Gradi`
- Duration: `[CONFIRM: XX minutes]`
- Time zone: automatically localized, visibly displayed
- Minimum notice: `[CONFIRM]`
- Buffer: `[CONFIRM]`
- Questions: do not repeat the taste form; ask only missing operational details
- Confirmation redirect: success state/page below

### Microcopy below calendar

Can't find a suitable time? Email `[SUPPORT EMAIL]` and we'll help.

## Booking confirmed state

### Headline

# You're Booked, [First Name]

### Body

Your session with Luca is confirmed for **[Date] at [Time] [Time Zone]**.

We've sent the calendar invitation and call link to **[Email]**. Add the event to your calendar now and keep an eye out for a short note from Luca before the session.

### What to prepare

You do not need to study anything. If you would like, bring one of these:

- the name or photo of a bottle you loved;
- a wine purchase that disappointed you;
- details about an upcoming dinner, gift or celebration;
- any questions raised by **Italy Beyond the Famous Labels**.

### CTA

**ADD TO MY CALENDAR →**

### Secondary link

Need to change the time? `Reschedule or cancel here.`

### Optional video from Luca

30–45 seconds, conversational:

> Hi [First Name], Luca here. Thank you for telling me a little about what you enjoy. Before we speak, I'll review your profile and begin mapping the Italian styles and producers that may fit you best. Bring any favorite bottle—or even a bottle that disappointed you—and we'll use it as a clue. I look forward to meeting you.

## Tracking

- Page view without booking: `booking_page_view`
- Booking complete webhook: `call_booked`
- Reschedule: `call_rescheduled`
- Cancellation: `call_cancelled`
- Pass UTM and contact ID into Cal.com if supported.

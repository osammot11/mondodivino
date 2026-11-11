# MondoDiVino USA — HVCO Funnel Master

## Obiettivo

Acquisire appassionati statunitensi di vino italiano tramite la guida gratuita **Italy Beyond the Famous Labels**, trasformare i lead più interessati in richieste di selezione personalizzata e portarli a prenotare una chiamata con MondoDiVino.

Il funnel non vende una generica wine box. Vende prima:

1. un modo più intelligente di scoprire vini italiani di grande valore;
2. la fiducia nella selezione *producer-first* di Luca Gradi;
3. una consulenza privata per costruire una selezione coerente con i gusti del cliente;
4. solo durante/dopo la chiamata, la proposta commerciale adatta.

## Big Idea

> The best Italian wine for your money is not always the most famous one.

## Meccanismo unico

**The Producer-First Selection Method**

Invece di partire da notorietà, punteggi o marketing, Luca parte da produttore, territorio, qualità nel bicchiere e coerenza del prezzo.

## Architettura

```text
Meta Ads
  ↓
Short-form opt-in page
  ↓ submit email
Evento: HVCO Opt-in
  ├─ invio immediato guida via email
  ├─ ingresso Sequenza A
  └─ redirect immediato
       ↓
Long-form Godfather Offer + Taste Profile Form
  ↓ submit profile
Evento: Taste Profile Completed
  ├─ stop Sequenza A
  ├─ ingresso Sequenza B
  └─ redirect immediato
       ↓
Booking thank-you page + Cal.com
  ↓ booking confirmed
Evento: Call Booked
  ├─ stop Sequenza B
  └─ ingresso Sequenza C
```

## Asset

- [[Campagna USA HVCO - 01 Short Form Opt-in Page]]
- [[Campagna USA HVCO - 02 Long Form Taste Profile Page]]
- [[Campagna USA HVCO - 03 Booking Thank-you Page]]
- [[Campagna USA HVCO - 04 Email Sequences]]

## Stati CRM e regole

| Stato | Trigger di ingresso | Azione primaria | Sequenza attiva | Trigger di uscita |
|---|---|---|---|---|
| `HVCO_LEAD` | Opt-in valido | Consegnare guida e portare al profilo | A | Profilo compilato, unsubscribe o hard bounce |
| `TASTE_PROFILE_COMPLETE` | Invio modulo gusti | Portare alla prenotazione | B | Call prenotata, unsubscribe o non qualificato |
| `CALL_BOOKED` | Booking Cal.com confermato | Aumentare show rate e preparare Luca | C | Call svolta, cancellata o no-show |
| `CALL_COMPLETED` | Esito registrato | Follow-up commerciale personalizzato | Manuale/CRM | Acquisto o perdita esplicita |
| `NO_SHOW` | Mancata presenza | Rebooking | Branch C | Nuova prenotazione o rinuncia |

### Regole di soppressione

- Nessun contatto deve ricevere contemporaneamente A, B e C.
- La compilazione del profilo interrompe A immediatamente.
- La prenotazione interrompe B immediatamente.
- Un acquisto interrompe ogni sequenza promozionale del funnel.
- Un unsubscribe, una richiesta di stop o un hard bounce interrompono tutti i messaggi non transazionali.
- Evitare di inviare un sollecito a prenotare se la sincronizzazione Cal.com/CRM non è aggiornata.

## Eventi da tracciare

1. `landing_view`
2. `hvco_optin`
3. `guide_email_delivered`
4. `taste_page_view`
5. `taste_form_started`
6. `taste_profile_completed`
7. `booking_page_view`
8. `call_booked`
9. `call_rescheduled`
10. `call_cancelled`
11. `call_show`
12. `call_no_show`
13. `offer_made`
14. `purchase`

Usare UTM e salvare almeno `utm_source`, `utm_medium`, `utm_campaign`, `utm_content` e `utm_term` sul contatto.

## Dati che non vanno inventati

Prima della pubblicazione devono essere forniti o validati:

- credenziali esatte e bio approvata di Luca Gradi;
- numero di vini/cantine degustati o selezionati;
- testimonianze e recensioni utilizzabili negli USA;
- durata della chiamata;
- disponibilità reale degli slot;
- prezzo/fascia definitiva della selezione;
- numero e tipologia di bottiglie;
- Stati USA servibili;
- merchant/importatore/licenziatario;
- imposte, dazi, shipping e firma alla consegna;
- policy per rotture, bottiglie tappate e danni da temperatura;
- garanzia o sostituzioni;
- eventuale valore monetario attribuito alla consulenza;
- privacy policy, terms, email consent e testi approvati per il settore alcolici.

## KPI principali

| Fase | KPI |
|---|---|
| Ads → landing | CTR, CPC, landing-page view rate |
| Opt-in page | opt-in rate, CPL |
| Taste page | profile start rate, profile completion rate |
| Booking page | booking rate su profili completati |
| Call | show rate, qualification rate, close rate |
| Business | CAC, revenue per lead, revenue per booked call, AOV, repeat rate |

## Test prioritari

1. Headline opt-in: `Famous Label Tax` vs `Hidden Italy` vs `No Sommelier Required`.
2. Form iniziale: solo first name + email vs first name + email + ZIP.
3. Long page: video breve di Luca vs immagine + lettera firmata.
4. CTA: `Build My Wine Profile` vs `Create My Private Selection`.
5. Booking: calendario embedded above the fold vs dopo tre benefit.
6. Sequenza B: solo email vs email + SMS autorizzato.

## Nota strategica

La chiamata è la Godfather Offer front-end: gratuita, personalizzata e con valore autonomo. La box resta la soluzione proposta dopo aver compreso gusti, occasioni, budget e vincoli di consegna. La pagina non deve promettere una selezione “perfetta” in senso assoluto; deve promettere una selezione costruita sui dati dichiarati dal cliente e guidata dall'esperienza di Luca.

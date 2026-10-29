# MVP requirements synthesis (read-only analysis)

Source precedence: Entrega 3A version 1.0 (7 Oct 2026, 18 pages) refines the earlier full 12-page Requerimientos and 25-page case deck. The supplied 3B screenshot reiterates the same five core behaviors and emphasizes exclusive reservation. User explicitly chooses WordPress plugin + theme and professional mobile-first app UI; prior low-code platform is not binding.

## Core requirements

1. Register donation (CMP-01 / SCRUM-7): authenticated merchant, or authorized coordinator assisted entry. Required: stable merchant identity, food description/type, positive numeric quantity, unit, pickup point, future deadline date+time. Persist unique donation ID, Disponible status, author, created timestamp. Assisted entries record merchant separately from author, channel (phone/SMS etc.), assisted flag. Server validates and returns field-specific errors while UI preserves valid input. Do not confirm before storage acknowledges. On uncertain creation retry, consult stable request before duplicating.
2. Browse (CMP-02 / SCRUM-8): authenticated volunteer sees only Disponible with future deadline, including food, quantity+unit, pickup point, cutoff. Reserved, delivered and expired excluded; server rechecks on reserve. Empty success says No hay donaciones disponibles; connection failure says No pudimos actualizar las donaciones + Reintentar. Cached data must be labeled previous information.
3. Exclusive reserve (CMP-03 / SCRUM-9), critical: session, role, current state, deadline checked server-side. Availability check + assignment atomic at persistence layer, unique reservation constraint per donation. Two concurrent users yield exactly one winner and clear conflict for loser (Esta donación ya fue reservada), without winner private details. Stable logical request ID retained for retries; same request returns same reservation ID+holder. Same key with changed payload conflicts. Expired, delivered, nonexistent or unauthorized cannot reserve. Offline/uncertain result stays pending until server confirmation.
4. Delivery (CMP-04 / SCRUM-10): only authorized coordination closes existing Reservada donation after recipient confirmation. Required recipient organization (unambiguous identity), pickup timestamp, delivery timestamp, recording responsible. Volunteer holder remains transporter, separate from coordinator recording. Require pickup <= delivery <= current server time. Persist unique delivery per reservation, history, origin, food, qty/unit, transporter. Repeated same close returns original result; subsequent changed close cannot overwrite/double-count. No direct Disponible -> Entregada.
5. State (CMP-05 / SCRUM-11): authenticated ID lookup shows current Disponible / Reservada / Entregada, history consistent with persistence. Private responsible data visible only to entitled role. Invalid or forbidden ID both produce No es posible consultar esta donación, avoiding existence disclosure. Merchant sees own donations; volunteer sees current offers and own reservations; coordination sees operational rescues. No direct access required for recipients or cooperants in core.

## Persistence / model

Participant: ID, role, operational name, merchant/volunteer ref, access status.
Merchant: ID, name, pickup point, operational contact.
Recipient org: ID, name, location, operational contact.
Donation: ID, merchant, food, qty, unit, pickup point, cutoff, status, author, channel, assisted flag, created.
Reservation: ID, donation unique, volunteer, confirmed timestamp, request key.
Delivery: ID, reservation unique, org, pickup+delivery times, recording responsible, request key.
Event: ID, donation, type, previous/new status, actor, timestamp, operation ref. Assignment+event and close+event atomically persist or recoverably remain consistent.

UTC storage, America/Bogota display. Server clock authoritative. Pickup is event, not fourth state. Expiry hides offers without rewriting historical status. Do not automatically release reservations, add cancellation, returns or reopen without a later rule.

## Outside current core

Automatic SMS intake, automatic notifications (volunteers/recipients), automatic recipient assignment, impact reports/exports, advanced user management, volunteer schedule/transport profile, recipient capacity/refrigeration matching. Manual coordinator confirmation of recipient capacity is intended. Prior 9-page plan and 25-page case contain broader vision; don't imply those are implemented by five-core delivery.

## QA acceptance

- CP01 valid donation recovered from separate session, same ID+data.
- CP02 blank, zero/negative qty, past cutoff reject without clearing valid input/creating row.
- CP03 coordinator assisted create accepted with author+merchant+channel; volunteer denied.
- CP04 mixed current/expired/reserved/delivered => only eligible offered.
- CP05 empty list vs disconnected distinct with retry and no stale confirmation.
- CP06 10 fresh two-user races => one holder + one conflict each; preserve server response+database evidence, invert order.
- CP07 lost response retry returns same ID+holder, no duplicate.
- CP08 close with org+pickup+delivery preserves origin/qty/transporter/history, Entregada.
- CP09 incomplete, conflicting/future dates, unauthorized close reject; replay valid close one delivery.
- CP10 lookup after transitions agrees with storage and role visibility.
- CP11 foreign/nonexistent lookup protected without private/existence disclosure.
- Mobile 360px with no horizontal scroll; low-end compatibility, no mandatory heavy media.
- Brief registration goal <3 minutes, requires observed direct+assisted measurements, not a claim.

## Prototype reference

The prior /Downloads/registro-donaciones prototype is frontend-only localStorage simulation. Exact named files are readable even though directory listing gives Operation not permitted. Copies of all code files linked by index are under work/requirements (read-only reference copies).
Fields include donor org/type/contact/phone, pickup address/day/start/end, access notes, food rows (description, qty, unit, preservation, cutoff), calculated kg, sanitary notes and fitness checkbox. Draft/publish behavior is simulated. README explicitly says replace js/api.js with real fetch. Avoid mistaking it for backend.
Original brand tokens: orange #FD6300, black #090909. Logos at assets/img/logo-mco.png and logo-mco-light.png. Existing prototype includes emojis in README but user explicitly bans them in new product.

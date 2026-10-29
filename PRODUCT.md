# Rescate de Alimentos

<!-- impeccable:product-schema 1 -->

## Platform
web

## Stack
WordPress PHP plugin and standalone classic theme, explicitly requested by the user. Vanilla JavaScript/CSS frontend; WordPress REST API and transactional SQL persistence.

## Users
Fundación Mesa Común coordinates donated food in Cali. Merchants publish, volunteers reserve and transport, coordinators enter assisted requests and confirm delivery. Recipients do not require accounts in this MVP.

## Product Purpose
Complete the five core behaviors defined by Entrega 3A: publish a donation, see eligible available offers, reserve exclusively, record delivery and consult protected current status.

## Operating Context
Mobile phones, short pickup windows and intermittent connectivity. Preserve input and stable operation identifiers; show success only after server confirmation. UTC storage, America/Bogota display.

## Capabilities and Constraints
Server role authorization, atomic reservation and retry safety, immutable delivery and audit history. Coordinator-only assisted entry and delivery. No automated messaging, internal route calculation, reports or cancellation in this version. Pickup planning supports an optional publisher-confirmed pin, a map loaded by explicit action and external route links; no live tracking. No emojis. User requested repository and suitable branches.

Records flagged `is_demo` offer an explicitly illustrative public map of Cali, centered at 3.4516, -76.532 at zoom 13. This demo view does not locate a merchant, add a pickup marker, copy a fictitious pickup address, offer a real route or request device location. Records outside that flag retain the saved-pin map and address-based external route behavior. Map resources load only after an explicit action; device location remains optional and separately requested only in the publishing pin selector.

## Brand Commitments
Professional mobile app. The latest explicit user direction replaces the former green identity with a faithful peach-cream, deep-red and vivid-orange food-app visual system based on their supplied screenshot. Preserve the display name Rescate de Alimentos, configurable brand/content, and the developer credit «Desarrollado por Moreno Technology». Existing prototype and PDFs remain factual references. No invented operating metrics or real donor data.

## Evidence on Hand
Four user-provided PDFs and a localStorage prototype, extracted under work/requirements. Existing WordPress administration session on the Hostinger site. GitHub session authenticated by the user for repository creation.

## Product Principles
- A donation has one confirmed reservation owner.
- Permission rules apply on the server.
- Draft, uncertain result, empty response and error are visibly different.
- Demonstration records are explicitly marked.
- Customize brand and content without editing code.

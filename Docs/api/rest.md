# REST API Dokumentace

Základní URL: `https://api.tvojeaplikace.cz/api/v1`

---

## 1. Integrace ARES
### `GET /integrations/ares/{ico}`
- **Popis:** Získá data o ekonomickém subjektu z registru ARES (cache 24h v Redis, odezva < 1.5s).
- **Odpověď 200 OK:**
```json
{
  "ico": "27082440",
  "company_name": "Alza.cz a.s.",
  "street": "Jankovcova 1522/53",
  "city": "Praha",
  "zip": "17000",
  "formatted_address": "Jankovcova 1522/53, 17000 Praha"
}
```

---

## 2. Autentizace & OAuth
- `POST /auth/provider/seznam` `{ code, claim_business_id? }`
- `POST /auth/provider/google` `{ id_token, claim_business_id? }`
- `POST /auth/provider/apple` `{ identity_token, claim_business_id? }`
- `POST /auth/magic-link/request` `{ email }`
- `POST /auth/magic-link/verify` `{ email, pin }`
- `POST /auth/refresh-token` `{ refresh_token }`

---

## 3. Onboarding & Claim Draft
### `POST /onboarding/claim-draft`
- **Body:** `{ session_draft_id: "draft_...", email?: "..." }`
- **Popis:** Převede data z anonymního draftu pod nově registrovaný business, vygeneruje poddoménu a aktivuje 14denní trial.

---

## 4. CRM Poptávky
- `GET /crm/leads?status=NEW|CALL_BACK|RESOLVED` (Vrací seznam a souhrnné statistiky)
- `PATCH /crm/leads/{lead_id}/status` `{ status: "RESOLVED" }`
- `PATCH /crm/leads/{lead_id}/reminder` `{ reminder_at: "2026-09-01T10:00:00Z" }`

---

## 5. Správa Obsahu Webu
- `GET /website/config`
- `PUT /website/config` (Aktualizuje Chameleon design i obsah a vymaže HTML cache)

---

## 6. Veřejný příjem poptávek
### `POST /public/site/{subdomain_or_domain}/lead`
- **Rate-limit:** Max 5 požadavků za minutu na jednu IP adresu.
- **Body:** `{ sender_name, sender_phone, sender_email, message }`

---

## 7. Caddy On-Demand TLS Check
### `GET /domains/check?domain={domain}`
- **Popis:** Interní endpoint pro Caddy reverzní proxy. Vrací HTTP 200 pro autorizované domény nebo HTTP 404 pro zamítnutí vydání SSL.

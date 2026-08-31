# CRM Poptávky (Leads) Model

## 1. Struktura Entity `LeadDTO`
- `lead_id`: UUID
- `business_id`: UUID tenantu
- `sender_name`: Jméno odesílatele poptávky
- `sender_phone`: Telefonní kontakt
- `sender_email`: Volitelný e-mail
- `message`: Text poptávky
- `status`: `LeadStatus` (`NEW` | `CALL_BACK` | `RESOLVED`)
- `reminder_at`: Datum a čas připomenutí (odložená notifikace)
- `created_at`: Časové razítko vytvoření

---

## 2. Stavový Automat
```mermaid
stateDiagram-v2
    [*] --> NEW: Odesláno z veřejného webu
    NEW --> CALL_BACK: Označeno živnostníkem k zavolání
    NEW --> RESOLVED: Vyřízeno přímo
    CALL_BACK --> RESOLVED: Po úspěšném hovoru / domluvě
    RESOLVED --> CALL_BACK: Obnovení komunikace
```

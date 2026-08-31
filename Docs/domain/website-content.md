# Chameleon Engine & Web Content Model

## 1. Chameleon Design Systém

Chameleon Engine převádí strukturovaný JSON konfigurace na CSS custom properties (`--primary`, `--secondary`, `--bg`, `--font-main`, `--radius`) a vybírá příslušné Latte šablony pro bloky.

### Styl & Nálada (Mood)
- **`MODERN`:** Sans-serif typografie (Inter), zaoblení `0.75rem`, vysoký kontrast.
- **`TRADITIONAL`:** Serif typografie (Georgia), zaoblení `0.375rem`, teplé podkladové tóny.
- **`BOLD`:** Úderná masivní typografie (Impact/Arial Black), zaoblení `1rem`, tmavé pozadí.
- **`ELEGANT`:** Jemná serif typografie (Garamond), zaoblení `1.25rem`, luxusní paleta.

### Blokové varianty
- **Hero:** `FULL_IMAGE_OVERLAY` | `SPLIT_TEXT_IMAGE` | `COMPACT_CARD`
- **Pricing:** `LIST_DOTS` | `CARDS_GRID` | `COMPACT_TABLE`
- **Gallery:** `GRID_2X2` | `CAROUSEL_SLIDER` | `FEATURED_HERO`

---

## 2. Obsahová struktura (`web_contents.content`)
```json
{
  "vacation_banner": { "active": true, "text": "Dovolená do 15. srpna" },
  "services": [
    { "id": "uuid", "title": "Oprava", "description": "Popis", "price_text": "od 500 Kč", "order": 1 }
  ],
  "gallery": [
    { "id": "uuid", "image_url": "...", "thumbnail_url": "...", "caption": "..." }
  ],
  "opening_hours": "Po-Pá 8:00 - 17:00",
  "contact": { "phone": "+420 777 ...", "email": "...", "address_visible": true }
}
```

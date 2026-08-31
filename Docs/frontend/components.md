# Frontend Architektura & Komponenty (Admin PWA)

## 1. Komponentní Hierarchie

```
App.vue (Mobile Shell + Spodní navigační lišta)
├── Onboarding.vue
│   ├── StepIco.vue (ARES integrace + debounce)
│   ├── StepArchetype.vue (4 dlaždice + obor)
│   ├── StepContact.vue (Telefon + e-mail)
│   ├── StepMedia.vue (Fotogalerie + upload)
│   ├── StepPreview.vue (Živý náhled webu + 14denní trial CTA)
│   └── AuthModal.vue (Seznam, Google, Apple, Magic Link PIN)
├── CrmLeads.vue
│   ├── StatsBar (Dnes, Nové, Zavolat, Hotovo)
│   ├── StatusTabs (Vše, Nové, Zavolat, Hotovo)
│   ├── LeadCard (Barevný status pill, 1-tap volání tel:, SMS sms:, odložení)
│   └── ReminderModal (Nastavení data a času)
├── ContentEditor.vue
│   ├── ServicesTab (CRUD max 30 služeb)
│   ├── GalleryTab (Multi-upload max 20 fotek)
│   ├── BannerTab (Dovolenkový stavový banner)
│   └── DesignTab (4 Moods, 10 barevných palet, 3 varianty bloků)
└── Settings.vue
    ├── ProfileCard
    ├── SubscriptionCard (Měsíční 250 Kč / Roční 2 500 Kč s .cz doménou)
    └── LogoutButton
```

## 2. Nativní Akce na Mobilu
- **Volat:** `<a :href="'tel:' + phone">`
- **SMS:** `<a :href="'sms:' + phone + '?body=Dobrý den...'">`
- **PWA Manifest:** Podpora instalace na plochu telefonu s offline ikonou a full-screen zobrazením.

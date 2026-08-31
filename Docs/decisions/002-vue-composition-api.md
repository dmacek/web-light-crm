# ADR 002: Vue 3 Composition API & Pinia pro Mobilní Správcovskou Aplikaci

## Status
Schváleno

## Kontext
Správcovská aplikace je mobile-first SPA/PWA s důrazem na reaktivitu, rychlost odezvy na mobilních zařízeních a snadnou integraci s nativními možnostmi telefonu (`tel:`, `sms:`).

## Rozhodnutí
Použít Vue 3 s `<script setup lang="ts">`, Pinia pro state management a `@vueuse/core` pro debouncing a lokální storage.

## Důsledky
- Typová bezpečnost a okamžitá odezva UI.
- Nulový overhead a rychlý build přes Vite.

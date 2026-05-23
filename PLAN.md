# GestoPro — Plan de développement

SaaS comptable pour auto-entrepreneurs et petits indépendants.
Assistant comptable IA propulsé par Anthropic Claude. Symfony 7.4 + app mobile.

---

## Stack technique

| Couche             | Technologie                               |
|--------------------|-------------------------------------------|
| Backend            | Symfony 7.4 / PHP 8.3+                    |
| ORM                | Doctrine + MySQL                          |
| API                | API Platform 3.x (REST/JSON-LD + OpenAPI) |
| Admin interne      | EasyAdmin 4                               |
| IA                 | Anthropic Claude API (`anthropic-ai/sdk`) |
| PDF                | KnpSnappyBundle (wkhtmltopdf)             |
| Auth web           | Symfony Security (sessions)               |
| Auth mobile        | LexikJWTAuthenticationBundle v3           |
| CORS               | NelmioCorsBundle                          |
| Fichiers / uploads | VichUploaderBundle                        |
| Paiements          | Stripe (`stripe/stripe-php`)              |
| Emails             | Symfony Mailer                            |
| Mobile             | App séparée (consomme l'API Platform)     |

---

## Entités principales

```text
User ──────────────────────┐
 ├── Client (N)            │  (multi-tenant : chaque User possède ses données)
 │    └── Invoice (N)      │
 │         └── InvoiceLine (N)
 ├── Expense (N)
 │    └── ExpenseCategory
 ├── UrssafDeclaration (N)
 ├── BalanceSheet (N)
 │    └── validée par Accountant
 └── Conversation IA (N)
      └── Message (N)

Accountant ────────────────┘
 └── gère N User (clients)
```

---

## Phase 1 — Fondations ✅

> Socle technique : auth, entités de base, API Platform, JWT.

- [x] Configuration `.env` (base de données, JWT, Stripe, Anthropic)
- [x] Création de la base de données MySQL `gestopro`
- [x] Entités Doctrine :
  - [x] `User` (email, password, firstName, lastName, siret, plan, stripeCustomerId)
  - [x] `Client` (nom, siret, email, téléphone, adresse, user)
  - [x] `Invoice` + `InvoiceLine` (numéro, statuts, totaux, PDF)
  - [x] `Expense` + `ExpenseCategory` (montant, reçu, OCR data)
  - [x] `UrssafDeclaration` (période, CA déclaré, cotisation calculée)
  - [x] `BalanceSheet` (période, données JSON, statut validation)
  - [x] `AiConversation` + `AiMessage` (historique assistant)
  - [x] `Accountant` + `AccountantInvitation` (portail expert)
- [x] Migration Doctrine (26 tables créées)
- [x] Symfony Security : inscription, connexion web + provider Doctrine
- [x] JWT (LexikJWT) pour authentification API mobile (`POST /api/login`)
- [x] API Platform : 35 routes REST exposées avec multi-tenancy + groupes de serialisation
- [x] Fixtures de développement (2 users, 5 clients, 5 factures, 6 dépenses, 3 URSSAF, 1 conv IA)

---

## Phase 2 — Gestion comptable cœur ✅ (en cours)

> Fonctionnalités métier essentielles.

### Clients

- [x] CRUD complet (web)
- [x] Historique des factures par client
- [ ] Import CSV clients

### Factures

- [x] Création avec lignes de prestation (qty × tarif unitaire)
- [x] Numérotation automatique (`FAC-2026-0042`)
- [x] Statuts : `draft` → `sent` → `paid` / `overdue` / `cancelled`
- [x] Génération PDF (Twig → wkhtmltopdf)
- [ ] Envoi par email (Symfony Mailer, pièce jointe PDF)
- [ ] Suivi paiement manuel
- [ ] Rappels automatiques (Symfony Messenger)
- [ ] Export CSV/Excel

### Dépenses

- [ ] Saisie manuelle (catégorie, montant HT/TTC, TVA, date)
- [ ] **Scan OCR via Claude Vision** :
  - [ ] Upload image/PDF du reçu (web) ou photo (mobile)
  - [ ] Service `ReceiptScannerService` → appel Claude Vision
  - [ ] Extraction automatique : fournisseur, date, montant, TVA, catégorie
  - [ ] Score de confiance affiché (badge vert/orange/rouge)
  - [ ] Formulaire pré-rempli → validation utilisateur
  - [ ] Fichier attaché comme justificatif (VichUploader)
- [ ] Déductibilité marquée
- [ ] Catégorisation automatique suggérée par IA

### URSSAF auto-entrepreneur

- [ ] Calcul cotisations sociales selon CA déclaré
- [ ] Périodicité mensuelle ou trimestrielle
- [ ] Alertes : franchise TVA, seuil CA
- [ ] Export déclaration prête à saisir sur urssaf.fr

---

## Phase 3 — Dashboard & Statistiques ⬜

- [ ] CA mensuel / annuel (graphique)
- [ ] Dépenses vs revenus (graphique)
- [ ] Taux de recouvrement (factures payées / émises)
- [ ] Cotisations URSSAF à venir (widget)
- [ ] Top clients par CA
- [ ] Prévisionnel (trend linéaire)
- [ ] Alertes : factures en retard, seuil TVA

---

## Phase 4 — Intégration Claude AI ⬜

### Assistant comptable (chat)

- [ ] Interface de conversation dans le dashboard
- [ ] Injection de contexte : CA, dépenses, factures, cotisations
- [ ] Historique des conversations persisté en base
- [ ] Exemples de questions : solde courant, optimisation fiscale, rappels

### Bilan Comptable IA

- [ ] Génération du bilan structuré (compte de résultat simplifié)
- [ ] Analyse narrative Claude (forces, risques, recommandations)
- [ ] Export PDF premium (mise en page professionnelle)
- [ ] Versioning par période (mensuel / annuel)
- [ ] Statuts : `draft` → `pending_review` → `validated`

### OCR reçus (lié Phase 2)

- [ ] `ReceiptScannerService` (Claude Vision + base64)
- [ ] Endpoint `POST /api/expenses/scan`
- [ ] Gestion des reçus illisibles (confidence < 0.75 → alerte)

---

## Phase 5 — Portail Expert-Comptable ⬜

- [ ] Rôle `ROLE_ACCOUNTANT` séparé
- [ ] Dashboard expert : liste des bilans clients à valider
- [ ] Système d'invitation client (email + token)
- [ ] Annotation ligne par ligne sur le bilan IA
- [ ] Statuts bilan : `pending_review` → `annotated` → `validated`
- [ ] Tampon numérique (image + signature + horodatage)
- [ ] Notifications client à chaque étape
- [ ] Un expert peut gérer N clients

---

## Phase 6 — SaaS & Abonnements ⬜

| Plan    | Prix      | Limites                                      |
|---------|-----------|----------------------------------------------|
| Gratuit | 0 €/mois  | 5 clients, 10 factures/mois, pas d'IA        |
| Pro     | 19 €/mois | Illimité, assistant IA, OCR reçus            |
| Expert  | 49 €/mois | Pro + portail comptable, bilans IA illimités |

- [ ] Stripe Checkout + webhooks
- [ ] Gestion abonnement (upgrade, downgrade, résiliation)
- [ ] Portail client Stripe (historique facturation)
- [ ] Guards Symfony selon le plan actif
- [ ] Page tarifs + landing page GestoPro

---

## Phase 7 — API Mobile ⬜

> En parallèle dès la Phase 2 — API Platform expose tout automatiquement.

- [ ] Tous les endpoints CRUD via API Platform (faits en Phase 1–2)
- [ ] Endpoint `POST /api/expenses/scan` (upload photo reçu)
- [ ] Endpoint dashboard summary (`GET /api/dashboard`)
- [ ] JWT refresh token
- [ ] Rate limiting
- [ ] Documentation OpenAPI auto-générée (Swagger UI)
- [ ] Push notifications (optionnel)

---

## Phase 8 — Sécurité, Performance & Production ⬜

- [ ] Audit sécurité OWASP (XSS, CSRF, injection SQL, upload)
- [ ] Tests unitaires PHPUnit
- [ ] Tests fonctionnels (API Platform + formulaires)
- [ ] CI/CD (GitHub Actions : lint → tests → déploiement)
- [ ] Cache Redis (requêtes lourdes dashboard)
- [ ] Monitoring : Sentry (erreurs) + Blackfire (perf)
- [ ] Déploiement VPS (Docker) ou Platform.sh
- [ ] Configuration HTTPS, headers sécurité

---

## Ordre de développement recommandé

```text
Phase 1 (fondations)
    → Phase 2 (comptabilité cœur)  ←→  Phase 7 (API mobile, en parallèle)
        → Phase 3 (dashboard)
            → Phase 4 (IA Claude)
                → Phase 5 (expert-comptable)
                    → Phase 6 (SaaS / Stripe)
                        → Phase 8 (prod)
```

---

## Variables d'environnement requises

```env
# Base de données
DATABASE_URL="mysql://root:@127.0.0.1:3306/gestopro"

# JWT
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=

# Anthropic Claude
ANTHROPIC_API_KEY=

# Stripe
STRIPE_SECRET_KEY=
STRIPE_PUBLISHABLE_KEY=
STRIPE_WEBHOOK_SECRET=

# App
APP_ENV=dev
APP_SECRET=
MAILER_DSN=smtp://localhost:1025
```

---

## Progression globale

| Phase | Statut   | Description                  |
|-------|----------|------------------------------|
| 1     | Terminé  | Fondations                   |
| 2     | A faire  | Comptabilité cœur + OCR      |
| 3     | A faire  | Dashboard & stats            |
| 4     | A faire  | Intégration Claude AI        |
| 5     | A faire  | Portail expert-comptable     |
| 6     | A faire  | SaaS & abonnements Stripe    |
| 7     | A faire  | API mobile (en parallèle)    |
| 8     | A faire  | Sécurité, tests, production  |

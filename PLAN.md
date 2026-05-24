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

- [x] Saisie manuelle (catégorie, montant HT/TTC, TVA, date)
- [x] **Scan OCR via Claude Vision** :
  - [x] Upload image/PDF du reçu (web drag-and-drop) ou photo (mobile)
  - [x] Service `ReceiptScannerService` → appel Claude Vision (`claude-opus-4-5`)
  - [x] Extraction automatique : fournisseur, date, montant, TVA, catégorie
  - [x] Score de confiance affiché (badge vert/orange/rouge)
  - [x] Formulaire pré-rempli → validation utilisateur
  - [x] Persistance `ocrData`, `ocrConfidence`, `ocrVerified` en base
  - [ ] Fichier attaché comme justificatif (VichUploader)
- [ ] Déductibilité marquée
- [ ] Catégorisation automatique suggérée par IA

### URSSAF auto-entrepreneur

- [ ] Calcul cotisations sociales selon CA déclaré
- [ ] Périodicité mensuelle ou trimestrielle
- [ ] Alertes : franchise TVA, seuil CA
- [ ] Export déclaration prête à saisir sur urssaf.fr

---

## Phase 3 — Dashboard & Statistiques ✅

- [x] CA mensuel / annuel (graphique barres)
- [x] Dépenses vs revenus (graphique barres superposé)
- [x] Taux de recouvrement (factures payées / émises)
- [x] Cotisations URSSAF à venir (widget)
- [x] Top clients par CA (barres de progression)
- [x] Alertes : factures en retard, seuil TVA
- [x] Graphique donut dépenses par catégorie
- [ ] Prévisionnel (trend linéaire)

---

## Phase 4 — Portail Expert-Comptable ✅

- [x] Rôle `ROLE_ACCOUNTANT` séparé (firewall `expert`, contexte de session isolé)
- [x] Dashboard expert : liste des bilans clients à valider
- [x] Système d'invitation client (token 64 hex, expiration 7j, URL directe)
- [x] Annotation globale sur le bilan (commentaire expert)
- [x] Statuts bilan : `pending_review` → `annotated` → `validated`
- [x] Validation horodatée (`validatedAt`)
- [ ] Tampon numérique (image + signature) — optionnel
- [ ] Notifications email client à chaque étape — optionnel
- [x] Un expert peut gérer N clients

---

## Phase 5 — SaaS & Abonnements ✅

| Plan    | Prix      | Limites                                      |
|---------|-----------|----------------------------------------------|
| Gratuit | 0 €/mois  | 5 clients, 10 factures/mois, pas d'IA        |
| Pro     | 19 €/mois | Illimité, assistant IA, OCR reçus            |
| Expert  | 49 €/mois | Pro + portail comptable, bilans IA illimités |

- [x] Stripe Checkout (sessions subscription pro/expert)
- [x] Webhooks Stripe (checkout.completed, subscription.updated/deleted)
- [x] Gestion abonnement (upgrade, portail Stripe, résiliation via portail)
- [x] Portail client Stripe (historique facturation, changement carte)
- [x] Guards plan free (max 5 clients, max 10 factures/mois)
- [x] Page tarifs publique `/pricing` (3 colonnes, FAQ)
- [x] Sidebar : badge plan + lien abonnement
- [ ] Prix Stripe à configurer dans `.env.local` (STRIPE_PRICE_PRO, STRIPE_PRICE_EXPERT)

---

## Phase 6 — API Mobile ✅

> API Platform expose tout automatiquement depuis la Phase 1–2.

- [x] Tous les endpoints CRUD via API Platform (faits en Phase 1–2)
- [x] Endpoint `POST /api/expenses/scan` (upload photo reçu, OCR Claude Vision)
- [x] Endpoint dashboard summary (`GET /api/dashboard`)
- [x] JWT refresh token (gesdinet/jwt-refresh-token-bundle, 30 jours)
- [x] Rate limiting (`login_throttling` : 5 tentatives / 15 min sur `/api/login`)
- [x] Documentation OpenAPI auto-générée (Swagger UI via API Platform)
- [ ] Push notifications (optionnel)

---

## Phase 7 — Intégration Claude AI ⬜

> À intégrer une fois le produit complet et stable.

### Assistant comptable (chat)

- [x] Interface de conversation dans le dashboard
- [x] Injection de contexte : CA, dépenses, factures, cotisations
- [x] Historique des conversations persisté en base
- [x] Exemples de questions : solde courant, optimisation fiscale, rappels

### Bilan Comptable IA

- [ ] Génération du bilan structuré (compte de résultat simplifié)
- [ ] Analyse narrative Claude (forces, risques, recommandations)
- [ ] Export PDF premium (mise en page professionnelle)
- [ ] Versioning par période (mensuel / annuel)
- [ ] Statuts : `draft` → `pending_review` → `validated`

### OCR reçus ✅

- [x] `ReceiptScannerService` (Claude Vision + base64, `claude-opus-4-5`)
- [x] Upload image/PDF du reçu → extraction automatique (drag-and-drop web)
- [x] Score de confiance (badge vert/orange/rouge)
- [x] Formulaire pré-rempli → validation utilisateur
- [x] Endpoint `POST /api/expenses/scan` (mobile JWT)
- [x] Endpoint `POST /expenses/scan-receipt` (web session)

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

## Ordre de développement

```text
Phase 1 (fondations) ✅
    → Phase 2 (comptabilité cœur) ✅
        → Phase 3 (dashboard & stats) ✅
            → Phase 4 (portail expert-comptable)
                → Phase 5 (SaaS / Stripe)
                    → Phase 6 (API mobile)
                        → Phase 7 (IA Claude) ← intégrée en dernier
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

| Phase | Statut      | Description                  |
|-------|-------------|------------------------------|
| 1     | ✅ Fait     | Fondations                   |
| 2     | ✅ Fait     | Comptabilité cœur            |
| 3     | ✅ Fait     | Dashboard & stats            |
| 4     | ✅ Fait     | Portail expert-comptable     |
| 5     | ✅ Fait     | SaaS & abonnements Stripe    |
| 6     | ✅ Fait     | API mobile                   |
| 7     | ⬜ À faire  | Intégration Claude AI        |
| 8     | ⬜ À faire  | Sécurité, tests, prod        |

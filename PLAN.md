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
 ├── Quote (N)
 │    └── QuoteLine (N)
 ├── Expense (N)
 │    └── ExpenseCategory
 ├── UrssafDeclaration (N)
 ├── BalanceSheet (N)
 │    └── validée par Accountant
 └── Conversation IA (N)
      └── Message (N)

Accountant ────────────────┘
 └── gère N User (clients)
      └── ExpertMessage (messagerie directe)
```

---

## Analyse landing page → fonctionnalités à livrer

> Tout ce qui est présenté sur la landing doit être opérationnel une fois connecté.
> Ce tableau est la source de vérité pour les priorités de développement.

| Fonctionnalité annoncée | Plan | État | Phase |
| --- | --- | --- | --- |
| Création facture + numérotation auto | Free | ✅ OK | 2 |
| Mentions légales pré-remplies | Free | ✅ OK | 2 |
| TVA calculée automatiquement | Free | ✅ OK | 2 |
| Génération PDF facture | Free | ✅ OK | 2 |
| Envoi facture par email | Free | ✅ OK | 2 |
| Suivi statut paiement | Free | ✅ OK | 2 |
| Devis (DRAFT → SENT → CONVERTI) | Free | ✅ OK | 2 |
| Export comptable basique | Free | ❌ À faire | 2 |
| Lien de paiement Stripe dans l'email | Pro | ❌ À faire | 5 |
| Relances automatiques tous les 7 jours | Pro | ❌ À faire | 2 |
| OCR reçus (photo → extraction IA) | Pro | ✅ OK | 7 |
| Score de confiance OCR (vert/orange/rouge) | Pro | ✅ OK | 7 |
| Assistant IA (chat, multi-tour) | Pro | ⚠️ Partiel | 7 |
| Contexte financier injecté dans l'IA | Pro | ❌ À vérifier | 7 |
| Calcul cotisations URSSAF automatique | Free | ❌ Manuel actuellement | 2 |
| Rappels avant échéances URSSAF/TVA | Free | ❌ À faire | 2 |
| Export justificatif déclaration URSSAF | Free | ❌ À faire | 2 |
| Multi-devises (factures internationales) | Pro | ❌ À faire | 2 |
| Export FEC complet | Pro | ❌ À faire | 2 |
| Portail expert-comptable | Expert | ✅ OK | 4 |
| Messagerie client ↔ expert | Expert | ✅ OK | 4 |
| Bilans IA mensuels (génération Claude) | Expert | ❌ À faire | 7 |
| Analyse narrative du bilan (Claude) | Expert | ❌ À faire | 7 |
| Liasse fiscale assistée | Expert | ❌ À faire | 7 |
| Export PDF bilan professionnel | Expert | ❌ À faire | 7 |
| Landing page publique | — | ✅ OK | 9 |
| Modal login/register sur la landing | — | ✅ OK | 9 |

---

## Phase 1 — Fondations ✅

> Socle technique : auth, entités de base, API Platform, JWT.

- [x] Configuration `.env` (base de données, JWT, Stripe, Anthropic)
- [x] Création de la base de données MySQL `gestopro`
- [x] Entités Doctrine :
  - [x] `User` (email, password, firstName, lastName, siret, plan, stripeCustomerId)
  - [x] `Client` (nom, siret, email, téléphone, adresse, user)
  - [x] `Invoice` + `InvoiceLine` (numéro, statuts, totaux, PDF)
  - [x] `Quote` + `QuoteLine` (numéro, statuts, conversion en facture)
  - [x] `Expense` + `ExpenseCategory` (montant, reçu, OCR data)
  - [x] `UrssafDeclaration` (période, CA déclaré, cotisation calculée)
  - [x] `BalanceSheet` (période, données JSON, statut validation)
  - [x] `AiConversation` + `AiMessage` (historique assistant)
  - [x] `Accountant` + `AccountantInvitation` (portail expert)
  - [x] `ExpertMessage` (messagerie client ↔ expert)
- [x] Migration Doctrine (26+ tables créées)
- [x] Symfony Security : inscription, connexion web + provider Doctrine
- [x] JWT (LexikJWT) pour authentification API mobile (`POST /api/login`)
- [x] API Platform : 35+ routes REST exposées avec multi-tenancy + groupes de serialisation
- [x] Fixtures de développement (2 users, 5 clients, 5 factures, 6 dépenses, 3 URSSAF, 1 conv IA)

---

## Phase 2 — Gestion comptable cœur 🔄 (partiellement fait)

> Fonctionnalités métier essentielles. Plusieurs items encore ouverts (voir tableau landing).

### Clients

- [x] CRUD complet (web)
- [x] Historique des factures par client
- [ ] Import CSV clients

### Factures

- [x] Création avec lignes de prestation (qty × tarif unitaire)
- [x] Numérotation automatique (`FAC-2026-0042`)
- [x] Statuts : `draft` → `sent` → `paid` / `overdue` / `cancelled`
- [x] Génération PDF (Twig → wkhtmltopdf)
- [x] Envoi par email (Symfony Mailer, pièce jointe PDF) — `InvoiceMailer`
- [ ] Lien de paiement Stripe intégré à l'email ← **promis sur la landing**
- [ ] Relances automatiques tous les 7 jours (Symfony Scheduler) ← **promis sur la landing**
- [ ] Multi-devises (€, $, £, CHF) ← **promis sur la landing (plan Pro)**
- [ ] Export FEC complet (format Livre de Comptes) ← **promis sur la landing (plan Pro)**
- [ ] Export CSV/Excel des factures

### Devis

- [x] CRUD complet + numérotation (`DEV-2026-0001`)
- [x] Workflow : `draft` → `sent` → `accepted` / `declined` → `converted` (facture)
- [x] Envoi par email + conversion en facture avec clonage des lignes
- [ ] Relances automatiques sur devis en attente
- [ ] Signature électronique (optionnel)

### Dépenses

- [x] Saisie manuelle (catégorie, montant HT/TTC, TVA, date)
- [x] **Scan OCR via Claude Vision** :
  - [x] Upload image/PDF du reçu (web drag-and-drop)
  - [x] Service `ReceiptScannerService` → appel Claude Vision
  - [x] Extraction automatique : fournisseur, date, montant, TVA, catégorie
  - [x] Score de confiance affiché (badge vert/orange/rouge)
  - [x] Formulaire pré-rempli → validation utilisateur
  - [x] Persistance `ocrData`, `ocrConfidence`, `ocrVerified` en base
  - [ ] Fichier attaché comme justificatif (VichUploader)
- [ ] Export CSV dépenses
- [ ] Export comptable basique (CSV normalisé) ← **promis sur la landing (plan Free)**

### URSSAF auto-entrepreneur

- [x] CRUD déclarations (période, CA, cotisation, périodicité)
- [x] Suggestion CA depuis les factures payées (`ca-suggestion` endpoint)
- [x] Taux de cotisation stocké sur la déclaration
- [ ] Calcul automatique des cotisations selon taux AE (BIC/BNC/lib) ← **promis sur la landing**
- [ ] Alertes : franchise TVA (34 400 €), seuil CA (176 200 € / 77 700 €)
- [ ] Rappels avant échéance (J-7, J-3) ← **promis sur la landing**
- [ ] Export déclaration prête à saisir sur urssaf.fr ← **promis sur la landing**

---

## Phase 3 — Dashboard & Statistiques ✅

- [x] CA mensuel / annuel (graphique barres)
- [x] Dépenses vs revenus (graphique barres superposé)
- [x] Taux de recouvrement (factures payées / émises)
- [x] Cotisations URSSAF à venir (widget)
- [x] Top clients par CA (barres de progression)
- [x] Alertes : factures en retard, seuil TVA
- [x] Graphique donut dépenses par catégorie
- [ ] Prévisionnel CA (trend linéaire)

---

## Phase 4 — Portail Expert-Comptable ✅

- [x] Rôle `ROLE_ACCOUNTANT` séparé (firewall `expert`, contexte de session isolé)
- [x] Annuaire experts côté client (présentation, bio, demande de mise en relation)
- [x] Workflow invitation : client → envoie demande → expert accepte/refuse
- [x] Hub expert côté client : bilans + messagerie directe au même endroit
- [x] Dashboard expert : bilans clients à réviser + demandes en attente
- [x] Annotation globale sur le bilan (commentaire expert)
- [x] Statuts bilan : `pending_review` → `annotated` → `validated`
- [x] Validation horodatée (`validatedAt`)
- [x] Messagerie directe client ↔ expert (`ExpertMessage`)
- [x] Profil public expert (bio, cabinet, numéro d'inscription)
- [x] Un expert peut gérer N clients
- [ ] Tampon numérique (image + signature expert) — optionnel
- [ ] Notifications email à chaque changement de statut bilan — optionnel

---

## Phase 5 — SaaS & Abonnements ✅

| Plan    | Prix      | Limites                                      |
|---------|-----------|----------------------------------------------|
| Gratuit | 0 €/mois  | 5 clients, 10 factures/mois, pas d'IA        |
| Pro     | 19 €/mois | Illimité, assistant IA, OCR reçus            |
| Expert  | 49 €/mois | Pro + portail comptable, bilans IA illimités |

- [x] Stripe Checkout (sessions subscription pro/expert)
- [x] Webhooks Stripe (checkout.completed, subscription.updated/deleted)
- [x] Gestion abonnement (upgrade, portail Stripe, résiliation)
- [x] Portail client Stripe (historique facturation, changement carte)
- [x] Guards plan free (max 5 clients, max 10 factures/mois)
- [x] Page tarifs publique `/pricing` (3 colonnes, FAQ)
- [x] Sidebar : badge plan + lien abonnement
- [ ] Lien de paiement Stripe dans les emails facture ← **promis sur la landing**
- [ ] Prix Stripe à configurer dans `.env.local` (STRIPE_PRICE_PRO, STRIPE_PRICE_EXPERT)

---

## Phase 6 — API Mobile ✅

> API Platform expose tout automatiquement depuis la Phase 1–2.

- [x] Tous les endpoints CRUD via API Platform
- [x] Endpoint `POST /api/expenses/scan` (upload photo reçu, OCR Claude Vision)
- [x] Endpoint dashboard summary (`GET /api/dashboard`)
- [x] JWT refresh token (gesdinet/jwt-refresh-token-bundle, 30 jours)
- [x] Rate limiting (`login_throttling` : 5 tentatives / 15 min sur `/api/login`)
- [x] Documentation OpenAPI auto-générée (Swagger UI via API Platform)
- [ ] Push notifications (optionnel)

---

## Phase 7 — Intégration Claude AI 🔄 (partiellement fait)

### OCR reçus ✅

- [x] `ReceiptScannerService` (Claude Vision + base64)
- [x] Upload image/PDF → extraction automatique (drag-and-drop web)
- [x] Score de confiance (badge vert/orange/rouge)
- [x] Formulaire pré-rempli → validation utilisateur
- [x] Endpoint `POST /api/expenses/scan` (mobile JWT)
- [x] Endpoint `POST /expenses/scan-receipt` (web session)

### Assistant comptable (chat) ⚠️ À vérifier/compléter

- [x] Interface de conversation dans le dashboard
- [x] Persistance historique en base (`AiConversation`, `AiMessage`, comptage tokens)
- [x] Guard plan Pro/Expert (`isPro()`)
- [ ] **Vérifier `AiAssistantService::chat()` — implémenté ou stub ?** ← priorité
- [ ] Injection de contexte financier réel (CA, dépenses, TVA, factures en cours)
- [ ] Exemples de questions suggérées au démarrage

### Bilan Comptable IA ❌ À faire

> Promis sur la landing pour le plan Expert. La structure `BalanceSheet` existe mais
> l'IA ne génère aucune analyse pour l'instant.

- [x] Agrégation données financières (CA, dépenses, URSSAF, résultat net) dans `BalanceSheetController`
- [ ] Génération analyse narrative Claude (forces, risques, recommandations) ← **promis sur la landing**
- [ ] Export PDF bilan professionnel (mise en page premium) ← **promis sur la landing**
- [ ] Versioning par période (mensuel / trimestriel / annuel)

### Liasse fiscale assistée ❌ À faire

> Promis sur la landing pour le plan Expert.

- [ ] Formulaire 2042-C PRO pré-rempli (auto-entrepreneur)
- [ ] Calcul BNC/BIC déclarable
- [ ] Export PDF prêt à télédéclarer

---

## Phase 8 — Sécurité, Performance & Production ⬜

- [ ] Audit sécurité OWASP (XSS, CSRF, injection SQL, upload)
- [ ] Tests unitaires PHPUnit (services critiques : calculs, OCR, IA)
- [ ] Tests fonctionnels (API Platform + formulaires web)
- [ ] CI/CD (GitHub Actions : lint → tests → déploiement)
- [ ] Cache Redis (requêtes lourdes dashboard)
- [ ] Monitoring : Sentry (erreurs) + Blackfire (perf)
- [ ] Déploiement VPS (Docker) ou Platform.sh
- [ ] Configuration HTTPS, headers sécurité (CSP, HSTS)

---

## Phase 9 — Landing page & UX publique ✅

- [x] Landing page publique sur `/` (accessible sans compte)
- [x] Hero avec mockup dashboard animé HTML
- [x] Section features : factures, OCR, IA, échéances (avec mockups visuels)
- [x] Section tarifs avec toggle mensuel/annuel
- [x] FAQ accordion (5 questions)
- [x] Formulaire de contact (POST → flash message)
- [x] CTA finale + footer complet
- [x] Modal login/register inline (tabs Client / Création compte)
- [x] Redirection déconnexion client → landing (`/`)
- [x] Dashboard déplacé sur `/dashboard` (landing prend `/`)
- [x] Popups de confirmation remplacées par modales custom (design system)
- [x] Sidebar client : bilans regroupés dans la section Expert-comptable

---

## Prochaines priorités (ordre recommandé)

1. **Vérifier `AiAssistantService`** — confirmer que le chat IA fonctionne réellement
2. **Relances automatiques factures** — Symfony Scheduler, envoi J+7 après envoi
3. **Calcul URSSAF automatique** — formule selon type AE + taux en vigueur
4. **Bilan IA (génération Claude)** — analyse narrative + export PDF
5. **Export FEC** — obligatoire pour les plans Pro/Expert
6. **Multi-devises** — devise sur Invoice/Quote, conversion à l'affichage
7. **Liasse fiscale assistée** — formulaire 2042-C PRO pré-rempli
8. **Lien de paiement Stripe dans l'email** — URL Stripe Checkout dans le mail facture

---

## Ordre de développement

```text
Phase 1 (fondations) ✅
    → Phase 2 (comptabilité cœur) 🔄 partiellement
        → Phase 3 (dashboard & stats) ✅
            → Phase 4 (portail expert-comptable) ✅
                → Phase 5 (SaaS / Stripe) ✅
                    → Phase 6 (API mobile) ✅
                        → Phase 7 (IA Claude) 🔄 OCR ✅ / chat à vérifier / bilan IA ❌
                            → Phase 8 (prod) ⬜
Phase 9 (landing & UX publique) ✅
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
STRIPE_PRICE_PRO=        # price_xxx à configurer
STRIPE_PRICE_EXPERT=     # price_xxx à configurer

# App
APP_ENV=dev
APP_SECRET=
MAILER_DSN=smtp://localhost:1025
```

---

## Progression globale

| Phase | Statut | Description |
| --- | --- | --- |
| 1 | ✅ Fait | Fondations |
| 2 | 🔄 Partiel | Comptabilité cœur (FEC, relances, URSSAF calc manquants) |
| 3 | ✅ Fait | Dashboard & stats |
| 4 | ✅ Fait | Portail expert-comptable |
| 5 | ✅ Fait | SaaS & abonnements Stripe |
| 6 | ✅ Fait | API mobile |
| 7 | 🔄 Partiel | IA Claude (OCR ✅, chat ⚠️, bilan IA ❌) |
| 8 | ⬜ À faire | Sécurité, tests, production |
| 9 | ✅ Fait | Landing page & UX publique |

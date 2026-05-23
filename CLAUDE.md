# GestoPro — Contexte projet pour Claude Code

## Projet

**GestoPro** est un SaaS comptable pour auto-entrepreneurs et petits indépendants.
- Assistant comptable IA propulsé par **Anthropic Claude**
- Backend **Symfony 7.4** / PHP 8.3
- Application mobile séparée connectée à la même API (tech mobile non encore décidée)
- Portail expert-comptable pour validation et tampon numérique des bilans IA

## Règles de travail

- **Commit + push GitHub après chaque tâche terminée** — une tâche = un commit, pas de regroupement.
- Remote GitHub : `https://github.com/anthonycozette/gestopro.git` (branche `master`)
- Mettre à jour `PLAN.md` à chaque tâche complétée (cocher `[x]`).
- Ne jamais commiter les fichiers sensibles : `.env.local`, `config/jwt/*.pem`, `var/`.

## Stack technique

| Couche             | Technologie                               |
|--------------------|-------------------------------------------|
| Backend            | Symfony 7.4 / PHP 8.3+                    |
| ORM                | Doctrine + MySQL (`gestopro` sur XAMPP)   |
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

## Entités Doctrine (toutes créées, migration exécutée)

- `User` — tenant principal, plans : free / pro / expert, Stripe
- `Client` — appartient à un User
- `Invoice` + `InvoiceLine` — statuts : draft/sent/paid/overdue/cancelled
- `Expense` + `ExpenseCategory` — avec champs OCR (ocrData, ocrConfidence, ocrVerified)
- `UrssafDeclaration` — cotisations auto-entrepreneur avec taux et périodicité
- `BalanceSheet` — bilan IA, statuts : draft/pending_review/annotated/validated
- `AiConversation` + `AiMessage` — historique assistant Claude (avec comptage tokens)
- `Accountant` — rôle ROLE_ACCOUNTANT, tampon numérique
- `AccountantInvitation` — token d'invitation avec expiration 7j

## Fonctionnalité clé : Scan OCR des reçus de dépenses

Les utilisateurs peuvent prendre en photo ou uploader un reçu.
Claude Vision extrait automatiquement : fournisseur, date, montant TTC/HT, TVA, catégorie.
Un score de confiance (0→1) détermine un badge vert/orange/rouge.
L'utilisateur valide les données pré-remplies avant création de la dépense.
Service à créer : `src/Service/ReceiptScannerService.php`
Endpoint API : `POST /api/expenses/scan`

## Plans d'abonnement

| Plan    | Prix      | Limites                                      |
|---------|-----------|----------------------------------------------|
| Gratuit | 0 €/mois  | 5 clients, 10 factures/mois, pas d'IA        |
| Pro     | 19 €/mois | Illimité, assistant IA, OCR reçus            |
| Expert  | 49 €/mois | Pro + portail comptable, bilans IA illimités |

## Environnement de développement

- OS : Windows 11, XAMPP (PHP 8.5.3, MySQL)
- Extension sodium activée dans `C:\xampp\php\php.ini`
- GitHub CLI (`gh`) installé et connecté sous `anthonycozette`
- Symfony CLI 5.15.1 disponible
- Base de données : `gestopro` sur MySQL local (root sans mot de passe)
- Serveur de dev : `symfony serve` depuis `c:\xampp\htdocs\gestopro`

## Avancement — voir PLAN.md pour le détail complet

### Phase 1 — Fondations (en cours)
- [x] Configuration `.env`
- [x] Base de données MySQL créée
- [x] 9 entités Doctrine + migration (26 tables)
- [x] Clés JWT générées (`config/jwt/`)
- [ ] Symfony Security : inscription, connexion, reset password
- [ ] JWT configuration et endpoints d'authentification
- [ ] API Platform : configuration et exposition des ressources
- [ ] Fixtures de développement (Faker)

### Phases suivantes (voir PLAN.md)
- Phase 2 : Gestion comptable cœur (clients, factures, dépenses + OCR, URSSAF)
- Phase 3 : Dashboard & statistiques
- Phase 4 : Intégration Claude AI (assistant chat + bilan IA)
- Phase 5 : Portail expert-comptable
- Phase 6 : SaaS & abonnements Stripe
- Phase 7 : API mobile (en parallèle dès la Phase 2)
- Phase 8 : Sécurité, tests, production

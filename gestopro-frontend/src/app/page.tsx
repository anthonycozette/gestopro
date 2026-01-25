import Link from "next/link"
import { 
  FileText, 
  Users, 
  Calculator, 
  Brain, 
  ScanLine, 
  CheckCircle2,
  ArrowRight,
  Sparkles,
  Shield,
  Zap
} from "lucide-react"

export default function Home() {
  const features = [
    {
      icon: FileText,
      title: "Gestion factures & clients",
      description: "Créez et gérez vos factures en quelques clics. Suivez vos clients et leurs paiements en temps réel.",
      color: "text-blue-500",
    },
    {
      icon: Calculator,
      title: "Calcul URSSAF précis",
      description: "Calculez automatiquement vos cotisations selon les règles officielles. Zéro erreur, zéro stress.",
      color: "text-cyan-500",
    },
    {
      icon: Brain,
      title: "Bilan IA avec Claude",
      description: "Générez votre bilan comptable complet avec l'IA. Analyses, recommandations et projections incluses.",
      color: "text-purple-500",
    },
    {
      icon: ScanLine,
      title: "Scan documents intelligent",
      description: "Photographiez vos reçus et factures. L'IA extrait automatiquement toutes les données importantes.",
      color: "text-emerald-500",
    },
    {
      icon: Users,
      title: "Portail expert-comptable",
      description: "Collaborez avec votre expert-comptable. Validation, signature et annotations en ligne.",
      color: "text-orange-500",
    },
    {
      icon: Shield,
      title: "Sécurité & conformité",
      description: "RGPD, hébergement France, chiffrement AES-256. Vos données sont protégées.",
      color: "text-red-500",
    },
  ]

  const pricing = [
    {
      name: "Starter",
      price: "19",
      description: "Pour démarrer votre activité",
      features: [
        "Jusqu'à 50 factures/mois",
        "Gestion clients illimitée",
        "Calcul URSSAF automatique",
        "Export PDF",
        "Support email",
      ],
      cta: "Commencer",
      popular: false,
    },
    {
      name: "Pro",
      price: "39",
      description: "Pour les entrepreneurs actifs",
      features: [
        "Factures illimitées",
        "Bilan IA mensuel",
        "Scan documents (100/mois)",
        "Portail expert-comptable",
        "Support prioritaire",
        "API accès",
      ],
      cta: "Essayer gratuitement",
      popular: true,
    },
    {
      name: "Business",
      price: "79",
      description: "Pour optimiser votre gestion",
      features: [
        "Tout Pro inclus",
        "Scan illimité",
        "Multi-utilisateurs (3)",
        "Bilan IA hebdomadaire",
        "Support 24/7",
        "Expert-comptable dédié",
      ],
      cta: "Contacter",
      popular: false,
    },
  ]

  const stats = [
    { value: "2500+", label: "Auto-entrepreneurs" },
    { value: "98%", label: "Satisfaction client" },
    { value: "10min", label: "Setup moyen" },
    { value: "45h/mois", label: "Temps gagné" },
  ]

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950">
      
      {/* Hero Section */}
      <section className="relative overflow-hidden pt-20 pb-16 sm:pt-32 sm:pb-24">
        {/* Background gradient effects */}
        <div className="absolute inset-0 -z-10">
          <div className="absolute top-0 left-1/4 w-96 h-96 bg-blue-500/20 rounded-full blur-3xl animate-float"></div>
          <div className="absolute top-20 right-1/4 w-96 h-96 bg-purple-500/20 rounded-full blur-3xl animate-float" style={{ animationDelay: '1s' }}></div>
          <div className="absolute bottom-0 left-1/2 w-96 h-96 bg-cyan-500/20 rounded-full blur-3xl animate-float" style={{ animationDelay: '2s' }}></div>
        </div>

        <div className="mx-auto max-w-7xl px-6 lg:px-8">
          <div className="mx-auto max-w-3xl text-center">
            {/* Badge */}
            <div className="mb-8 inline-flex items-center gap-2 rounded-full bg-white/5 px-4 py-2 backdrop-blur-sm border border-white/10">
              <Sparkles className="w-4 h-4 text-yellow-400" />
              <span className="text-sm text-gray-300">Propulsé par l'IA Claude Enterprise</span>
            </div>

            {/* Title */}
            <h1 className="text-5xl font-bold tracking-tight sm:text-7xl mb-6">
              <span className="text-gradient">Gestion comptable</span>
              <br />
              <span className="text-white">intelligente pour</span>
              <br />
              <span className="text-gradient">auto-entrepreneurs</span>
            </h1>

            {/* Description */}
            <p className="mt-6 text-lg leading-8 text-gray-300 max-w-2xl mx-auto">
              Factures, URSSAF, bilans : automatisez votre comptabilité en 10 minutes. 
              L'IA s'occupe du reste pendant que vous développez votre business.
            </p>

            {/* CTA Buttons */}
            <div className="mt-10 flex items-center justify-center gap-4">
              <Link
                href="/register"
                className="group flex items-center gap-2 px-8 py-4 text-base font-semibold text-white bg-gradient-brand rounded-xl hover:opacity-90 transition-smooth glow-blue"
              >
                S'inscrire gratuitement
                <ArrowRight className="w-5 h-5 group-hover:translate-x-1 transition-transform" />
              </Link>
              <Link
                href="#features"
                className="px-8 py-4 text-base font-semibold text-gray-300 bg-white/5 rounded-xl hover:bg-white/10 transition-smooth backdrop-blur-sm border border-white/10"
              >
                Découvrir
              </Link>
            </div>

            {/* Social proof */}
            <div className="mt-12 flex items-center justify-center gap-8 text-sm text-gray-400">
              <div className="flex items-center gap-2">
                <CheckCircle2 className="w-5 h-5 text-green-500" />
                <span>Sans engagement</span>
              </div>
              <div className="flex items-center gap-2">
                <CheckCircle2 className="w-5 h-5 text-green-500" />
                <span>14 jours gratuits</span>
              </div>
              <div className="flex items-center gap-2">
                <CheckCircle2 className="w-5 h-5 text-green-500" />
                <span>Aucune CB requise</span>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Stats Section */}
      <section className="border-y border-white/10 bg-white/5 backdrop-blur-sm py-12">
        <div className="mx-auto max-w-7xl px-6 lg:px-8">
          <div className="grid grid-cols-2 gap-8 md:grid-cols-4">
            {stats.map((stat) => (
              <div key={stat.label} className="text-center">
                <div className="text-4xl font-bold text-gradient mb-2">
                  {stat.value}
                </div>
                <div className="text-sm text-gray-400">{stat.label}</div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Features Section */}
      <section id="features" className="py-24 sm:py-32">
        <div className="mx-auto max-w-7xl px-6 lg:px-8">
          {/* Section header */}
          <div className="mx-auto max-w-2xl text-center mb-16">
            <h2 className="text-base font-semibold text-brand-turquoise mb-4">
              Fonctionnalités
            </h2>
            <p className="text-4xl font-bold tracking-tight text-white sm:text-5xl mb-6">
              Tout ce dont vous avez besoin
              <br />
              <span className="text-gradient">en une seule plateforme</span>
            </p>
            <p className="text-lg text-gray-400">
              GestoPro centralise tous les outils essentiels pour gérer votre micro-entreprise efficacement.
            </p>
          </div>

          {/* Features grid */}
          <div className="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3">
            {features.map((feature) => {
              const Icon = feature.icon
              return (
                <div
                  key={feature.title}
                  className="group relative rounded-2xl bg-white/5 p-8 backdrop-blur-sm border border-white/10 hover:border-white/20 transition-smooth hover:bg-white/10"
                >
                  <div className={`inline-flex rounded-lg p-3 bg-white/5 mb-5 ${feature.color}`}>
                    <Icon className="w-6 h-6" />
                  </div>
                  <h3 className="text-xl font-semibold text-white mb-3">
                    {feature.title}
                  </h3>
                  <p className="text-gray-400 leading-relaxed">
                    {feature.description}
                  </p>
                </div>
              )
            })}
          </div>
        </div>
      </section>

      {/* Pricing Section */}
      <section id="pricing" className="py-24 sm:py-32 bg-white/5 backdrop-blur-sm border-y border-white/10">
        <div className="mx-auto max-w-7xl px-6 lg:px-8">
          {/* Section header */}
          <div className="mx-auto max-w-2xl text-center mb-16">
            <h2 className="text-base font-semibold text-brand-turquoise mb-4">
              Tarifs
            </h2>
            <p className="text-4xl font-bold tracking-tight text-white sm:text-5xl mb-6">
              Des prix <span className="text-gradient">transparents</span>
              <br />
              adaptés à votre croissance
            </p>
            <p className="text-lg text-gray-400">
              Commencez gratuitement pendant 14 jours. Sans engagement, sans carte bancaire.
            </p>
          </div>

          {/* Pricing cards */}
          <div className="grid grid-cols-1 gap-8 lg:grid-cols-3">
            {pricing.map((plan) => (
              <div
                key={plan.name}
                className={`relative rounded-2xl p-8 backdrop-blur-sm border transition-smooth ${
                  plan.popular
                    ? 'bg-gradient-brand border-white/20 shadow-2xl scale-105'
                    : 'bg-white/5 border-white/10 hover:border-white/20'
                }`}
              >
                {plan.popular && (
                  <div className="absolute -top-4 left-1/2 -translate-x-1/2">
                    <span className="inline-flex items-center gap-1 rounded-full bg-gradient-to-r from-yellow-400 to-orange-500 px-4 py-1 text-sm font-semibold text-slate-900">
                      <Zap className="w-4 h-4" />
                      Le plus populaire
                    </span>
                  </div>
                )}

                <div className="mb-8">
                  <h3 className="text-2xl font-bold text-white mb-2">
                    {plan.name}
                  </h3>
                  <p className="text-gray-400 mb-6">{plan.description}</p>
                  <div className="flex items-baseline gap-2">
                    <span className="text-5xl font-bold text-white">
                      {plan.price}€
                    </span>
                    <span className="text-gray-400">/mois</span>
                  </div>
                </div>

                <ul className="space-y-4 mb-8">
                  {plan.features.map((feature) => (
                    <li key={feature} className="flex items-start gap-3">
                      <CheckCircle2 className="w-5 h-5 text-green-400 flex-shrink-0 mt-0.5" />
                      <span className="text-gray-300">{feature}</span>
                    </li>
                  ))}
                </ul>

                <Link
                  href="/register"
                  className={`block w-full text-center px-6 py-3 rounded-lg font-semibold transition-smooth ${
                    plan.popular
                      ? 'bg-white text-slate-900 hover:bg-gray-100'
                      : 'bg-white/10 text-white hover:bg-white/20 border border-white/20'
                  }`}
                >
                  {plan.cta}
                </Link>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* CTA Section */}
      <section className="py-24 sm:py-32">
        <div className="mx-auto max-w-7xl px-6 lg:px-8">
          <div className="relative overflow-hidden rounded-3xl bg-gradient-brand p-12 lg:p-20 text-center">
            <div className="relative z-10">
              <h2 className="text-4xl font-bold text-white mb-6">
                Prêt à simplifier votre comptabilité ?
              </h2>
              <p className="text-xl text-white/90 mb-8 max-w-2xl mx-auto">
                Rejoignez les 2500+ auto-entrepreneurs qui gagnent 45h par mois avec GestoPro.
              </p>
              <Link
                href="/register"
                className="inline-flex items-center gap-2 px-8 py-4 text-lg font-semibold text-slate-900 bg-white rounded-xl hover:bg-gray-100 transition-smooth shadow-xl"
              >
                Commencer gratuitement
                <ArrowRight className="w-5 h-5" />
              </Link>
            </div>
            
            {/* Decorative elements */}
            <div className="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
            <div className="absolute bottom-0 left-0 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
          </div>
        </div>
      </section>
    </div>
  )
}
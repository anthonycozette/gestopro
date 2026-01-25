"use client";

import { useState } from "react";
import Link from "next/link";
import {
    Check,
    X,
    ChevronDown,
    ChevronUp,
    Zap,
    Crown,
    Rocket,
    Building2,
} from "lucide-react";

export default function PricingPage() {
    const [billingPeriod, setBillingPeriod] = useState<"monthly" | "yearly">(
        "monthly",
    );
    const [expandedFaq, setExpandedFaq] = useState<number | null>(null);

    const plans = [
        {
            id: "gratuit",
            name: "Gratuit",
            icon: Zap,
            price: { monthly: 0, yearly: 0 },
            description: "Parfait pour tester",
            cta: "Commencer gratuitement",
            ctaLink: "/register",
            highlighted: false,
            features: [
                { name: "Factures", included: true, value: "5/mois" },
                { name: "Devis", included: true, value: "5/mois" },
                { name: "Clients", included: true, value: "1" },
                { name: "Simulateur URSSAF", included: true, value: "1/mois" },
                { name: "Dashboard", included: true, value: "Basique" },
                { name: "Scan IA", included: false },
                { name: "Bilan PDF", included: false },
                { name: "Export", included: false },
                { name: "Portail expert", included: false },
                { name: "Support", included: true, value: "FAQ" },
            ],
        },
        {
            id: "essentiel",
            name: "Essentiel",
            icon: Rocket,
            price: { monthly: 9, yearly: 90 },
            description: "Pour les indépendants",
            cta: "Essayer 14 jours gratuits",
            ctaLink: "https://stripe.com/checkout", // Stripe link
            highlighted: false,
            features: [
                { name: "Factures", included: true, value: "Illimité" },
                { name: "Devis", included: true, value: "Illimité" },
                { name: "Clients", included: true, value: "Illimité" },
                {
                    name: "Simulateur URSSAF",
                    included: true,
                    value: "Illimité",
                },
                { name: "Dashboard", included: true, value: "Complet" },
                { name: "Scan IA", included: true, value: "50 docs/mois" },
                { name: "Bilan PDF", included: true, value: "Basique" },
                { name: "Export", included: true, value: "XML/PDF" },
                { name: "Portail expert", included: false },
                { name: "Support", included: true, value: "Standard" },
            ],
        },
        {
            id: "pro",
            name: "Pro",
            icon: Crown,
            price: { monthly: 19, yearly: 180 },
            description: "Pour les pros & PME",
            cta: "Essayer 14 jours gratuits",
            ctaLink: "https://stripe.com/checkout",
            highlighted: true,
            features: [
                { name: "Factures", included: true, value: "Illimité" },
                { name: "Devis", included: true, value: "Illimité" },
                { name: "Clients", included: true, value: "Illimité" },
                {
                    name: "Simulateur URSSAF",
                    included: true,
                    value: "Illimité",
                },
                { name: "Dashboard", included: true, value: "Avancé" },
                { name: "Scan IA", included: true, value: "Illimité" },
                { name: "Bilan PDF", included: true, value: "Avancé IA" },
                { name: "Export", included: true, value: "Excel + API" },
                { name: "Portail expert", included: true, value: "Validation" },
                { name: "Support", included: true, value: "Prioritaire" },
            ],
        },
        {
            id: "pme",
            name: "PME",
            icon: Building2,
            price: { monthly: 49, yearly: 480 },
            description: "Pour les équipes",
            cta: "Essayer 14 jours gratuits",
            ctaLink: "https://stripe.com/checkout",
            highlighted: false,
            features: [
                { name: "Factures", included: true, value: "Illimité" },
                { name: "Devis", included: true, value: "Illimité" },
                { name: "Clients", included: true, value: "Illimité" },
                {
                    name: "Simulateur URSSAF",
                    included: true,
                    value: "Illimité",
                },
                { name: "Dashboard", included: true, value: "Multi-user" },
                { name: "Scan IA", included: true, value: "Illimité" },
                { name: "Bilan PDF", included: true, value: "Premium IA" },
                { name: "Export", included: true, value: "Tous formats" },
                { name: "Portail expert", included: true, value: "Signature" },
                { name: "Support", included: true, value: "VIP 24/7" },
            ],
        },
    ];

    const faqs = [
        {
            question: "Puis-je changer de plan à tout moment ?",
            answer: "Oui, vous pouvez passer à un plan supérieur ou inférieur à tout moment. Les modifications sont applicables au prochain cycle de facturation.",
        },
        {
            question: "Y a-t-il une période d'essai gratuit ?",
            answer: "Oui, les plans Pro et PME bénéficient d'un essai gratuit de 14 jours. Aucune carte bancaire requise pour commencer.",
        },
        {
            question: "Puis-je exporter mes données ?",
            answer: "Oui, tous les plans payants proposent des exports en XML et PDF. Le plan Pro ajoute Excel et les API.",
        },
        {
            question: "Les calculs URSSAF sont-ils précis ?",
            answer: "Nos calculs sont basés sur les taux officiels URSSAF 2026. Vérifiez toujours avant déclaration. Consultez un expert-comptable pour plus de sécurité.",
        },
        {
            question: "Peut-on avoir un devis personnalisé ?",
            answer: "Oui, pour des besoins spécifiques (intégrations, multi-succursales). Contactez notre équipe : contact@gestopro.fr",
        },
        {
            question: "Vos données sont-elles sécurisées ?",
            answer: "Oui, chiffrement AES-256, hébergement France (OVH), certifications RGPD, et sauvegardes automatiques quotidiennes.",
        },
    ];

    const formatPrice = (price: number) => {
        return new Intl.NumberFormat("fr-FR", {
            style: "currency",
            currency: "EUR",
            minimumFractionDigits: 0,
        }).format(price);
    };

    const getPrice = (plan: any) => {
        const price =
            billingPeriod === "monthly"
                ? plan.price.monthly
                : plan.price.yearly;
        return price;
    };

    const getSavings = (plan: any) => {
        if (plan.price.yearly === 0) return null;
        const monthlyTotal = plan.price.monthly * 12;
        const savings = monthlyTotal - plan.price.yearly;
        return Math.round((savings / monthlyTotal) * 100);
    };

    return (
        <div className="min-h-screen bg-gradient-to-br from-slate-950 via-blue-950 to-slate-900 p-6 lg:p-8">
            <div className="max-w-7xl mx-auto">
                {/* Header */}
                <div className="text-center mb-16">
                    <h1 className="text-5xl lg:text-6xl font-black bg-gradient-to-r from-blue-400 via-teal-400 to-purple-400 bg-clip-text text-transparent mb-6">
                        Tarification simple et transparente
                    </h1>
                    <p className="text-xl text-gray-400 max-w-3xl mx-auto mb-8">
                        Commencez gratuitement, puis choisissez le plan qui vous
                        convient. Pas de frais cachés, annulez quand vous le
                        souhaitez.
                    </p>

                    {/* Billing Toggle */}
                    <div className="flex items-center justify-center gap-4">
                        <button
                            onClick={() => setBillingPeriod("monthly")}
                            className={`px-6 py-2 rounded-lg font-semibold transition-all duration-300 ${
                                billingPeriod === "monthly"
                                    ? "bg-blue-600 text-white"
                                    : "bg-slate-700/30 text-gray-400 hover:bg-slate-700/50"
                            }`}
                        >
                            Mensuel
                        </button>
                        <button
                            onClick={() => setBillingPeriod("yearly")}
                            className={`px-6 py-2 rounded-lg font-semibold transition-all duration-300 relative ${
                                billingPeriod === "yearly"
                                    ? "bg-blue-600 text-white"
                                    : "bg-slate-700/30 text-gray-400 hover:bg-slate-700/50"
                            }`}
                        >
                            Annuel
                            {billingPeriod === "yearly" && (
                                <span className="absolute -top-3 -right-3 bg-gradient-to-r from-green-400 to-emerald-400 text-slate-900 text-xs font-bold px-2 py-1 rounded-full">
                                    -20%
                                </span>
                            )}
                        </button>
                    </div>
                </div>

                {/* Plans Grid */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-16">
                    {plans.map((plan) => {
                        const Icon = plan.icon;
                        const savings = getSavings(plan);
                        const price = getPrice(plan);

                        return (
                            <div
                                key={plan.id}
                                className={`relative rounded-2xl border transition-all duration-300 overflow-hidden ${
                                    plan.highlighted
                                        ? "bg-gradient-to-br from-blue-500/10 to-purple-500/10 border-blue-500/50 shadow-2xl shadow-blue-500/20 transform lg:scale-105"
                                        : "bg-gradient-to-br from-slate-800/50 to-slate-900/50 border-slate-700/50 hover:border-slate-600/50"
                                }`}
                            >
                                {plan.highlighted && (
                                    <div className="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-blue-400 via-teal-400 to-purple-400"></div>
                                )}

                                <div className="p-6 lg:p-8">
                                    {/* Badge populaire */}
                                    {plan.highlighted && (
                                        <div className="absolute top-6 right-6 bg-gradient-to-r from-blue-500 to-purple-500 text-white text-xs font-bold px-3 py-1 rounded-full">
                                            POPULAIRE
                                        </div>
                                    )}

                                    {/* Icône et nom */}
                                    <div className="flex items-center gap-3 mb-2">
                                        <div
                                            className={`p-2 rounded-lg ${
                                                plan.highlighted
                                                    ? "bg-blue-500/20"
                                                    : "bg-slate-700/30"
                                            }`}
                                        >
                                            <Icon className="w-6 h-6 text-blue-400" />
                                        </div>
                                        <h3 className="text-2xl font-bold text-white">
                                            {plan.name}
                                        </h3>
                                    </div>

                                    <p className="text-gray-400 text-sm mb-6">
                                        {plan.description}
                                    </p>

                                    {/* Prix */}
                                    <div className="mb-6">
                                        <div className="flex items-baseline gap-2">
                                            <span className="text-4xl font-black bg-gradient-to-r from-blue-400 to-purple-400 bg-clip-text text-transparent">
                                                {price === 0
                                                    ? "Gratuit"
                                                    : `${formatPrice(price)}`}
                                            </span>
                                            {price > 0 && (
                                                <span className="text-gray-500">
                                                    /
                                                    {billingPeriod === "monthly"
                                                        ? "mois"
                                                        : "an"}
                                                </span>
                                            )}
                                        </div>
                                        {savings &&
                                            billingPeriod === "yearly" && (
                                                <p className="text-sm text-green-400 mt-2">
                                                    Économisez {savings}% en
                                                    annuel
                                                </p>
                                            )}
                                    </div>

                                    {/* CTA Button */}
                                    <Link
                                        href={plan.ctaLink}
                                        className={`w-full flex items-center justify-center px-6 py-3 rounded-xl font-semibold transition-all duration-300 transform hover:scale-105 mb-8 ${
                                            plan.highlighted
                                                ? "bg-gradient-to-r from-blue-600 via-teal-500 to-purple-600 text-white hover:shadow-2xl hover:shadow-blue-500/50"
                                                : "bg-slate-700/30 border border-slate-600/50 text-gray-300 hover:bg-slate-700/50 hover:border-slate-500/50"
                                        }`}
                                    >
                                        {plan.cta}
                                    </Link>

                                    {/* Features List */}
                                    <div className="space-y-3">
                                        {plan.features.map((feature, idx) => (
                                            <div
                                                key={idx}
                                                className="flex items-start gap-3"
                                            >
                                                {feature.included ? (
                                                    <Check className="w-5 h-5 text-green-400 flex-shrink-0 mt-0.5" />
                                                ) : (
                                                    <X className="w-5 h-5 text-gray-600 flex-shrink-0 mt-0.5" />
                                                )}
                                                <div className="flex-1">
                                                    <p
                                                        className={`text-sm ${
                                                            feature.included
                                                                ? "text-gray-300"
                                                                : "text-gray-600"
                                                        }`}
                                                    >
                                                        {feature.name}
                                                    </p>
                                                    {feature.value && (
                                                        <p className="text-xs text-gray-500">
                                                            {feature.value}
                                                        </p>
                                                    )}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            </div>
                        );
                    })}
                </div>

                {/* Comparison Table */}
                <div className="mb-16">
                    <h2 className="text-3xl font-bold text-white text-center mb-8">
                        Comparaison détaillée
                    </h2>

                    <div className="overflow-x-auto bg-gradient-to-br from-slate-800/50 to-slate-900/50 backdrop-blur-xl border border-slate-700/50 rounded-2xl">
                        <table className="w-full">
                            <thead className="bg-slate-800/80 border-b border-slate-700/50">
                                <tr>
                                    <th className="px-6 py-4 text-left text-sm font-bold text-gray-300">
                                        Fonctionnalité
                                    </th>
                                    {plans.map((plan) => (
                                        <th
                                            key={plan.id}
                                            className="px-6 py-4 text-center text-sm font-bold text-gray-300"
                                        >
                                            {plan.name}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-700/30">
                                {[
                                    { label: "Factures", key: "invoices" },
                                    { label: "Clients", key: "clients" },
                                    { label: "Dépenses", key: "expenses" },
                                    { label: "URSSAF", key: "urssaf" },
                                    { label: "Scan IA", key: "scan_ia" },
                                    { label: "Bilan PDF", key: "bilan" },
                                    { label: "Portail expert", key: "expert" },
                                    { label: "Support", key: "support" },
                                ].map((item) => (
                                    <tr
                                        key={item.key}
                                        className="hover:bg-slate-700/20 transition-colors"
                                    >
                                        <td className="px-6 py-4 font-medium text-gray-300">
                                            {item.label}
                                        </td>
                                        {plans.map((plan) => (
                                            <td
                                                key={`${plan.id}-${item.key}`}
                                                className="px-6 py-4 text-center"
                                            >
                                                <Check className="w-5 h-5 text-green-400 mx-auto" />
                                            </td>
                                        ))}
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* FAQ Section */}
                <div className="mb-16">
                    <h2 className="text-3xl font-bold text-white text-center mb-8">
                        Questions fréquentes
                    </h2>

                    <div className="max-w-2xl mx-auto space-y-4">
                        {faqs.map((faq, idx) => (
                            <div
                                key={idx}
                                className="bg-gradient-to-br from-slate-800/50 to-slate-900/50 backdrop-blur-xl border border-slate-700/50 rounded-2xl overflow-hidden hover:border-slate-600/50 transition-all duration-300"
                            >
                                <button
                                    onClick={() =>
                                        setExpandedFaq(
                                            expandedFaq === idx ? null : idx,
                                        )
                                    }
                                    className="w-full px-6 py-4 flex items-center justify-between hover:bg-slate-700/20 transition-colors"
                                >
                                    <h3 className="text-lg font-semibold text-white text-left">
                                        {faq.question}
                                    </h3>
                                    {expandedFaq === idx ? (
                                        <ChevronUp className="w-5 h-5 text-blue-400 flex-shrink-0" />
                                    ) : (
                                        <ChevronDown className="w-5 h-5 text-gray-500 flex-shrink-0" />
                                    )}
                                </button>

                                {expandedFaq === idx && (
                                    <div className="px-6 py-4 border-t border-slate-700/50 bg-slate-700/10">
                                        <p className="text-gray-400 leading-relaxed">
                                            {faq.answer}
                                        </p>
                                    </div>
                                )}
                            </div>
                        ))}
                    </div>
                </div>

                {/* CTA Section */}
                <div className="bg-gradient-to-r from-blue-600/20 via-teal-600/20 to-purple-600/20 border border-blue-500/20 rounded-2xl p-8 lg:p-12 text-center">
                    <h2 className="text-3xl lg:text-4xl font-bold text-white mb-4">
                        Prêt à commencer ?
                    </h2>
                    <p className="text-xl text-gray-400 mb-8">
                        Rejoignez des centaines d'entrepreneurs qui font
                        confiance à GestoPro
                    </p>
                    <div className="flex flex-col sm:flex-row gap-4 justify-center">
                        <Link
                            href="/register"
                            className="inline-flex items-center justify-center px-8 py-4 bg-gradient-to-r from-blue-600 via-teal-500 to-purple-600 text-white font-bold rounded-xl hover:shadow-2xl hover:shadow-blue-500/50 transition-all duration-300 transform hover:scale-105"
                        >
                            Essayer gratuitement
                        </Link>
                        <Link
                            href="/contact"
                            className="inline-flex items-center justify-center px-8 py-4 bg-slate-700/30 border border-slate-600/50 text-gray-300 font-semibold rounded-xl hover:bg-slate-700/50 transition-all duration-300"
                        >
                            Contacter l'équipe
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    );
}

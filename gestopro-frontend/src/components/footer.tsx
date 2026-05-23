import Link from "next/link";
import { Github, Twitter, Linkedin, Mail } from "lucide-react";

export default function Footer() {
    const navigation = {
        product: [
            { name: "Fonctionnalités", href: "#features" },
            { name: "Tarifs", href: "#pricing" },
            { name: "Documentation", href: "/docs" },
            { name: "Roadmap", href: "/roadmap" },
        ],
        company: [
            { name: "À propos", href: "/about" },
            { name: "Blog", href: "/blog" },
            { name: "Carrières", href: "/careers" },
            { name: "Contact", href: "/contact" },
        ],
        legal: [
            { name: "Mentions légales", href: "/legal" },
            { name: "Politique de confidentialité", href: "/privacy" },
            { name: "CGU", href: "/terms" },
            { name: "CGV", href: "/sales-terms" },
        ],
        support: [
            { name: "Centre d'aide", href: "/help" },
            { name: "FAQ", href: "/faq" },
            { name: "Statut", href: "/status" },
            { name: "API", href: "/api" },
        ],
    };

    const social = [
        { name: "Twitter", icon: Twitter, href: "#" },
        { name: "LinkedIn", icon: Linkedin, href: "#" },
        { name: "GitHub", icon: Github, href: "#" },
        { name: "Email", icon: Mail, href: "mailto:contact@gestopro.fr" },
    ];

    return (
        <footer className="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 border-t border-white/10">
            <div className="mx-auto max-w-7xl px-6 py-12 lg:px-8 lg:py-16">
                {/* Section principale */}
                <div className="grid grid-cols-2 gap-8 lg:grid-cols-5">
                    {/* Logo et description */}
                    <div className="col-span-2">
                        <Link
                            href="/"
                            className="flex items-center space-x-2 mb-4"
                        >
                            <div className="w-10 h-10 bg-gradient-brand rounded-lg flex items-center justify-center">
                                <span className="text-white font-bold text-xl">
                                    G
                                </span>
                            </div>
                            <span className="text-xl font-bold text-gradient">
                                GestoPro
                            </span>
                        </Link>
                        <p className="text-sm text-gray-300 max-w-xs mb-6">
                            Solution SaaS de gestion comptable avec IA pour
                            auto-entrepreneurs et micro-entreprises. Simplifiez
                            votre facturation, vos déclarations URSSAF et
                            générez vos bilans automatiquement.
                        </p>
                        {/* Réseaux sociaux */}
                        <div className="flex space-x-4">
                            {social.map((item) => {
                                const Icon = item.icon;
                                return (
                                    <Link
                                        key={item.name}
                                        href={item.href}
                                        className="text-gray-400 hover:text-brand-turquoise transition-smooth"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        <span className="sr-only">
                                            {item.name}
                                        </span>
                                        <Icon className="h-5 w-5" />
                                    </Link>
                                );
                            })}
                        </div>
                    </div>

                    {/* Produit */}
                    <div>
                        <h3 className="text-sm font-semibold text-white mb-4">
                            Produit
                        </h3>
                        <ul className="space-y-3">
                            {navigation.product.map((item) => (
                                <li key={item.name}>
                                    <Link
                                        href={item.href}
                                        className="text-sm text-gray-300 hover:text-brand-turquoise transition-smooth"
                                    >
                                        {item.name}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </div>

                    {/* Entreprise */}
                    <div>
                        <h3 className="text-sm font-semibold text-white mb-4">
                            Entreprise
                        </h3>
                        <ul className="space-y-3">
                            {navigation.company.map((item) => (
                                <li key={item.name}>
                                    <Link
                                        href={item.href}
                                        className="text-sm text-gray-300 hover:text-brand-turquoise transition-smooth"
                                    >
                                        {item.name}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </div>

                    {/* Support */}
                    <div>
                        <h3 className="text-sm font-semibold text-white mb-4">
                            Support
                        </h3>
                        <ul className="space-y-3">
                            {navigation.support.map((item) => (
                                <li key={item.name}>
                                    <Link
                                        href={item.href}
                                        className="text-sm text-gray-300 hover:text-brand-turquoise transition-smooth"
                                    >
                                        {item.name}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </div>
                </div>

                {/* Séparateur */}
                <div className="mt-12 border-t border-white/10 pt-8">
                    <div className="flex flex-col md:flex-row md:items-center md:justify-between">
                        {/* Copyright */}
                        <p className="text-sm text-gray-400">
                            &copy; {new Date().getFullYear()} GestoPro. Tous
                            droits réservés.
                        </p>

                        {/* Liens légaux */}
                        <div className="mt-4 flex flex-wrap gap-4 md:mt-0">
                            {navigation.legal.map((item) => (
                                <Link
                                    key={item.name}
                                    href={item.href}
                                    className="text-sm text-gray-400 hover:text-brand-turquoise transition-smooth"
                                >
                                    {item.name}
                                </Link>
                            ))}
                        </div>
                    </div>

                    {/* Badge conformité */}
                    <div className="mt-6 flex items-center justify-center md:justify-start space-x-4">
                        <span className="text-xs text-gray-400 flex items-center space-x-2">
                            <span className="w-2 h-2 bg-green-500 rounded-full animate-glow"></span>
                            <span>Hébergé en France 🇫🇷</span>
                        </span>
                        <span className="text-xs text-gray-400">
                            RGPD Conforme
                        </span>
                        <span className="text-xs text-gray-400">
                            Certifié ISO 27001
                        </span>
                    </div>
                </div>
            </div>
        </footer>
    );
}

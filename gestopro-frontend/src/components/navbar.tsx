"use client";

import { useState } from "react";
import Link from "next/link";
import { Menu, X } from "lucide-react";

export default function Navbar() {
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

    const navigation = [
        { name: "Fonctionnalités", href: "#features" },
        { name: "Tarifs", href: "#pricing" },
        { name: "À propos", href: "/about" },
        { name: "Contact", href: "/contact" },
    ];

    return (
        <nav className="fixed top-0 left-0 right-0 z-50 bg-slate-900/80 backdrop-blur-lg border-b border-white/10">
            <div className="mx-auto max-w-7xl px-6 lg:px-8">
                <div className="flex h-16 items-center justify-between">
                    {/* Logo */}
                    <div className="flex items-center">
                        <Link href="/" className="flex items-center space-x-2">
                            <div className="w-10 h-10 bg-gradient-brand rounded-lg flex items-center justify-center">
                                <span className="text-white font-bold text-xl">
                                    G
                                </span>
                            </div>
                            <span className="text-xl font-bold text-gradient">
                                GestoPro
                            </span>
                        </Link>
                    </div>

                    {/* Navigation Desktop */}
                    <div className="hidden md:flex md:items-center md:space-x-8">
                        {navigation.map((item) => (
                            <Link
                                key={item.name}
                                href={item.href}
                                className="text-gray-300 hover:text-brand-turquoise transition-smooth text-sm font-medium"
                            >
                                {item.name}
                            </Link>
                        ))}
                    </div>

                    {/* Boutons Desktop */}
                    <div className="hidden md:flex md:items-center md:space-x-4">
                        <Link
                            href="/login"
                            className="text-sm font-medium text-gray-300 hover:text-brand-turquoise transition-smooth"
                        >
                            Connexion
                        </Link>
                        <Link
                            href="/register"
                            className="px-4 py-2 text-sm font-medium text-white bg-gradient-brand rounded-lg hover:opacity-90 transition-smooth glow-blue"
                        >
                            S'inscrire
                        </Link>
                    </div>

                    {/* Bouton Menu Mobile */}
                    <div className="flex md:hidden">
                        <button
                            type="button"
                            className="inline-flex items-center justify-center rounded-md p-2 text-gray-300 hover:bg-white/10 transition-smooth"
                            onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
                        >
                            <span className="sr-only">Ouvrir le menu</span>
                            {mobileMenuOpen ? (
                                <X className="h-6 w-6" aria-hidden="true" />
                            ) : (
                                <Menu className="h-6 w-6" aria-hidden="true" />
                            )}
                        </button>
                    </div>
                </div>
            </div>

            {/* Menu Mobile */}
            {mobileMenuOpen && (
                <div className="md:hidden bg-slate-900/95 backdrop-blur-lg border-t border-white/10">
                    <div className="space-y-1 px-4 pb-3 pt-2">
                        {navigation.map((item) => (
                            <Link
                                key={item.name}
                                href={item.href}
                                className="block rounded-md px-3 py-2 text-base font-medium text-gray-300 hover:bg-white/10 hover:text-brand-turquoise transition-smooth"
                                onClick={() => setMobileMenuOpen(false)}
                            >
                                {item.name}
                            </Link>
                        ))}
                        <div className="mt-4 space-y-2">
                            <Link
                                href="/login"
                                className="block w-full text-center rounded-md px-3 py-2 text-base font-medium text-gray-300 hover:bg-white/10 hover:text-brand-turquoise transition-smooth"
                                onClick={() => setMobileMenuOpen(false)}
                            >
                                Connexion
                            </Link>
                            <Link
                                href="/register"
                                className="block w-full text-center rounded-md px-3 py-2 text-base font-medium text-white bg-gradient-brand hover:opacity-90 transition-smooth"
                                onClick={() => setMobileMenuOpen(false)}
                            >
                                S'inscrire
                            </Link>
                        </div>
                    </div>
                </div>
            )}
        </nav>
    );
}

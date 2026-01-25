"use client";

import { useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import {
    Mail,
    Lock,
    User,
    Building2,
    ArrowRight,
    AlertCircle,
    CheckCircle2,
} from "lucide-react";

export default function RegisterPage() {
    const router = useRouter();
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState("");
    const [formData, setFormData] = useState({
        fullName: "",
        email: "",
        company: "",
        password: "",
        confirmPassword: "",
        acceptTerms: false,
    });

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setLoading(true);
        setError("");

        // Validation
        if (formData.password !== formData.confirmPassword) {
            setError("Les mots de passe ne correspondent pas");
            setLoading(false);
            return;
        }

        if (formData.password.length < 8) {
            setError("Le mot de passe doit contenir au moins 8 caractères");
            setLoading(false);
            return;
        }

        if (!formData.acceptTerms) {
            setError("Vous devez accepter les conditions d'utilisation");
            setLoading(false);
            return;
        }

        try {
            // TODO: Remplacer par l'appel API réel
            const response = await fetch("/api/auth/register", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    fullName: formData.fullName,
                    email: formData.email,
                    company: formData.company,
                    password: formData.password,
                }),
            });

            if (!response.ok) {
                throw new Error("Erreur lors de l'inscription");
            }

            const data = await response.json();

            // Stocker le token
            if (typeof window !== "undefined") {
                localStorage.setItem("token", data.token);
            }

            // Redirection vers le dashboard
            router.push("/dashboard");
        } catch (err: any) {
            setError(err.message || "Une erreur est survenue");
        } finally {
            setLoading(false);
        }
    };

    const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const { name, value, type, checked } = e.target;
        setFormData((prev) => ({
            ...prev,
            [name]: type === "checkbox" ? checked : value,
        }));
    };

    // Password strength indicator
    const getPasswordStrength = () => {
        const { password } = formData;
        if (!password) return { label: "", color: "" };

        if (password.length < 6)
            return { label: "Faible", color: "text-red-400" };
        if (password.length < 10)
            return { label: "Moyen", color: "text-yellow-400" };
        return { label: "Fort", color: "text-green-400" };
    };

    const passwordStrength = getPasswordStrength();

    return (
        <div className="min-h-screen bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950 flex items-center justify-center px-6 py-12">
            {/* Background effects */}
            <div className="absolute inset-0 -z-10">
                <div className="absolute top-1/4 left-1/4 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl animate-float"></div>
                <div
                    className="absolute bottom-1/4 right-1/4 w-96 h-96 bg-purple-500/10 rounded-full blur-3xl animate-float"
                    style={{ animationDelay: "2s" }}
                ></div>
            </div>

            <div className="w-full max-w-md">
                {/* Header */}
                <div className="text-center mb-8">
                    <Link
                        href="/"
                        className="inline-flex items-center space-x-2 mb-6"
                    >
                        <div className="w-12 h-12 bg-gradient-brand rounded-xl flex items-center justify-center">
                            <span className="text-white font-bold text-2xl">
                                G
                            </span>
                        </div>
                        <span className="text-2xl font-bold text-gradient">
                            GestoPro
                        </span>
                    </Link>
                    <h1 className="text-3xl font-bold text-white mb-2">
                        Créer un compte
                    </h1>
                    <p className="text-gray-400">
                        Commencez gratuitement pendant 14 jours
                    </p>
                </div>

                {/* Form card */}
                <div className="bg-white/5 backdrop-blur-lg rounded-2xl p-8 border border-white/10">
                    {/* Error message */}
                    {error && (
                        <div className="mb-6 p-4 bg-red-500/10 border border-red-500/20 rounded-lg flex items-start gap-3">
                            <AlertCircle className="w-5 h-5 text-red-400 flex-shrink-0 mt-0.5" />
                            <p className="text-sm text-red-400">{error}</p>
                        </div>
                    )}

                    <form onSubmit={handleSubmit} className="space-y-5">
                        {/* Full Name */}
                        <div>
                            <label
                                htmlFor="fullName"
                                className="block text-sm font-medium text-gray-300 mb-2"
                            >
                                Nom complet
                            </label>
                            <div className="relative">
                                <User className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                                <input
                                    id="fullName"
                                    name="fullName"
                                    type="text"
                                    required
                                    value={formData.fullName}
                                    onChange={handleChange}
                                    className="w-full pl-10 pr-4 py-3 bg-white/5 border border-white/10 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-brand-turquoise transition-smooth"
                                    placeholder="Jean Dupont"
                                />
                            </div>
                        </div>

                        {/* Email */}
                        <div>
                            <label
                                htmlFor="email"
                                className="block text-sm font-medium text-gray-300 mb-2"
                            >
                                Email professionnel
                            </label>
                            <div className="relative">
                                <Mail className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                                <input
                                    id="email"
                                    name="email"
                                    type="email"
                                    required
                                    value={formData.email}
                                    onChange={handleChange}
                                    className="w-full pl-10 pr-4 py-3 bg-white/5 border border-white/10 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-brand-turquoise transition-smooth"
                                    placeholder="vous@entreprise.fr"
                                />
                            </div>
                        </div>

                        {/* Company */}
                        <div>
                            <label
                                htmlFor="company"
                                className="block text-sm font-medium text-gray-300 mb-2"
                            >
                                Nom de l'entreprise
                            </label>
                            <div className="relative">
                                <Building2 className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                                <input
                                    id="company"
                                    name="company"
                                    type="text"
                                    required
                                    value={formData.company}
                                    onChange={handleChange}
                                    className="w-full pl-10 pr-4 py-3 bg-white/5 border border-white/10 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-brand-turquoise transition-smooth"
                                    placeholder="Mon Entreprise SARL"
                                />
                            </div>
                        </div>

                        {/* Password */}
                        <div>
                            <label
                                htmlFor="password"
                                className="block text-sm font-medium text-gray-300 mb-2"
                            >
                                Mot de passe
                            </label>
                            <div className="relative">
                                <Lock className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                                <input
                                    id="password"
                                    name="password"
                                    type="password"
                                    required
                                    value={formData.password}
                                    onChange={handleChange}
                                    className="w-full pl-10 pr-4 py-3 bg-white/5 border border-white/10 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-brand-turquoise transition-smooth"
                                    placeholder="••••••••"
                                />
                            </div>
                            {formData.password && (
                                <p
                                    className={`text-xs mt-1 ${passwordStrength.color}`}
                                >
                                    Force : {passwordStrength.label}
                                </p>
                            )}
                        </div>

                        {/* Confirm Password */}
                        <div>
                            <label
                                htmlFor="confirmPassword"
                                className="block text-sm font-medium text-gray-300 mb-2"
                            >
                                Confirmer le mot de passe
                            </label>
                            <div className="relative">
                                <Lock className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                                <input
                                    id="confirmPassword"
                                    name="confirmPassword"
                                    type="password"
                                    required
                                    value={formData.confirmPassword}
                                    onChange={handleChange}
                                    className="w-full pl-10 pr-4 py-3 bg-white/5 border border-white/10 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-brand-turquoise transition-smooth"
                                    placeholder="••••••••"
                                />
                            </div>
                        </div>

                        {/* Terms */}
                        <div>
                            <label className="flex items-start gap-3 cursor-pointer">
                                <input
                                    type="checkbox"
                                    name="acceptTerms"
                                    checked={formData.acceptTerms}
                                    onChange={handleChange}
                                    className="w-4 h-4 mt-1 rounded border-white/20 bg-white/5 text-brand-blue focus:ring-brand-blue focus:ring-offset-0"
                                />
                                <span className="text-sm text-gray-300">
                                    J'accepte les{" "}
                                    <Link
                                        href="/terms"
                                        className="text-brand-turquoise hover:text-brand-blue transition-smooth"
                                    >
                                        conditions d'utilisation
                                    </Link>{" "}
                                    et la{" "}
                                    <Link
                                        href="/privacy"
                                        className="text-brand-turquoise hover:text-brand-blue transition-smooth"
                                    >
                                        politique de confidentialité
                                    </Link>
                                </span>
                            </label>
                        </div>

                        {/* Submit button */}
                        <button
                            type="submit"
                            disabled={loading}
                            className="group w-full flex items-center justify-center gap-2 px-6 py-3 bg-gradient-brand text-white font-semibold rounded-lg hover:opacity-90 transition-smooth disabled:opacity-50 disabled:cursor-not-allowed glow-blue"
                        >
                            {loading ? (
                                <span>Création du compte...</span>
                            ) : (
                                <>
                                    <span>Créer mon compte</span>
                                    <ArrowRight className="w-5 h-5 group-hover:translate-x-1 transition-transform" />
                                </>
                            )}
                        </button>
                    </form>

                    {/* Benefits */}
                    <div className="mt-6 pt-6 border-t border-white/10">
                        <p className="text-xs text-gray-400 mb-3">
                            Inclus dans votre essai gratuit :
                        </p>
                        <div className="space-y-2">
                            {[
                                "14 jours d'essai gratuit",
                                "Aucune carte bancaire requise",
                                "Accès à toutes les fonctionnalités",
                                "Support prioritaire",
                            ].map((benefit) => (
                                <div
                                    key={benefit}
                                    className="flex items-center gap-2"
                                >
                                    <CheckCircle2 className="w-4 h-4 text-green-400 flex-shrink-0" />
                                    <span className="text-sm text-gray-300">
                                        {benefit}
                                    </span>
                                </div>
                            ))}
                        </div>
                    </div>

                    {/* Login link */}
                    <div className="text-center mt-6">
                        <p className="text-gray-400">
                            Vous avez déjà un compte ?{" "}
                            <Link
                                href="/login"
                                className="text-brand-turquoise hover:text-brand-blue font-semibold transition-smooth"
                            >
                                Se connecter
                            </Link>
                        </p>
                    </div>
                </div>

                {/* Back to home */}
                <div className="text-center mt-6">
                    <Link
                        href="/"
                        className="text-sm text-gray-400 hover:text-gray-300 transition-smooth"
                    >
                        ← Retour à l'accueil
                    </Link>
                </div>
            </div>
        </div>
    );
}

"use client";

import { useState } from "react";
import Link from "next/link";
import {
    ChevronLeft,
    Calculator,
    TrendingUp,
    DollarSign,
    Calendar,
    AlertCircle,
    Info,
    Download,
} from "lucide-react";

export default function UrssafPage() {
    const [turnover, setTurnover] = useState(50000);
    const [socialRegime, setSocialRegime] = useState("micro");
    const [activityType, setActivityType] = useState("services");
    const [selectedQuarter, setSelectedQuarter] = useState("q1");

    // URSSAF rates for 2026 (France)
    const rates = {
        micro: {
            services: {
                contribution: 0.225, // 22.5%
                label: "Services (22.5%)",
            },
            ecommerce: {
                contribution: 0.225,
                label: "E-commerce (22.5%)",
            },
            vente: {
                contribution: 0.145, // 14.5%
                label: "Vente (14.5%)",
            },
        },
        real: {
            services: {
                contribution: 0.45, // 45%
                label: "Services (45%)",
            },
            ecommerce: {
                contribution: 0.45,
                label: "E-commerce (45%)",
            },
            vente: {
                contribution: 0.415, // 41.5%
                label: "Vente (41.5%)",
            },
        },
    };

    const thresholds = {
        micro: 72600,
        real: 72600,
    };

    const calculateContributions = () => {
        const rate = rates[socialRegime][activityType].contribution;
        const annualContribution = turnover * rate;
        const quarterlyContribution = annualContribution / 4;

        // Additional micro-social contributions (ACRE may apply for first year)
        const microAdditional = socialRegime === "micro" ? turnover * 0.025 : 0; // ~2.5% additional
        const realAdditional = socialRegime === "real" ? turnover * 0.05 : 0; // ~5% additional

        return {
            baseContribution: annualContribution,
            additionalContributions: microAdditional + realAdditional,
            total: annualContribution + microAdditional + realAdditional,
            quarterly: quarterlyContribution,
        };
    };

    const contributions = calculateContributions();

    const exemptions = {
        acre: {
            label: "ACRE (Aide aux Créateurs)",
            savings:
                socialRegime === "micro" ? turnover * 0.15 : turnover * 0.3,
            conditions: "Première année d'activité (ou conditions spécifiques)",
        },
        disabilities: {
            label: "Travailleurs handicapés",
            savings: contributions.total * 0.5,
            conditions: "Reconnaissance RQTH",
        },
    };

    const quarters = [
        { id: "q1", label: "Q1 (Jan-Mar)", months: "Jan - Mar" },
        { id: "q2", label: "Q2 (Avr-Jun)", months: "Avr - Jun" },
        { id: "q3", label: "Q3 (Jul-Sep)", months: "Jul - Sep" },
        { id: "q4", label: "Q4 (Oct-Déc)", months: "Oct - Déc" },
    ];

    const monthlyProjection = Array.from({ length: 12 }, (_, i) => ({
        month: [
            "Jan",
            "Fév",
            "Mar",
            "Avr",
            "Mai",
            "Juin",
            "Juil",
            "Aoû",
            "Sep",
            "Oct",
            "Nov",
            "Déc",
        ][i],
        turnover: Math.round(turnover / 12),
        contribution: Math.round(contributions.quarterly / 3),
    }));

    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat("fr-FR", {
            style: "currency",
            currency: "EUR",
            minimumFractionDigits: 2,
        }).format(amount);
    };

    const profitability = {
        grossRevenue: turnover,
        socialContributions: contributions.total,
        netRevenue: turnover - contributions.total,
        profitMargin: ((turnover - contributions.total) / turnover) * 100,
    };

    return (
        <div className="min-h-screen bg-gradient-to-br from-slate-950 via-blue-950 to-slate-900 p-6 lg:p-8">
            <div className="max-w-7xl mx-auto">
                {/* Header */}
                <div className="mb-8">
                    <Link
                        href="/accounting"
                        className="inline-flex items-center gap-2 px-4 py-2 mb-6 text-gray-400 hover:text-blue-300 transition-all duration-200 group"
                    >
                        <ChevronLeft className="w-5 h-5 group-hover:-translate-x-1 transition-transform" />
                        <span className="font-medium">Retour comptabilité</span>
                    </Link>

                    <div>
                        <h1 className="text-4xl font-black bg-gradient-to-r from-blue-400 via-teal-400 to-purple-400 bg-clip-text text-transparent mb-2">
                            Simulateur URSSAF
                        </h1>
                        <p className="text-gray-400">
                            Estimez vos cotisations sociales pour 2026
                        </p>
                    </div>
                </div>

                {/* Input Section */}
                <div className="bg-gradient-to-br from-slate-800/50 to-slate-900/50 backdrop-blur-xl border border-slate-700/50 rounded-2xl p-6 mb-6">
                    <h2 className="text-xl font-bold text-white mb-6 flex items-center gap-3">
                        <Calculator className="w-5 h-5 text-blue-400" />
                        Paramètres
                    </h2>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        {/* Turnover Input */}
                        <div>
                            <label className="block text-sm font-medium text-gray-300 mb-3">
                                Chiffre d'affaires annuel (€)
                            </label>
                            <div className="relative">
                                <DollarSign className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-500" />
                                <input
                                    type="number"
                                    value={turnover}
                                    onChange={(e) =>
                                        setTurnover(Number(e.target.value))
                                    }
                                    step="5000"
                                    min="0"
                                    className="w-full pl-12 pr-4 py-3 bg-slate-700/30 border border-slate-600/50 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:border-blue-500/50 focus:bg-slate-700/50 transition-all duration-300"
                                />
                            </div>
                            <div className="mt-2 text-xs text-gray-500">
                                Threshold:{" "}
                                {formatCurrency(thresholds[socialRegime])}
                                {turnover > thresholds[socialRegime] && (
                                    <span className="text-orange-400 ml-2">
                                        ⚠️ Dépassement
                                    </span>
                                )}
                            </div>
                        </div>

                        {/* Social Regime */}
                        <div>
                            <label className="block text-sm font-medium text-gray-300 mb-3">
                                Régime social
                            </label>
                            <select
                                value={socialRegime}
                                onChange={(e) =>
                                    setSocialRegime(
                                        e.target.value as typeof socialRegime,
                                    )
                                }
                                className="w-full px-4 py-3 bg-slate-700/30 border border-slate-600/50 rounded-xl text-white focus:outline-none focus:border-blue-500/50 focus:bg-slate-700/50 transition-all duration-300 appearance-none"
                            >
                                <option value="micro">Micro-entreprise</option>
                                <option value="real">Réel (EIRL/SARL)</option>
                            </select>
                            <div className="mt-2 text-xs text-gray-500">
                                {socialRegime === "micro"
                                    ? "Régime simplifié"
                                    : "Régime complet"}
                            </div>
                        </div>

                        {/* Activity Type */}
                        <div>
                            <label className="block text-sm font-medium text-gray-300 mb-3">
                                Type d'activité
                            </label>
                            <select
                                value={activityType}
                                onChange={(e) =>
                                    setActivityType(
                                        e.target.value as typeof activityType,
                                    )
                                }
                                className="w-full px-4 py-3 bg-slate-700/30 border border-slate-600/50 rounded-xl text-white focus:outline-none focus:border-blue-500/50 focus:bg-slate-700/50 transition-all duration-300 appearance-none"
                            >
                                <option value="services">
                                    {rates[socialRegime].services.label}
                                </option>
                                <option value="ecommerce">
                                    {rates[socialRegime].ecommerce.label}
                                </option>
                                <option value="vente">
                                    {rates[socialRegime].vente.label}
                                </option>
                            </select>
                            <div className="mt-2 text-xs text-gray-500">
                                Taux applicable
                            </div>
                        </div>
                    </div>
                </div>

                {/* Main Results */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    {/* Annual Summary */}
                    <div className="bg-gradient-to-br from-slate-800/50 to-slate-900/50 backdrop-blur-xl border border-slate-700/50 rounded-2xl p-6">
                        <h2 className="text-xl font-bold text-white mb-6 flex items-center gap-3">
                            <TrendingUp className="w-5 h-5 text-green-400" />
                            Récapitulatif annuel
                        </h2>

                        <div className="space-y-4">
                            <div className="flex items-center justify-between p-4 bg-slate-700/20 border border-slate-600/30 rounded-xl">
                                <span className="text-gray-400">CA annuel</span>
                                <span className="text-xl font-bold text-white">
                                    {formatCurrency(profitability.grossRevenue)}
                                </span>
                            </div>

                            <div className="flex items-center justify-between p-4 bg-red-500/5 border border-red-500/20 rounded-xl">
                                <div>
                                    <span className="text-gray-400">
                                        Cotisations sociales
                                    </span>
                                    <div className="text-xs text-gray-500 mt-1">
                                        (Base{" "}
                                        {(
                                            rates[socialRegime][activityType]
                                                .contribution * 100
                                        ).toFixed(1)}
                                        %)
                                    </div>
                                </div>
                                <span className="text-xl font-bold text-red-400">
                                    -{formatCurrency(contributions.total)}
                                </span>
                            </div>

                            <div className="flex items-center justify-between p-4 bg-gradient-to-r from-blue-500/10 to-purple-500/10 border border-blue-500/20 rounded-xl">
                                <span className="text-gray-300 font-medium">
                                    Revenu net
                                </span>
                                <span className="text-2xl font-black bg-gradient-to-r from-blue-400 to-purple-400 bg-clip-text text-transparent">
                                    {formatCurrency(profitability.netRevenue)}
                                </span>
                            </div>

                            <div className="flex items-center justify-between p-4 bg-slate-700/20 border border-slate-600/30 rounded-xl">
                                <span className="text-gray-400">
                                    Marge nette
                                </span>
                                <span className="text-lg font-bold text-teal-300">
                                    {profitability.profitMargin.toFixed(1)}%
                                </span>
                            </div>
                        </div>
                    </div>

                    {/* Quarterly Breakdown */}
                    <div className="bg-gradient-to-br from-slate-800/50 to-slate-900/50 backdrop-blur-xl border border-slate-700/50 rounded-2xl p-6">
                        <h2 className="text-xl font-bold text-white mb-6 flex items-center gap-3">
                            <Calendar className="w-5 h-5 text-orange-400" />
                            Par trimestre
                        </h2>

                        <div className="space-y-3">
                            {quarters.map((q) => (
                                <button
                                    key={q.id}
                                    onClick={() => setSelectedQuarter(q.id)}
                                    className={`w-full p-4 rounded-xl border transition-all duration-200 text-left ${
                                        selectedQuarter === q.id
                                            ? "bg-blue-500/20 border-blue-500/50"
                                            : "bg-slate-700/20 border-slate-600/30 hover:border-slate-600/50"
                                    }`}
                                >
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <div className="font-semibold text-white">
                                                {q.label}
                                            </div>
                                            <div className="text-xs text-gray-500 mt-1">
                                                {q.months}
                                            </div>
                                        </div>
                                        <div className="text-right">
                                            <div className="text-sm text-gray-400">
                                                Cotisations
                                            </div>
                                            <div className="text-lg font-bold text-orange-300">
                                                {formatCurrency(
                                                    contributions.quarterly,
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                </button>
                            ))}
                        </div>
                    </div>
                </div>

                {/* Exemptions & Reductions */}
                <div className="bg-gradient-to-br from-slate-800/50 to-slate-900/50 backdrop-blur-xl border border-slate-700/50 rounded-2xl p-6 mb-6">
                    <h2 className="text-xl font-bold text-white mb-6 flex items-center gap-3">
                        <AlertCircle className="w-5 h-5 text-purple-400" />
                        Exonérations possibles
                    </h2>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {Object.entries(exemptions).map(([key, exemption]) => (
                            <div
                                key={key}
                                className="p-4 bg-purple-500/10 border border-purple-500/20 rounded-xl"
                            >
                                <div className="flex items-start gap-3">
                                    <Info className="w-5 h-5 text-purple-400 flex-shrink-0 mt-1" />
                                    <div className="flex-1">
                                        <h3 className="text-white font-semibold mb-2">
                                            {exemption.label}
                                        </h3>
                                        <p className="text-sm text-gray-400 mb-3">
                                            {exemption.conditions}
                                        </p>
                                        <div className="flex items-end justify-between">
                                            <span className="text-xs text-gray-500">
                                                Économies potentielles:
                                            </span>
                                            <span className="text-lg font-bold text-green-400">
                                                {formatCurrency(
                                                    exemption.savings,
                                                )}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>

                {/* Monthly Projection */}
                <div className="bg-gradient-to-br from-slate-800/50 to-slate-900/50 backdrop-blur-xl border border-slate-700/50 rounded-2xl p-6 mb-6">
                    <h2 className="text-xl font-bold text-white mb-6 flex items-center gap-3">
                        <TrendingUp className="w-5 h-5 text-cyan-400" />
                        Projection mensuelle
                    </h2>

                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="bg-slate-800/50 border-b border-slate-600/30">
                                <tr>
                                    <th className="px-4 py-3 text-left text-sm font-semibold text-gray-300">
                                        Mois
                                    </th>
                                    <th className="px-4 py-3 text-right text-sm font-semibold text-gray-300">
                                        CA estimé
                                    </th>
                                    <th className="px-4 py-3 text-right text-sm font-semibold text-gray-300">
                                        Cotisations
                                    </th>
                                    <th className="px-4 py-3 text-right text-sm font-semibold text-gray-300">
                                        Revenu net
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-700/30">
                                {monthlyProjection.map((row, idx) => (
                                    <tr
                                        key={idx}
                                        className="hover:bg-slate-700/20 transition-all duration-200"
                                    >
                                        <td className="px-4 py-3 text-sm font-medium text-white">
                                            {row.month}
                                        </td>
                                        <td className="px-4 py-3 text-right text-sm text-gray-300">
                                            {formatCurrency(row.turnover)}
                                        </td>
                                        <td className="px-4 py-3 text-right text-sm text-red-400">
                                            -{formatCurrency(row.contribution)}
                                        </td>
                                        <td className="px-4 py-3 text-right text-sm font-semibold text-teal-300">
                                            {formatCurrency(
                                                row.turnover - row.contribution,
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* Info Box */}
                <div className="bg-blue-500/5 border border-blue-500/20 rounded-2xl p-6">
                    <div className="flex gap-4">
                        <Info className="w-6 h-6 text-blue-400 flex-shrink-0 mt-1" />
                        <div>
                            <h3 className="text-white font-semibold mb-2">
                                Information importante
                            </h3>
                            <p className="text-sm text-gray-400 leading-relaxed mb-3">
                                Ce simulateur fourni une estimation basée sur
                                les taux 2026. Les cotisations réelles peuvent
                                varier selon votre situation personnelle
                                (ancienneté, exonérations appliquées, revenus
                                professionnels supplémentaires). Consultez
                                l'URSSAF ou un expert-comptable pour une
                                évaluation précise.
                            </p>
                            <a
                                href="https://www.urssaf.fr"
                                target="_blank"
                                rel="noopener noreferrer"
                                className="inline-flex items-center gap-2 text-blue-400 hover:text-blue-300 transition-colors text-sm font-medium"
                            >
                                Consulter URSSAF.fr
                                <span>→</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

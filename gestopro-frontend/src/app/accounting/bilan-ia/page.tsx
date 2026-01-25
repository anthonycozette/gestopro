"use client";

import { useState } from "react";
import Link from "next/link";
import {
    ChevronLeft,
    BarChart3,
    TrendingUp,
    DollarSign,
    PieChart,
    Download,
    FileText,
    AlertCircle,
    CheckCircle,
    ArrowUpRight,
    ArrowDownLeft,
} from "lucide-react";

export default function BilanPage() {
    const [selectedPeriod, setSelectedPeriod] = useState("2025");
    const [exportFormat, setExportFormat] = useState("pdf");

    // Mock annual data for 2025
    const annualData = {
        year: 2025,
        startDate: "2025-01-01",
        endDate: "2025-12-31",

        // Revenue
        totalRevenue: 245600,
        invoicedRevenue: 245600,
        estimatedRevenue: 0,
        revenueByQuarter: [
            { quarter: "Q1", amount: 58200, percentage: 23.7 },
            { quarter: "Q2", amount: 62100, percentage: 25.3 },
            { quarter: "Q3", amount: 57800, percentage: 23.5 },
            { quarter: "Q4", amount: 67500, percentage: 27.5 },
        ],
        revenueGrowth: 12.5,

        // Expenses
        totalExpenses: 89450,
        expensesByCategory: [
            {
                category: "Bureau",
                amount: 12500,
                percentage: 14.0,
                trend: -2.5,
            },
            {
                category: "Logiciels",
                amount: 8900,
                percentage: 10.0,
                trend: 5.2,
            },
            {
                category: "Transport",
                amount: 6500,
                percentage: 7.3,
                trend: 3.1,
            },
            { category: "Repas", amount: 5200, percentage: 5.8, trend: -1.2 },
            {
                category: "Formation",
                amount: 9800,
                percentage: 11.0,
                trend: 8.9,
            },
            {
                category: "Déplacements",
                amount: 11200,
                percentage: 12.5,
                trend: 4.3,
            },
            {
                category: "Marketing",
                amount: 18900,
                percentage: 21.1,
                trend: 12.5,
            },
            { category: "Télécom", amount: 5400, percentage: 6.0, trend: 1.8 },
            {
                category: "Autres",
                amount: 11050,
                percentage: 12.3,
                trend: -3.5,
            },
        ],

        // Social contributions
        socialContributions: 110520,
        socialContributionsByRegime: {
            baseRate: 0.45,
            additionalRate: 0.05,
        },
        socialContributionsBreakdown: [
            { type: "Base URSSAF", amount: 110520, percentage: 100 },
        ],

        // Taxes
        estimatedTaxes: 11200,
        taxBreakdown: [
            { type: "CFE", amount: 3200, percentage: 28.6 },
            { type: "CVI", amount: 2100, percentage: 18.8 },
            { type: "Impôt sur le revenu", amount: 5900, percentage: 52.6 },
        ],

        // Net income
        netIncome: 34430,
        netIncomeMargin: 14.0,
        netIncomeMonthly: 2869,

        // Key ratios
        profitability: {
            grossMargin: 36.4,
            operatingMargin: 22.1,
            netMargin: 14.0,
        },

        // Monthly data
        monthlyBreakdown: [
            {
                month: "Jan",
                revenue: 18900,
                expenses: 7200,
                social: 8500,
                profit: 3200,
            },
            {
                month: "Fév",
                revenue: 19500,
                expenses: 7100,
                social: 8800,
                profit: 3600,
            },
            {
                month: "Mar",
                revenue: 19800,
                expenses: 7500,
                social: 8900,
                profit: 3400,
            },
            {
                month: "Avr",
                revenue: 20200,
                expenses: 7400,
                social: 9100,
                profit: 3700,
            },
            {
                month: "Mai",
                revenue: 20800,
                expenses: 7800,
                social: 9400,
                profit: 3600,
            },
            {
                month: "Juin",
                revenue: 21100,
                expenses: 7900,
                social: 9500,
                profit: 3700,
            },
            {
                month: "Juil",
                revenue: 19200,
                expenses: 7300,
                social: 8600,
                profit: 3300,
            },
            {
                month: "Aoû",
                revenue: 18900,
                expenses: 7100,
                social: 8500,
                profit: 3300,
            },
            {
                month: "Sep",
                revenue: 19700,
                expenses: 7600,
                social: 8900,
                profit: 3200,
            },
            {
                month: "Oct",
                revenue: 22100,
                expenses: 8200,
                social: 9900,
                profit: 4000,
            },
            {
                month: "Nov",
                revenue: 22700,
                expenses: 8400,
                social: 10200,
                profit: 3900,
            },
            {
                month: "Déc",
                revenue: 22700,
                expenses: 8300,
                social: 10200,
                profit: 4200,
            },
        ],
    };

    const recommendations = [
        {
            id: 1,
            type: "success",
            title: "Croissance revenue",
            description:
                "Votre CA a augmenté de 12.5% par rapport à 2024. Excellent !",
            action: "Poursuivre cette dynamique",
        },
        {
            id: 2,
            type: "warning",
            title: "Marketing élevé",
            description:
                "Les dépenses marketing représentent 21.1% de vos frais. À surveiller.",
            action: "Analyser le ROI marketing",
        },
        {
            id: 3,
            type: "info",
            title: "Cotisations URSSAF",
            description:
                "Vous êtes en régime réel avec taux à 45%. Vérifiez vos exonérations possibles.",
            action: "Consulter un expert-comptable",
        },
        {
            id: 4,
            type: "success",
            title: "Marge nette saine",
            description:
                "Votre marge nette de 14% est conforme aux standards du secteur.",
            action: "Maintenir cet équilibre",
        },
    ];

    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat("fr-FR", {
            style: "currency",
            currency: "EUR",
            minimumFractionDigits: 0,
        }).format(amount);
    };

    const handleExport = () => {
        console.log(`Exporting as ${exportFormat}`);
        alert(`Export PDF en cours...`);
    };

    const getRecommendationIcon = (type: string) => {
        switch (type) {
            case "success":
                return <CheckCircle className="w-5 h-5 text-green-400" />;
            case "warning":
                return <AlertCircle className="w-5 h-5 text-orange-400" />;
            default:
                return <AlertCircle className="w-5 h-5 text-blue-400" />;
        }
    };

    const getRecommendationColor = (type: string) => {
        switch (type) {
            case "success":
                return "bg-green-500/5 border-green-500/20";
            case "warning":
                return "bg-orange-500/5 border-orange-500/20";
            default:
                return "bg-blue-500/5 border-blue-500/20";
        }
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

                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-4xl font-black bg-gradient-to-r from-blue-400 via-teal-400 to-purple-400 bg-clip-text text-transparent mb-2">
                                Bilan annuel
                            </h1>
                            <p className="text-gray-400">
                                Synthèse financière complète {annualData.year}
                            </p>
                        </div>
                        <button
                            onClick={handleExport}
                            className="flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-600 via-teal-500 to-purple-600 text-white font-semibold rounded-xl hover:shadow-2xl hover:shadow-blue-500/50 transition-all duration-300 transform hover:scale-105"
                        >
                            <Download className="w-5 h-5" />
                            <span>Exporter PDF</span>
                        </button>
                    </div>
                </div>

                {/* Key Metrics */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <div className="bg-gradient-to-br from-blue-500/10 to-blue-600/5 backdrop-blur-xl border border-blue-400/20 rounded-2xl p-5 hover:border-blue-400/50 transition-all duration-300 group">
                        <div className="text-sm text-gray-400 mb-2">
                            CA total
                        </div>
                        <div className="text-3xl font-black text-blue-300 group-hover:text-blue-200 transition-colors mb-2">
                            {formatCurrency(annualData.totalRevenue)}
                        </div>
                        <div className="flex items-center gap-2 text-xs text-green-400">
                            <ArrowUpRight className="w-4 h-4" />
                            <span>+{annualData.revenueGrowth}% YoY</span>
                        </div>
                    </div>

                    <div className="bg-gradient-to-br from-red-500/10 to-red-600/5 backdrop-blur-xl border border-red-400/20 rounded-2xl p-5 hover:border-red-400/50 transition-all duration-300 group">
                        <div className="text-sm text-gray-400 mb-2">
                            Charges
                        </div>
                        <div className="text-3xl font-black text-red-300 group-hover:text-red-200 transition-colors mb-2">
                            {formatCurrency(
                                annualData.totalExpenses +
                                    annualData.socialContributions +
                                    annualData.estimatedTaxes,
                            )}
                        </div>
                        <div className="flex items-center gap-2 text-xs text-red-400">
                            <ArrowDownLeft className="w-4 h-4" />
                            <span>
                                {(
                                    ((annualData.totalExpenses +
                                        annualData.socialContributions +
                                        annualData.estimatedTaxes) /
                                        annualData.totalRevenue) *
                                    100
                                ).toFixed(1)}
                                % du CA
                            </span>
                        </div>
                    </div>

                    <div className="bg-gradient-to-br from-green-500/10 to-green-600/5 backdrop-blur-xl border border-green-400/20 rounded-2xl p-5 hover:border-green-400/50 transition-all duration-300 group">
                        <div className="text-sm text-gray-400 mb-2">
                            Revenu net
                        </div>
                        <div className="text-3xl font-black text-green-300 group-hover:text-green-200 transition-colors mb-2">
                            {formatCurrency(annualData.netIncome)}
                        </div>
                        <div className="flex items-center gap-2 text-xs text-green-400">
                            <span>
                                {annualData.netIncomeMargin}% marge nette
                            </span>
                        </div>
                    </div>

                    <div className="bg-gradient-to-br from-purple-500/10 to-purple-600/5 backdrop-blur-xl border border-purple-400/20 rounded-2xl p-5 hover:border-purple-400/50 transition-all duration-300 group">
                        <div className="text-sm text-gray-400 mb-2">
                            Revenu mensuel
                        </div>
                        <div className="text-3xl font-black text-purple-300 group-hover:text-purple-200 transition-colors mb-2">
                            {formatCurrency(annualData.netIncomeMonthly)}
                        </div>
                        <div className="flex items-center gap-2 text-xs text-purple-400">
                            <span>Moyenne annuelle</span>
                        </div>
                    </div>
                </div>

                {/* Revenue & Expenses Breakdown */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    {/* Revenue by Quarter */}
                    <div className="bg-gradient-to-br from-slate-800/50 to-slate-900/50 backdrop-blur-xl border border-slate-700/50 rounded-2xl p-6">
                        <h2 className="text-xl font-bold text-white mb-6 flex items-center gap-3">
                            <TrendingUp className="w-5 h-5 text-blue-400" />
                            CA par trimestre
                        </h2>

                        <div className="space-y-4">
                            {annualData.revenueByQuarter.map((q) => (
                                <div key={q.quarter}>
                                    <div className="flex items-center justify-between mb-2">
                                        <span className="text-gray-300 font-medium">
                                            {q.quarter}
                                        </span>
                                        <span className="text-white font-bold">
                                            {formatCurrency(q.amount)}
                                        </span>
                                    </div>
                                    <div className="w-full bg-slate-700/30 rounded-full h-2 overflow-hidden">
                                        <div
                                            className="bg-gradient-to-r from-blue-500 to-blue-400 h-full rounded-full transition-all duration-300"
                                            style={{
                                                width: `${q.percentage}%`,
                                            }}
                                        />
                                    </div>
                                    <div className="text-xs text-gray-500 mt-1">
                                        {q.percentage}% du total
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>

                    {/* Expenses by Category */}
                    <div className="bg-gradient-to-br from-slate-800/50 to-slate-900/50 backdrop-blur-xl border border-slate-700/50 rounded-2xl p-6">
                        <h2 className="text-xl font-bold text-white mb-6 flex items-center gap-3">
                            <PieChart className="w-5 h-5 text-orange-400" />
                            Dépenses par catégorie
                        </h2>

                        <div className="space-y-3 max-h-96 overflow-y-auto">
                            {annualData.expensesByCategory
                                .sort((a, b) => b.amount - a.amount)
                                .map((exp) => (
                                    <div
                                        key={exp.category}
                                        className="p-3 bg-slate-700/20 border border-slate-600/30 rounded-lg hover:border-slate-600/50 transition-all"
                                    >
                                        <div className="flex items-center justify-between mb-2">
                                            <span className="text-gray-300 font-medium">
                                                {exp.category}
                                            </span>
                                            <div className="flex items-center gap-2">
                                                <span className="text-white font-bold">
                                                    {formatCurrency(exp.amount)}
                                                </span>
                                                <span
                                                    className={`text-xs font-semibold ${
                                                        exp.trend > 0
                                                            ? "text-orange-400"
                                                            : "text-green-400"
                                                    }`}
                                                >
                                                    {exp.trend > 0 ? "+" : ""}
                                                    {exp.trend}%
                                                </span>
                                            </div>
                                        </div>
                                        <div className="w-full bg-slate-700/30 rounded-full h-1.5 overflow-hidden">
                                            <div
                                                className="bg-orange-500 h-full rounded-full"
                                                style={{
                                                    width: `${exp.percentage}%`,
                                                }}
                                            />
                                        </div>
                                    </div>
                                ))}
                        </div>

                        <div className="mt-4 pt-4 border-t border-slate-600/30">
                            <div className="flex items-center justify-between">
                                <span className="text-gray-300 font-medium">
                                    Total dépenses
                                </span>
                                <span className="text-white font-bold text-lg">
                                    {formatCurrency(annualData.totalExpenses)}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Charges Breakdown */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    {/* Social Contributions */}
                    <div className="bg-gradient-to-br from-slate-800/50 to-slate-900/50 backdrop-blur-xl border border-slate-700/50 rounded-2xl p-6">
                        <h2 className="text-xl font-bold text-white mb-6 flex items-center gap-3">
                            <DollarSign className="w-5 h-5 text-red-400" />
                            Cotisations sociales
                        </h2>

                        <div className="space-y-4">
                            <div className="flex items-center justify-between p-4 bg-red-500/10 border border-red-500/20 rounded-xl">
                                <span className="text-gray-300">
                                    Base URSSAF (45%)
                                </span>
                                <span className="text-white font-bold">
                                    {formatCurrency(
                                        annualData.socialContributions,
                                    )}
                                </span>
                            </div>

                            <div className="p-4 bg-slate-700/20 border border-slate-600/30 rounded-xl">
                                <div className="text-sm text-gray-400 mb-3">
                                    Répartition mensuelle
                                </div>
                                <div className="text-2xl font-black text-white">
                                    {formatCurrency(
                                        annualData.socialContributions / 12,
                                    )}
                                </div>
                                <div className="text-xs text-gray-500 mt-2">
                                    Par mois en moyenne
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Taxes */}
                    <div className="bg-gradient-to-br from-slate-800/50 to-slate-900/50 backdrop-blur-xl border border-slate-700/50 rounded-2xl p-6">
                        <h2 className="text-xl font-bold text-white mb-6 flex items-center gap-3">
                            <FileText className="w-5 h-5 text-purple-400" />
                            Taxes & impôts
                        </h2>

                        <div className="space-y-3">
                            {annualData.taxBreakdown.map((tax) => (
                                <div
                                    key={tax.type}
                                    className="flex items-center justify-between p-4 bg-slate-700/20 border border-slate-600/30 rounded-xl"
                                >
                                    <span className="text-gray-300">
                                        {tax.type}
                                    </span>
                                    <div className="text-right">
                                        <div className="text-white font-bold">
                                            {formatCurrency(tax.amount)}
                                        </div>
                                        <div className="text-xs text-gray-500">
                                            {tax.percentage}%
                                        </div>
                                    </div>
                                </div>
                            ))}

                            <div className="mt-4 pt-4 border-t border-slate-600/30">
                                <div className="flex items-center justify-between">
                                    <span className="text-gray-300 font-medium">
                                        Total taxes
                                    </span>
                                    <span className="text-white font-bold text-lg">
                                        {formatCurrency(
                                            annualData.estimatedTaxes,
                                        )}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Profit Margins */}
                <div className="bg-gradient-to-br from-slate-800/50 to-slate-900/50 backdrop-blur-xl border border-slate-700/50 rounded-2xl p-6 mb-6">
                    <h2 className="text-xl font-bold text-white mb-6 flex items-center gap-3">
                        <BarChart3 className="w-5 h-5 text-teal-400" />
                        Marges & ratios
                    </h2>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div className="p-4 bg-slate-700/20 border border-slate-600/30 rounded-xl">
                            <div className="text-sm text-gray-400 mb-2">
                                Marge brute
                            </div>
                            <div className="text-3xl font-black text-teal-300">
                                {annualData.profitability.grossMargin}%
                            </div>
                            <div className="text-xs text-gray-500 mt-2">
                                {formatCurrency(
                                    annualData.totalRevenue -
                                        annualData.totalExpenses,
                                )}
                            </div>
                        </div>

                        <div className="p-4 bg-slate-700/20 border border-slate-600/30 rounded-xl">
                            <div className="text-sm text-gray-400 mb-2">
                                Marge d'exploitation
                            </div>
                            <div className="text-3xl font-black text-cyan-300">
                                {annualData.profitability.operatingMargin}%
                            </div>
                            <div className="text-xs text-gray-500 mt-2">
                                Avant taxes/social
                            </div>
                        </div>

                        <div className="p-4 bg-slate-700/20 border border-slate-600/30 rounded-xl">
                            <div className="text-sm text-gray-400 mb-2">
                                Marge nette
                            </div>
                            <div className="text-3xl font-black text-green-300">
                                {annualData.profitability.netMargin}%
                            </div>
                            <div className="text-xs text-gray-500 mt-2">
                                Résultat final
                            </div>
                        </div>
                    </div>
                </div>

                {/* Recommendations */}
                <div className="bg-gradient-to-br from-slate-800/50 to-slate-900/50 backdrop-blur-xl border border-slate-700/50 rounded-2xl p-6 mb-6">
                    <h2 className="text-xl font-bold text-white mb-6">
                        Recommandations
                    </h2>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {recommendations.map((rec) => (
                            <div
                                key={rec.id}
                                className={`p-4 border rounded-xl ${getRecommendationColor(
                                    rec.type,
                                )}`}
                            >
                                <div className="flex items-start gap-3">
                                    {getRecommendationIcon(rec.type)}
                                    <div className="flex-1">
                                        <h3 className="text-white font-semibold mb-1">
                                            {rec.title}
                                        </h3>
                                        <p className="text-sm text-gray-400 mb-3">
                                            {rec.description}
                                        </p>
                                        <button className="text-xs font-semibold text-blue-400 hover:text-blue-300 transition-colors">
                                            {rec.action} →
                                        </button>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>

                {/* Monthly Breakdown Table */}
                <div className="bg-gradient-to-br from-slate-800/50 to-slate-900/50 backdrop-blur-xl border border-slate-700/50 rounded-2xl p-6">
                    <h2 className="text-xl font-bold text-white mb-6">
                        Détail mensuel
                    </h2>

                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="bg-slate-800/50 border-b border-slate-600/30">
                                <tr>
                                    <th className="px-4 py-3 text-left text-sm font-semibold text-gray-300">
                                        Mois
                                    </th>
                                    <th className="px-4 py-3 text-right text-sm font-semibold text-gray-300">
                                        CA
                                    </th>
                                    <th className="px-4 py-3 text-right text-sm font-semibold text-gray-300">
                                        Dépenses
                                    </th>
                                    <th className="px-4 py-3 text-right text-sm font-semibold text-gray-300">
                                        Social
                                    </th>
                                    <th className="px-4 py-3 text-right text-sm font-semibold text-gray-300">
                                        Revenu net
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-700/30">
                                {annualData.monthlyBreakdown.map((row, idx) => (
                                    <tr
                                        key={idx}
                                        className="hover:bg-slate-700/20 transition-all"
                                    >
                                        <td className="px-4 py-3 text-sm font-medium text-white">
                                            {row.month}
                                        </td>
                                        <td className="px-4 py-3 text-right text-sm text-blue-400 font-semibold">
                                            {formatCurrency(row.revenue)}
                                        </td>
                                        <td className="px-4 py-3 text-right text-sm text-orange-400 font-semibold">
                                            {formatCurrency(row.expenses)}
                                        </td>
                                        <td className="px-4 py-3 text-right text-sm text-red-400 font-semibold">
                                            {formatCurrency(row.social)}
                                        </td>
                                        <td className="px-4 py-3 text-right text-sm text-green-400 font-bold">
                                            {formatCurrency(row.profit)}
                                        </td>
                                    </tr>
                                ))}
                                <tr className="bg-slate-700/20 border-t-2 border-slate-600/50 font-bold">
                                    <td className="px-4 py-4 text-white">
                                        TOTAL
                                    </td>
                                    <td className="px-4 py-4 text-right text-blue-300">
                                        {formatCurrency(
                                            annualData.monthlyBreakdown.reduce(
                                                (sum, m) => sum + m.revenue,
                                                0,
                                            ),
                                        )}
                                    </td>
                                    <td className="px-4 py-4 text-right text-orange-300">
                                        {formatCurrency(
                                            annualData.monthlyBreakdown.reduce(
                                                (sum, m) => sum + m.expenses,
                                                0,
                                            ),
                                        )}
                                    </td>
                                    <td className="px-4 py-4 text-right text-red-300">
                                        {formatCurrency(
                                            annualData.monthlyBreakdown.reduce(
                                                (sum, m) => sum + m.social,
                                                0,
                                            ),
                                        )}
                                    </td>
                                    <td className="px-4 py-4 text-right text-green-300">
                                        {formatCurrency(
                                            annualData.monthlyBreakdown.reduce(
                                                (sum, m) => sum + m.profit,
                                                0,
                                            ),
                                        )}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    );
}

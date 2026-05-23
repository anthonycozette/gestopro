"use client";

import { useState } from "react";
import Link from "next/link";
import {
    TrendingUp,
    TrendingDown,
    FileText,
    Users,
    CreditCard,
    DollarSign,
    Calendar,
    ArrowUpRight,
    ArrowDownRight,
    Plus,
    AlertCircle,
    CheckCircle2,
    Clock,
    BarChart3,
} from "lucide-react";

export default function DashboardPage() {
    // TODO: Remplacer par les vraies données de l'API
    const [stats] = useState({
        revenue: {
            current: 45750,
            previous: 38200,
            change: 19.8,
        },
        invoices: {
            pending: 12,
            paid: 48,
            overdue: 3,
        },
        clients: {
            total: 28,
            active: 23,
            new: 5,
        },
        urssaf: {
            nextPayment: 2840,
            dueDate: "2026-02-15",
            status: "ok",
        },
    });

    const recentInvoices = [
        {
            id: "INV-2026-001",
            client: "SARL Dupont",
            amount: 1250,
            status: "paid",
            date: "2026-01-18",
        },
        {
            id: "INV-2026-002",
            client: "SAS Martin",
            amount: 3400,
            status: "pending",
            date: "2026-01-19",
        },
        {
            id: "INV-2026-003",
            client: "EI Dubois",
            amount: 890,
            status: "overdue",
            date: "2026-01-10",
        },
        {
            id: "INV-2026-004",
            client: "EURL Lambert",
            amount: 2100,
            status: "paid",
            date: "2026-01-17",
        },
    ];

    const quickActions = [
        {
            icon: FileText,
            label: "Nouvelle facture",
            href: "/invoices/new",
            color: "bg-blue-500",
        },
        {
            icon: Users,
            label: "Ajouter un client",
            href: "/clients/new",
            color: "bg-purple-500",
        },
        {
            icon: CreditCard,
            label: "Ajouter une dépense",
            href: "/expenses/new",
            color: "bg-cyan-500",
        },
        {
            icon: BarChart3,
            label: "Voir le bilan IA",
            href: "/accounting/bilan-ia",
            color: "bg-emerald-500",
        },
    ];

    const getStatusBadge = (status: string) => {
        const styles = {
            paid: "bg-green-500/10 text-green-400 border-green-500/20",
            pending: "bg-yellow-500/10 text-yellow-400 border-yellow-500/20",
            overdue: "bg-red-500/10 text-red-400 border-red-500/20",
        };
        const labels = {
            paid: "Payée",
            pending: "En attente",
            overdue: "En retard",
        };
        return (
            <span
                className={`px-2 py-1 rounded-md text-xs font-medium border ${styles[status as keyof typeof styles]}`}
            >
                {labels[status as keyof typeof labels]}
            </span>
        );
    };

    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat("fr-FR", {
            style: "currency",
            currency: "EUR",
        }).format(amount);
    };

    const formatDate = (dateStr: string) => {
        return new Date(dateStr).toLocaleDateString("fr-FR", {
            day: "numeric",
            month: "long",
            year: "numeric",
        });
    };

    return (
        <div className="min-h-screen bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950">
            <div className="max-w-7xl mx-auto px-6 py-8">
                {/* Header */}
                <div className="mb-8">
                    <h1 className="text-3xl font-bold text-white mb-2">
                        Tableau de bord
                    </h1>
                    <p className="text-gray-400">
                        Bienvenue ! Voici un aperçu de votre activité.
                    </p>
                </div>

                {/* Stats Cards */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    {/* Revenue */}
                    <div className="bg-white/5 backdrop-blur-sm border border-white/10 rounded-xl p-6 hover:border-white/20 transition-smooth">
                        <div className="flex items-center justify-between mb-4">
                            <div className="p-2 bg-blue-500/10 rounded-lg">
                                <DollarSign className="w-5 h-5 text-blue-400" />
                            </div>
                            {stats.revenue.change > 0 ? (
                                <div className="flex items-center gap-1 text-green-400 text-sm">
                                    <TrendingUp className="w-4 h-4" />
                                    <span>+{stats.revenue.change}%</span>
                                </div>
                            ) : (
                                <div className="flex items-center gap-1 text-red-400 text-sm">
                                    <TrendingDown className="w-4 h-4" />
                                    <span>{stats.revenue.change}%</span>
                                </div>
                            )}
                        </div>
                        <div className="text-2xl font-bold text-white mb-1">
                            {formatCurrency(stats.revenue.current)}
                        </div>
                        <div className="text-sm text-gray-400">
                            Chiffre d'affaires ce mois
                        </div>
                    </div>

                    {/* Invoices */}
                    <div className="bg-white/5 backdrop-blur-sm border border-white/10 rounded-xl p-6 hover:border-white/20 transition-smooth">
                        <div className="flex items-center justify-between mb-4">
                            <div className="p-2 bg-purple-500/10 rounded-lg">
                                <FileText className="w-5 h-5 text-purple-400" />
                            </div>
                            {stats.invoices.overdue > 0 && (
                                <div className="p-1 bg-red-500/10 rounded-full">
                                    <AlertCircle className="w-4 h-4 text-red-400" />
                                </div>
                            )}
                        </div>
                        <div className="text-2xl font-bold text-white mb-1">
                            {stats.invoices.pending}
                        </div>
                        <div className="text-sm text-gray-400">
                            Factures en attente
                        </div>
                        <div className="mt-3 flex items-center gap-4 text-xs">
                            <span className="text-green-400">
                                {stats.invoices.paid} payées
                            </span>
                            <span className="text-red-400">
                                {stats.invoices.overdue} en retard
                            </span>
                        </div>
                    </div>

                    {/* Clients */}
                    <div className="bg-white/5 backdrop-blur-sm border border-white/10 rounded-xl p-6 hover:border-white/20 transition-smooth">
                        <div className="flex items-center justify-between mb-4">
                            <div className="p-2 bg-cyan-500/10 rounded-lg">
                                <Users className="w-5 h-5 text-cyan-400" />
                            </div>
                            {stats.clients.new > 0 && (
                                <div className="px-2 py-1 bg-green-500/10 rounded-md">
                                    <span className="text-xs text-green-400">
                                        +{stats.clients.new} nouveau
                                    </span>
                                </div>
                            )}
                        </div>
                        <div className="text-2xl font-bold text-white mb-1">
                            {stats.clients.total}
                        </div>
                        <div className="text-sm text-gray-400">
                            Clients totaux
                        </div>
                        <div className="mt-3 text-xs text-gray-500">
                            {stats.clients.active} actifs ce mois
                        </div>
                    </div>

                    {/* URSSAF */}
                    <div className="bg-white/5 backdrop-blur-sm border border-white/10 rounded-xl p-6 hover:border-white/20 transition-smooth">
                        <div className="flex items-center justify-between mb-4">
                            <div className="p-2 bg-emerald-500/10 rounded-lg">
                                <Calendar className="w-5 h-5 text-emerald-400" />
                            </div>
                            <CheckCircle2 className="w-5 h-5 text-green-400" />
                        </div>
                        <div className="text-2xl font-bold text-white mb-1">
                            {formatCurrency(stats.urssaf.nextPayment)}
                        </div>
                        <div className="text-sm text-gray-400">
                            Prochaine déclaration URSSAF
                        </div>
                        <div className="mt-3 flex items-center gap-2 text-xs text-gray-500">
                            <Clock className="w-3 h-3" />
                            <span>
                                Échéance : {formatDate(stats.urssaf.dueDate)}
                            </span>
                        </div>
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Recent Invoices */}
                    <div className="lg:col-span-2 bg-white/5 backdrop-blur-sm border border-white/10 rounded-xl p-6">
                        <div className="flex items-center justify-between mb-6">
                            <h2 className="text-xl font-semibold text-white">
                                Factures récentes
                            </h2>
                            <Link
                                href="/invoices"
                                className="text-sm text-brand-turquoise hover:text-brand-blue transition-smooth flex items-center gap-1"
                            >
                                Voir tout
                                <ArrowUpRight className="w-4 h-4" />
                            </Link>
                        </div>

                        <div className="space-y-4">
                            {recentInvoices.map((invoice) => (
                                <div
                                    key={invoice.id}
                                    className="flex items-center justify-between p-4 bg-white/5 rounded-lg border border-white/5 hover:border-white/10 transition-smooth"
                                >
                                    <div className="flex items-center gap-4">
                                        <div className="p-2 bg-white/5 rounded-lg">
                                            <FileText className="w-5 h-5 text-gray-400" />
                                        </div>
                                        <div>
                                            <div className="text-white font-medium">
                                                {invoice.id}
                                            </div>
                                            <div className="text-sm text-gray-400">
                                                {invoice.client}
                                            </div>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-4">
                                        <div className="text-right">
                                            <div className="text-white font-semibold">
                                                {formatCurrency(invoice.amount)}
                                            </div>
                                            <div className="text-xs text-gray-500">
                                                {formatDate(invoice.date)}
                                            </div>
                                        </div>
                                        {getStatusBadge(invoice.status)}
                                    </div>
                                </div>
                            ))}
                        </div>

                        <Link
                            href="/invoices/new"
                            className="mt-4 w-full flex items-center justify-center gap-2 px-4 py-3 bg-white/5 border border-white/10 rounded-lg text-gray-300 hover:bg-white/10 hover:border-white/20 transition-smooth"
                        >
                            <Plus className="w-5 h-5" />
                            <span>Créer une nouvelle facture</span>
                        </Link>
                    </div>

                    {/* Quick Actions */}
                    <div className="bg-white/5 backdrop-blur-sm border border-white/10 rounded-xl p-6">
                        <h2 className="text-xl font-semibold text-white mb-6">
                            Actions rapides
                        </h2>

                        <div className="space-y-3">
                            {quickActions.map((action) => {
                                const Icon = action.icon;
                                return (
                                    <Link
                                        key={action.label}
                                        href={action.href}
                                        className="group flex items-center gap-3 p-4 bg-white/5 rounded-lg border border-white/5 hover:border-white/20 hover:bg-white/10 transition-smooth"
                                    >
                                        <div
                                            className={`p-2 ${action.color} rounded-lg`}
                                        >
                                            <Icon className="w-5 h-5 text-white" />
                                        </div>
                                        <span className="text-gray-300 group-hover:text-white transition-smooth">
                                            {action.label}
                                        </span>
                                        <ArrowUpRight className="w-4 h-4 text-gray-500 ml-auto group-hover:text-brand-turquoise transition-smooth" />
                                    </Link>
                                );
                            })}
                        </div>

                        {/* Alert URSSAF */}
                        <div className="mt-6 p-4 bg-blue-500/10 border border-blue-500/20 rounded-lg">
                            <div className="flex items-start gap-3">
                                <AlertCircle className="w-5 h-5 text-blue-400 flex-shrink-0 mt-0.5" />
                                <div>
                                    <div className="text-sm font-medium text-blue-400 mb-1">
                                        Déclaration URSSAF
                                    </div>
                                    <div className="text-xs text-gray-400 mb-3">
                                        Votre prochaine déclaration est prévue
                                        le {formatDate(stats.urssaf.dueDate)}
                                    </div>
                                    <Link
                                        href="/accounting/urssaf"
                                        className="text-xs text-blue-400 hover:text-blue-300 transition-smooth underline"
                                    >
                                        Préparer ma déclaration
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

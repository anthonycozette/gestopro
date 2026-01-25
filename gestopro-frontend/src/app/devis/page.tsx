"use client";

import { useState } from "react";
import Link from "next/link";
import {
    Plus,
    Search,
    Filter,
    Download,
    Eye,
    Edit,
    Trash2,
    Copy,
    FileText,
    Calendar,
    DollarSign,
    ChevronLeft,
    ArrowRight,
} from "lucide-react";

export default function DevisPage() {
    const [searchTerm, setSearchTerm] = useState("");
    const [statusFilter, setStatusFilter] = useState("all");
    const [selectedDevis, setSelectedDevis] = useState<string[]>([]);

    // Mock devis data
    const devisList = [
        {
            id: "DEVIS-2026-001",
            client: "SARL Dupont",
            clientEmail: "contact@dupont.fr",
            amount: 2500.0,
            status: "draft",
            date: "2026-01-18",
            validUntil: "2026-02-18",
            items: 4,
        },
        {
            id: "DEVIS-2026-002",
            client: "SAS Martin",
            clientEmail: "info@martin.fr",
            amount: 5200.0,
            status: "sent",
            date: "2026-01-19",
            validUntil: "2026-02-19",
            items: 6,
        },
        {
            id: "DEVIS-2026-003",
            client: "EI Dubois",
            clientEmail: "dubois@mail.fr",
            amount: 1450.0,
            status: "accepted",
            date: "2026-01-15",
            validUntil: "2026-02-15",
            items: 3,
        },
        {
            id: "DEVIS-2026-004",
            client: "EURL Lambert",
            clientEmail: "contact@lambert.fr",
            amount: 3800.0,
            status: "rejected",
            date: "2026-01-10",
            validUntil: "2026-02-10",
            items: 5,
        },
        {
            id: "DEVIS-2026-005",
            client: "SASU Petit",
            clientEmail: "petit@exemple.fr",
            amount: 6750.0,
            status: "sent",
            date: "2026-01-20",
            validUntil: "2026-02-20",
            items: 7,
        },
    ];

    const statusConfig = {
        draft: {
            label: "Brouillon",
            color: "bg-gray-500/10 text-gray-400 border-gray-500/20",
        },
        sent: {
            label: "Envoyé",
            color: "bg-blue-500/10 text-blue-400 border-blue-500/20",
        },
        accepted: {
            label: "Accepté",
            color: "bg-green-500/10 text-green-400 border-green-500/20",
        },
        rejected: {
            label: "Rejeté",
            color: "bg-red-500/10 text-red-400 border-red-500/20",
        },
    };

    const stats = {
        total: devisList.length,
        sent: devisList.filter((d) => d.status === "sent").length,
        accepted: devisList.filter((d) => d.status === "accepted").length,
        rejected: devisList.filter((d) => d.status === "rejected").length,
        totalAmount: devisList.reduce((sum, d) => sum + d.amount, 0),
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
            month: "short",
            year: "numeric",
        });
    };

    const filteredDevis = devisList.filter((devis) => {
        const matchesSearch =
            devis.id.toLowerCase().includes(searchTerm.toLowerCase()) ||
            devis.client.toLowerCase().includes(searchTerm.toLowerCase());
        const matchesStatus =
            statusFilter === "all" || devis.status === statusFilter;
        return matchesSearch && matchesStatus;
    });

    const handleSelectAll = (checked: boolean) => {
        if (checked) {
            setSelectedDevis(filteredDevis.map((d) => d.id));
        } else {
            setSelectedDevis([]);
        }
    };

    const handleSelectDevis = (id: string, checked: boolean) => {
        if (checked) {
            setSelectedDevis((prev) => [...prev, id]);
        } else {
            setSelectedDevis((prev) => prev.filter((dId) => dId !== id));
        }
    };

    const handleDelete = (id: string) => {
        console.log("Delete devis:", id);
    };

    const handleDuplicate = (id: string) => {
        console.log("Duplicate devis:", id);
    };

    const handleConvertToInvoice = (id: string) => {
        console.log("Convert to invoice:", id);
    };

    return (
        <div className="min-h-screen bg-gradient-to-br from-slate-950 via-blue-950 to-slate-900 p-6 lg:p-8">
            <div className="max-w-7xl mx-auto">
                {/* Header */}
                <div className="mb-8">
                    <Link
                        href="/dashboard"
                        className="inline-flex items-center gap-2 px-4 py-2 mb-6 text-gray-400 hover:text-blue-300 transition-all duration-200 group"
                    >
                        <ChevronLeft className="w-5 h-5 group-hover:-translate-x-1 transition-transform" />
                        <span className="font-medium">Retour au dashboard</span>
                    </Link>

                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-4xl font-black bg-gradient-to-r from-blue-400 via-teal-400 to-purple-400 bg-clip-text text-transparent mb-2">
                                Devis
                            </h1>
                            <p className="text-gray-400">
                                Gérez tous vos devis clients
                            </p>
                        </div>
                        <Link
                            href="/devis/new"
                            className="flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-600 via-teal-500 to-purple-600 text-white font-semibold rounded-xl hover:shadow-2xl hover:shadow-blue-500/50 transition-all duration-300 transform hover:scale-105"
                        >
                            <Plus className="w-5 h-5" />
                            <span>Nouveau devis</span>
                        </Link>
                    </div>
                </div>

                {/* Stats */}
                <div className="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
                    <div className="bg-gradient-to-br from-blue-500/10 to-blue-600/5 backdrop-blur-xl border border-blue-400/20 rounded-2xl p-5 hover:border-blue-400/50 transition-all duration-300 group">
                        <div className="text-sm text-gray-400 mb-2">Total</div>
                        <div className="text-3xl font-black text-blue-300 group-hover:text-blue-200 transition-colors">
                            {stats.total}
                        </div>
                        <div className="text-xs text-gray-500 mt-2">devis</div>
                    </div>

                    <div className="bg-gradient-to-br from-cyan-500/10 to-cyan-600/5 backdrop-blur-xl border border-cyan-400/20 rounded-2xl p-5 hover:border-cyan-400/50 transition-all duration-300 group">
                        <div className="text-sm text-gray-400 mb-2">
                            Envoyés
                        </div>
                        <div className="text-3xl font-black text-cyan-300 group-hover:text-cyan-200 transition-colors">
                            {stats.sent}
                        </div>
                        <div className="text-xs text-gray-500 mt-2">
                            en attente
                        </div>
                    </div>

                    <div className="bg-gradient-to-br from-green-500/10 to-green-600/5 backdrop-blur-xl border border-green-400/20 rounded-2xl p-5 hover:border-green-400/50 transition-all duration-300 group">
                        <div className="text-sm text-gray-400 mb-2">
                            Acceptés
                        </div>
                        <div className="text-3xl font-black text-green-400 group-hover:text-green-300 transition-colors">
                            {stats.accepted}
                        </div>
                        <div className="text-xs text-gray-500 mt-2">
                            convertis
                        </div>
                    </div>

                    <div className="bg-gradient-to-br from-red-500/10 to-red-600/5 backdrop-blur-xl border border-red-400/20 rounded-2xl p-5 hover:border-red-400/50 transition-all duration-300 group">
                        <div className="text-sm text-gray-400 mb-2">
                            Rejetés
                        </div>
                        <div className="text-3xl font-black text-red-400 group-hover:text-red-300 transition-colors">
                            {stats.rejected}
                        </div>
                        <div className="text-xs text-gray-500 mt-2">
                            non retenus
                        </div>
                    </div>

                    <div className="bg-gradient-to-br from-purple-500/10 to-purple-600/5 backdrop-blur-xl border border-purple-400/20 rounded-2xl p-5 hover:border-purple-400/50 transition-all duration-300 group">
                        <div className="text-sm text-gray-400 mb-2">Total</div>
                        <div className="text-2xl font-black text-purple-300 group-hover:text-purple-200 transition-colors">
                            {formatCurrency(stats.totalAmount).split(",")[0]}
                        </div>
                        <div className="text-xs text-gray-500 mt-2">
                            montant
                        </div>
                    </div>
                </div>

                {/* Filters & Search */}
                <div className="bg-gradient-to-br from-slate-800/50 to-slate-900/50 backdrop-blur-xl border border-slate-700/50 rounded-2xl p-6 mb-6">
                    <div className="flex flex-col md:flex-row gap-4">
                        {/* Search */}
                        <div className="flex-1 relative">
                            <Search className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-500" />
                            <input
                                type="text"
                                placeholder="Rechercher par numéro ou client..."
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                                className="w-full pl-12 pr-4 py-3 bg-slate-700/30 border border-slate-600/50 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:border-blue-500/50 focus:bg-slate-700/50 transition-all duration-300"
                            />
                        </div>

                        {/* Status filter */}
                        <div className="relative">
                            <Filter className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-500 pointer-events-none" />
                            <select
                                value={statusFilter}
                                onChange={(e) =>
                                    setStatusFilter(e.target.value)
                                }
                                className="pl-12 pr-8 py-3 bg-slate-700/30 border border-slate-600/50 rounded-xl text-white focus:outline-none focus:border-blue-500/50 focus:bg-slate-700/50 transition-all duration-300 appearance-none cursor-pointer"
                            >
                                <option value="all">Tous les statuts</option>
                                <option value="draft">Brouillons</option>
                                <option value="sent">Envoyés</option>
                                <option value="accepted">Acceptés</option>
                                <option value="rejected">Rejetés</option>
                            </select>
                        </div>

                        {/* Export button */}
                        <button className="flex items-center gap-2 px-4 py-3 bg-slate-700/30 border border-slate-600/50 rounded-xl text-gray-300 hover:bg-slate-700/50 hover:border-slate-500/50 transition-all duration-300 font-medium">
                            <Download className="w-5 h-5" />
                            <span>Exporter</span>
                        </button>
                    </div>

                    {/* Selected actions */}
                    {selectedDevis.length > 0 && (
                        <div className="mt-4 pt-4 border-t border-slate-600/30 flex items-center justify-between">
                            <span className="text-sm text-gray-400">
                                {selectedDevis.length} devis sélectionné(s)
                            </span>
                            <div className="flex items-center gap-2">
                                <button className="px-4 py-2 bg-slate-700/30 border border-slate-600/50 rounded-lg text-gray-300 hover:bg-slate-700/50 transition-all text-sm font-medium">
                                    Exporter sélection
                                </button>
                                <button className="px-4 py-2 bg-red-500/10 border border-red-500/20 rounded-lg text-red-400 hover:bg-red-500/20 transition-all text-sm font-medium">
                                    Supprimer sélection
                                </button>
                            </div>
                        </div>
                    )}
                </div>

                {/* Devis Table */}
                <div className="bg-gradient-to-br from-slate-800/50 to-slate-900/50 backdrop-blur-xl border border-slate-700/50 rounded-2xl overflow-hidden shadow-2xl shadow-blue-950/20">
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="bg-gradient-to-r from-slate-800/80 to-slate-900/80 border-b border-slate-700/50">
                                <tr>
                                    <th className="px-6 py-4 text-left">
                                        <input
                                            type="checkbox"
                                            checked={
                                                selectedDevis.length ===
                                                    filteredDevis.length &&
                                                filteredDevis.length > 0
                                            }
                                            onChange={(e) =>
                                                handleSelectAll(
                                                    e.target.checked,
                                                )
                                            }
                                            className="w-4 h-4 rounded border-slate-600 bg-slate-700/50 text-blue-500 focus:ring-blue-500 focus:ring-offset-0 cursor-pointer"
                                        />
                                    </th>
                                    <th className="px-6 py-4 text-left text-sm font-semibold text-gray-300">
                                        Numéro
                                    </th>
                                    <th className="px-6 py-4 text-left text-sm font-semibold text-gray-300">
                                        Client
                                    </th>
                                    <th className="px-6 py-4 text-left text-sm font-semibold text-gray-300">
                                        Montant
                                    </th>
                                    <th className="px-6 py-4 text-left text-sm font-semibold text-gray-300">
                                        Date
                                    </th>
                                    <th className="px-6 py-4 text-left text-sm font-semibold text-gray-300">
                                        Validité
                                    </th>
                                    <th className="px-6 py-4 text-left text-sm font-semibold text-gray-300">
                                        Statut
                                    </th>
                                    <th className="px-6 py-4 text-right text-sm font-semibold text-gray-300">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-700/30">
                                {filteredDevis.map((devis) => (
                                    <tr
                                        key={devis.id}
                                        className="hover:bg-slate-700/20 transition-all duration-200 group"
                                    >
                                        <td className="px-6 py-4">
                                            <input
                                                type="checkbox"
                                                checked={selectedDevis.includes(
                                                    devis.id,
                                                )}
                                                onChange={(e) =>
                                                    handleSelectDevis(
                                                        devis.id,
                                                        e.target.checked,
                                                    )
                                                }
                                                className="w-4 h-4 rounded border-slate-600 bg-slate-700/50 text-blue-500 focus:ring-blue-500 focus:ring-offset-0 cursor-pointer"
                                            />
                                        </td>
                                        <td className="px-6 py-4">
                                            <div className="flex items-center gap-3">
                                                <div className="p-2 bg-gradient-to-br from-purple-500/20 to-purple-600/10 rounded-lg group-hover:from-purple-500/30 group-hover:to-purple-600/20 transition-colors">
                                                    <FileText className="w-4 h-4 text-purple-400" />
                                                </div>
                                                <div>
                                                    <div className="text-white font-semibold group-hover:text-purple-300 transition-colors">
                                                        {devis.id}
                                                    </div>
                                                    <div className="text-xs text-gray-500">
                                                        {devis.items} article(s)
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-6 py-4">
                                            <div>
                                                <div className="text-white font-medium">
                                                    {devis.client}
                                                </div>
                                                <div className="text-xs text-gray-500">
                                                    {devis.clientEmail}
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-6 py-4">
                                            <div className="text-white font-semibold group-hover:text-teal-300 transition-colors">
                                                {formatCurrency(devis.amount)}
                                            </div>
                                        </td>
                                        <td className="px-6 py-4">
                                            <div className="flex items-center gap-2 text-gray-400 text-sm">
                                                <Calendar className="w-4 h-4" />
                                                <span>
                                                    {formatDate(devis.date)}
                                                </span>
                                            </div>
                                        </td>
                                        <td className="px-6 py-4">
                                            <div className="text-gray-400 text-sm">
                                                {formatDate(devis.validUntil)}
                                            </div>
                                        </td>
                                        <td className="px-6 py-4">
                                            <span
                                                className={`inline-flex px-3 py-1 rounded-full text-xs font-semibold border transition-all duration-200 ${
                                                    statusConfig[
                                                        devis.status as keyof typeof statusConfig
                                                    ].color
                                                }`}
                                            >
                                                {
                                                    statusConfig[
                                                        devis.status as keyof typeof statusConfig
                                                    ].label
                                                }
                                            </span>
                                        </td>
                                        <td className="px-6 py-4">
                                            <div className="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                                <Link
                                                    href={`/devis/${devis.id}`}
                                                    className="p-2 text-gray-400 hover:text-blue-400 hover:bg-blue-500/10 rounded-lg transition-all duration-200"
                                                    title="Voir"
                                                >
                                                    <Eye className="w-4 h-4" />
                                                </Link>
                                                <Link
                                                    href={`/devis/${devis.id}/edit`}
                                                    className="p-2 text-gray-400 hover:text-teal-400 hover:bg-teal-500/10 rounded-lg transition-all duration-200"
                                                    title="Modifier"
                                                >
                                                    <Edit className="w-4 h-4" />
                                                </Link>
                                                <button
                                                    onClick={() =>
                                                        handleDuplicate(
                                                            devis.id,
                                                        )
                                                    }
                                                    className="p-2 text-gray-400 hover:text-purple-400 hover:bg-purple-500/10 rounded-lg transition-all duration-200"
                                                    title="Dupliquer"
                                                >
                                                    <Copy className="w-4 h-4" />
                                                </button>
                                                {devis.status ===
                                                    "accepted" && (
                                                    <button
                                                        onClick={() =>
                                                            handleConvertToInvoice(
                                                                devis.id,
                                                            )
                                                        }
                                                        className="p-2 text-gray-400 hover:text-green-400 hover:bg-green-500/10 rounded-lg transition-all duration-200"
                                                        title="Convertir en facture"
                                                    >
                                                        <ArrowRight className="w-4 h-4" />
                                                    </button>
                                                )}
                                                <button
                                                    onClick={() =>
                                                        handleDelete(devis.id)
                                                    }
                                                    className="p-2 text-gray-400 hover:text-red-400 hover:bg-red-500/10 rounded-lg transition-all duration-200"
                                                    title="Supprimer"
                                                >
                                                    <Trash2 className="w-4 h-4" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    {/* Empty state */}
                    {filteredDevis.length === 0 && (
                        <div className="py-16 text-center">
                            <div className="flex justify-center mb-6">
                                <div className="p-4 bg-gradient-to-br from-purple-500/20 to-purple-600/10 rounded-2xl">
                                    <FileText className="w-12 h-12 text-gray-400" />
                                </div>
                            </div>
                            <p className="text-gray-300 mb-2 font-semibold text-lg">
                                Aucun devis trouvé
                            </p>
                            <p className="text-gray-500 text-sm mb-8">
                                {searchTerm || statusFilter !== "all"
                                    ? "Essayez de modifier vos filtres"
                                    : "Commencez par créer votre premier devis"}
                            </p>
                            <Link
                                href="/devis/new"
                                className="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-600 via-teal-500 to-purple-600 text-white font-semibold rounded-xl hover:shadow-2xl hover:shadow-blue-500/50 transition-all duration-300 transform hover:scale-105"
                            >
                                <Plus className="w-5 h-5" />
                                <span>Créer un devis</span>
                            </Link>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}

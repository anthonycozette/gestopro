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
    FileText,
    Calendar,
    DollarSign,
    ChevronLeft,
    TrendingUp,
} from "lucide-react";

export default function InvoicesPage() {
    const [searchTerm, setSearchTerm] = useState("");
    const [statusFilter, setStatusFilter] = useState("all");
    const [selectedInvoices, setSelectedInvoices] = useState<string[]>([]);
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [editingId, setEditingId] = useState<string | null>(null);
    const [formData, setFormData] = useState({
        clientId: "",
        invoiceNumber: `INV-${new Date().getFullYear()}-${String(Math.floor(Math.random() * 1000)).padStart(3, "0")}`,
        invoiceDate: new Date().toISOString().split("T")[0],
        dueDate: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000)
            .toISOString()
            .split("T")[0],
        amount: "",
        status: "draft",
    });

    // Mock clients
    const clients = [
        { id: "1", name: "SARL Dupont", email: "contact@dupont.fr" },
        { id: "2", name: "SAS Martin", email: "info@martin.fr" },
        { id: "3", name: "EI Dubois", email: "dubois@mail.fr" },
        { id: "4", name: "EURL Lambert", email: "contact@lambert.fr" },
    ];

    // Mock invoices
    const invoices = [
        {
            id: "INV-2026-001",
            client: "SARL Dupont",
            clientEmail: "contact@dupont.fr",
            clientId: "1",
            amount: 1250.0,
            status: "paid",
            date: "2026-01-18",
            dueDate: "2026-02-18",
            items: 3,
        },
        {
            id: "INV-2026-002",
            client: "SAS Martin",
            clientEmail: "info@martin.fr",
            clientId: "2",
            amount: 3400.0,
            status: "pending",
            date: "2026-01-19",
            dueDate: "2026-02-19",
            items: 5,
        },
        {
            id: "INV-2026-003",
            client: "EI Dubois",
            clientEmail: "dubois@mail.fr",
            clientId: "3",
            amount: 890.0,
            status: "overdue",
            date: "2026-01-10",
            dueDate: "2026-02-10",
            items: 2,
        },
        {
            id: "INV-2026-004",
            client: "EURL Lambert",
            clientEmail: "contact@lambert.fr",
            clientId: "4",
            amount: 2100.0,
            status: "paid",
            date: "2026-01-17",
            dueDate: "2026-02-17",
            items: 4,
        },
        {
            id: "INV-2026-005",
            client: "SASU Petit",
            clientEmail: "petit@exemple.fr",
            clientId: "1",
            amount: 1750.0,
            status: "draft",
            date: "2026-01-20",
            dueDate: "2026-02-20",
            items: 3,
        },
    ];

    const statusConfig = {
        paid: {
            label: "Payée",
            color: "bg-green-500/10 text-green-400 border-green-500/20",
        },
        pending: {
            label: "En attente",
            color: "bg-yellow-500/10 text-yellow-400 border-yellow-500/20",
        },
        overdue: {
            label: "En retard",
            color: "bg-red-500/10 text-red-400 border-red-500/20",
        },
        draft: {
            label: "Brouillon",
            color: "bg-gray-500/10 text-gray-400 border-gray-500/20",
        },
    };

    const stats = {
        total: invoices.length,
        paid: invoices.filter((i) => i.status === "paid").length,
        pending: invoices.filter((i) => i.status === "pending").length,
        overdue: invoices.filter((i) => i.status === "overdue").length,
        totalAmount: invoices.reduce((sum, inv) => sum + inv.amount, 0),
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

    const filteredInvoices = invoices.filter((invoice) => {
        const matchesSearch =
            invoice.id.toLowerCase().includes(searchTerm.toLowerCase()) ||
            invoice.client.toLowerCase().includes(searchTerm.toLowerCase());
        const matchesStatus =
            statusFilter === "all" || invoice.status === statusFilter;
        return matchesSearch && matchesStatus;
    });

    const handleSelectAll = (checked: boolean) => {
        if (checked) {
            setSelectedInvoices(filteredInvoices.map((inv) => inv.id));
        } else {
            setSelectedInvoices([]);
        }
    };

    const handleSelectInvoice = (id: string, checked: boolean) => {
        if (checked) {
            setSelectedInvoices((prev) => [...prev, id]);
        } else {
            setSelectedInvoices((prev) => prev.filter((invId) => invId !== id));
        }
    };

    const handleOpenModal = (invoice?: any) => {
        if (invoice) {
            setEditingId(invoice.id);
            setFormData({
                clientId: invoice.clientId,
                invoiceNumber: invoice.id,
                invoiceDate: invoice.date,
                dueDate: invoice.dueDate,
                amount: invoice.amount.toString(),
                status: invoice.status,
            });
        } else {
            setEditingId(null);
            setFormData({
                clientId: "",
                invoiceNumber: `INV-${new Date().getFullYear()}-${String(Math.floor(Math.random() * 1000)).padStart(3, "0")}`,
                invoiceDate: new Date().toISOString().split("T")[0],
                dueDate: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000)
                    .toISOString()
                    .split("T")[0],
                amount: "",
                status: "draft",
            });
        }
        setIsModalOpen(true);
    };

    const handleCloseModal = () => {
        setIsModalOpen(false);
        setEditingId(null);
    };

    const handleSave = () => {
        if (!formData.clientId || !formData.amount) {
            alert("Client et montant requis");
            return;
        }
        console.log("Save invoice:", editingId, formData);
        handleCloseModal();
    };

    const handleDelete = (id: string) => {
        console.log("Delete invoice:", id);
    };

    const handleInputChange = (
        e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>,
    ) => {
        const { name, value } = e.target;
        setFormData((prev) => ({ ...prev, [name]: value }));
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
                                Factures
                            </h1>
                            <p className="text-gray-400">
                                Gérez toutes vos factures clients en un seul
                                endroit
                            </p>
                        </div>
                        <button
                            onClick={() => handleOpenModal()}
                            className="flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-600 via-teal-500 to-purple-600 text-white font-semibold rounded-xl hover:shadow-2xl hover:shadow-blue-500/50 transition-all duration-300 transform hover:scale-105"
                        >
                            <Plus className="w-5 h-5" />
                            <span>Nouvelle facture</span>
                        </button>
                    </div>
                </div>

                {/* Stats */}
                <div className="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
                    <div className="bg-gradient-to-br from-blue-500/10 to-blue-600/5 backdrop-blur-xl border border-blue-400/20 rounded-2xl p-6 hover:border-blue-400/50 transition-all duration-300">
                        <div className="flex items-center justify-between">
                            <div>
                                <div className="text-sm text-gray-400 mb-2">
                                    Total
                                </div>
                                <div className="text-4xl font-black text-blue-300">
                                    {stats.total}
                                </div>
                            </div>
                            <div className="p-3 bg-blue-500/20 rounded-xl">
                                <FileText className="w-8 h-8 text-blue-400" />
                            </div>
                        </div>
                    </div>

                    <div className="bg-gradient-to-br from-green-500/10 to-green-600/5 backdrop-blur-xl border border-green-400/20 rounded-2xl p-6 hover:border-green-400/50 transition-all duration-300">
                        <div className="text-sm text-gray-400 mb-2">Payées</div>
                        <div className="text-4xl font-black text-green-300">
                            {stats.paid}
                        </div>
                    </div>

                    <div className="bg-gradient-to-br from-yellow-500/10 to-yellow-600/5 backdrop-blur-xl border border-yellow-400/20 rounded-2xl p-6 hover:border-yellow-400/50 transition-all duration-300">
                        <div className="text-sm text-gray-400 mb-2">
                            En attente
                        </div>
                        <div className="text-4xl font-black text-yellow-300">
                            {stats.pending}
                        </div>
                    </div>

                    <div className="bg-gradient-to-br from-red-500/10 to-red-600/5 backdrop-blur-xl border border-red-400/20 rounded-2xl p-6 hover:border-red-400/50 transition-all duration-300">
                        <div className="text-sm text-gray-400 mb-2">
                            En retard
                        </div>
                        <div className="text-4xl font-black text-red-300">
                            {stats.overdue}
                        </div>
                    </div>

                    <div className="bg-gradient-to-br from-purple-500/10 to-purple-600/5 backdrop-blur-xl border border-purple-400/20 rounded-2xl p-6 hover:border-purple-400/50 transition-all duration-300">
                        <div className="text-sm text-gray-400 mb-2">
                            Montant total
                        </div>
                        <div className="text-2xl font-black text-purple-300">
                            {formatCurrency(stats.totalAmount).split(",")[0]}
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
                                <option value="paid">Payées</option>
                                <option value="pending">En attente</option>
                                <option value="overdue">En retard</option>
                                <option value="draft">Brouillons</option>
                            </select>
                        </div>

                        {/* Export button */}
                        <button className="flex items-center gap-2 px-4 py-3 bg-slate-700/30 border border-slate-600/50 rounded-xl text-gray-300 hover:bg-slate-700/50 hover:border-slate-500/50 transition-all duration-300 font-medium">
                            <Download className="w-5 h-5" />
                            <span>Exporter</span>
                        </button>
                    </div>

                    {/* Selected actions */}
                    {selectedInvoices.length > 0 && (
                        <div className="mt-4 pt-4 border-t border-slate-600/30 flex items-center justify-between">
                            <span className="text-sm text-gray-400">
                                {selectedInvoices.length} facture(s)
                                sélectionnée(s)
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

                {/* Invoices Table */}
                <div className="bg-gradient-to-br from-slate-800/50 to-slate-900/50 backdrop-blur-xl border border-slate-700/50 rounded-2xl overflow-hidden shadow-2xl shadow-blue-950/20">
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="bg-gradient-to-r from-slate-800/80 to-slate-900/80 border-b border-slate-700/50">
                                <tr>
                                    <th className="px-6 py-4 text-left">
                                        <input
                                            type="checkbox"
                                            checked={
                                                selectedInvoices.length ===
                                                    filteredInvoices.length &&
                                                filteredInvoices.length > 0
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
                                        Échéance
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
                                {filteredInvoices.map((invoice) => (
                                    <tr
                                        key={invoice.id}
                                        className="hover:bg-slate-700/20 transition-all duration-200 group"
                                    >
                                        <td className="px-6 py-4">
                                            <input
                                                type="checkbox"
                                                checked={selectedInvoices.includes(
                                                    invoice.id,
                                                )}
                                                onChange={(e) =>
                                                    handleSelectInvoice(
                                                        invoice.id,
                                                        e.target.checked,
                                                    )
                                                }
                                                className="w-4 h-4 rounded border-slate-600 bg-slate-700/50 text-blue-500 focus:ring-blue-500 focus:ring-offset-0 cursor-pointer"
                                            />
                                        </td>
                                        <td className="px-6 py-4">
                                            <div className="flex items-center gap-3">
                                                <div className="p-2 bg-blue-500/10 rounded-lg">
                                                    <FileText className="w-4 h-4 text-blue-400" />
                                                </div>
                                                <div>
                                                    <div className="text-white font-medium group-hover:text-blue-300 transition-colors">
                                                        {invoice.id}
                                                    </div>
                                                    <div className="text-xs text-gray-500">
                                                        {invoice.items}{" "}
                                                        article(s)
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-6 py-4">
                                            <div>
                                                <div className="text-white font-medium">
                                                    {invoice.client}
                                                </div>
                                                <div className="text-xs text-gray-500">
                                                    {invoice.clientEmail}
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-6 py-4">
                                            <div className="text-white font-semibold group-hover:text-green-300 transition-colors">
                                                {formatCurrency(invoice.amount)}
                                            </div>
                                        </td>
                                        <td className="px-6 py-4">
                                            <div className="flex items-center gap-2 text-gray-400 text-sm">
                                                <Calendar className="w-4 h-4" />
                                                <span>
                                                    {formatDate(invoice.date)}
                                                </span>
                                            </div>
                                        </td>
                                        <td className="px-6 py-4">
                                            <div className="text-gray-400 text-sm">
                                                {formatDate(invoice.dueDate)}
                                            </div>
                                        </td>
                                        <td className="px-6 py-4">
                                            <span
                                                className={`inline-flex px-3 py-1 rounded-full text-xs font-semibold border ${statusConfig[invoice.status as keyof typeof statusConfig].color}`}
                                            >
                                                {
                                                    statusConfig[
                                                        invoice.status as keyof typeof statusConfig
                                                    ].label
                                                }
                                            </span>
                                        </td>
                                        <td className="px-6 py-4">
                                            <div className="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                                <button
                                                    className="p-2 text-gray-400 hover:text-blue-400 hover:bg-blue-500/10 rounded-lg transition-all duration-200"
                                                    title="Voir"
                                                >
                                                    <Eye className="w-4 h-4" />
                                                </button>
                                                <button
                                                    onClick={() =>
                                                        handleOpenModal(invoice)
                                                    }
                                                    className="p-2 text-gray-400 hover:text-blue-400 hover:bg-blue-500/10 rounded-lg transition-all duration-200"
                                                    title="Modifier"
                                                >
                                                    <Edit className="w-4 h-4" />
                                                </button>
                                                <button
                                                    onClick={() =>
                                                        handleDelete(invoice.id)
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
                    {filteredInvoices.length === 0 && (
                        <div className="py-16 text-center">
                            <div className="flex justify-center mb-6">
                                <div className="p-4 bg-gradient-to-br from-blue-500/20 to-blue-600/10 rounded-2xl">
                                    <FileText className="w-12 h-12 text-gray-400" />
                                </div>
                            </div>
                            <p className="text-gray-300 mb-2 font-semibold text-lg">
                                Aucune facture trouvée
                            </p>
                            <p className="text-gray-500 text-sm mb-8">
                                {searchTerm || statusFilter !== "all"
                                    ? "Essayez de modifier vos filtres"
                                    : "Commencez par créer votre première facture"}
                            </p>
                            {!searchTerm && statusFilter === "all" && (
                                <button
                                    onClick={() => handleOpenModal()}
                                    className="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-600 via-teal-500 to-purple-600 text-white font-semibold rounded-xl hover:shadow-2xl hover:shadow-blue-500/50 transition-all duration-300 transform hover:scale-105"
                                >
                                    <Plus className="w-5 h-5" />
                                    <span>Créer une facture</span>
                                </button>
                            )}
                        </div>
                    )}
                </div>
            </div>

            {/* Modal Ajout/Modification */}
            {isModalOpen && (
                <div className="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4 z-50">
                    <div className="bg-slate-800 border border-slate-700/50 rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                        <div className="sticky top-0 bg-slate-800 border-b border-slate-700/50 p-6 flex items-center justify-between">
                            <h2 className="text-2xl font-bold text-white">
                                {editingId
                                    ? "Modifier facture"
                                    : "Nouvelle facture"}
                            </h2>
                            <button
                                onClick={handleCloseModal}
                                className="p-2 text-gray-400 hover:text-gray-300 hover:bg-slate-700/50 rounded-lg transition-all"
                            >
                                <Plus className="w-5 h-5 rotate-45" />
                            </button>
                        </div>

                        <div className="p-6 space-y-4">
                            {/* Client */}
                            <div>
                                <label className="block text-sm font-medium text-gray-300 mb-2">
                                    Client *
                                </label>
                                <select
                                    name="clientId"
                                    value={formData.clientId}
                                    onChange={handleInputChange}
                                    className="w-full px-4 py-3 bg-slate-700/30 border border-slate-600/50 rounded-xl text-white focus:outline-none focus:border-blue-500/50 transition-all appearance-none"
                                >
                                    <option value="">
                                        Sélectionner un client
                                    </option>
                                    {clients.map((client) => (
                                        <option
                                            key={client.id}
                                            value={client.id}
                                        >
                                            {client.name}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            {/* Invoice Number */}
                            <div>
                                <label className="block text-sm font-medium text-gray-300 mb-2">
                                    Numéro de facture
                                </label>
                                <input
                                    type="text"
                                    name="invoiceNumber"
                                    value={formData.invoiceNumber}
                                    onChange={handleInputChange}
                                    className="w-full px-4 py-3 bg-slate-700/30 border border-slate-600/50 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:border-blue-500/50 transition-all"
                                />
                            </div>

                            {/* Amount */}
                            <div>
                                <label className="block text-sm font-medium text-gray-300 mb-2">
                                    Montant (€) *
                                </label>
                                <input
                                    type="number"
                                    name="amount"
                                    value={formData.amount}
                                    onChange={handleInputChange}
                                    placeholder="0.00"
                                    step="0.01"
                                    min="0"
                                    className="w-full px-4 py-3 bg-slate-700/30 border border-slate-600/50 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:border-blue-500/50 transition-all"
                                />
                            </div>

                            {/* Dates */}
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-300 mb-2">
                                        Date de facturation
                                    </label>
                                    <input
                                        type="date"
                                        name="invoiceDate"
                                        value={formData.invoiceDate}
                                        onChange={handleInputChange}
                                        className="w-full px-4 py-3 bg-slate-700/30 border border-slate-600/50 rounded-xl text-white focus:outline-none focus:border-blue-500/50 transition-all"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-300 mb-2">
                                        Date d'échéance
                                    </label>
                                    <input
                                        type="date"
                                        name="dueDate"
                                        value={formData.dueDate}
                                        onChange={handleInputChange}
                                        className="w-full px-4 py-3 bg-slate-700/30 border border-slate-600/50 rounded-xl text-white focus:outline-none focus:border-blue-500/50 transition-all"
                                    />
                                </div>
                            </div>

                            {/* Status */}
                            <div>
                                <label className="block text-sm font-medium text-gray-300 mb-2">
                                    Statut
                                </label>
                                <select
                                    name="status"
                                    value={formData.status}
                                    onChange={handleInputChange}
                                    className="w-full px-4 py-3 bg-slate-700/30 border border-slate-600/50 rounded-xl text-white focus:outline-none focus:border-blue-500/50 transition-all appearance-none"
                                >
                                    <option value="draft">Brouillon</option>
                                    <option value="pending">En attente</option>
                                    <option value="paid">Payée</option>
                                    <option value="overdue">En retard</option>
                                </select>
                            </div>
                        </div>

                        {/* Modal Actions */}
                        <div className="sticky bottom-0 bg-slate-800 border-t border-slate-700/50 p-6 space-y-3">
                            <button
                                onClick={handleSave}
                                className="w-full flex items-center justify-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-600 via-teal-500 to-purple-600 text-white font-semibold rounded-xl hover:shadow-2xl hover:shadow-blue-500/50 transition-all duration-300"
                            >
                                <Plus className="w-5 h-5" />
                                {editingId ? "Modifier" : "Créer"}
                            </button>

                            <button
                                onClick={handleCloseModal}
                                className="w-full px-6 py-3 bg-slate-700/30 border border-slate-600/50 rounded-xl text-gray-300 hover:bg-slate-700/50 transition-all font-medium"
                            >
                                Annuler
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}

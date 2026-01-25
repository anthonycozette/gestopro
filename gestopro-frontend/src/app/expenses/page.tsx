"use client";

import { useState } from "react";
import Link from "next/link";
import {
    Plus,
    Search,
    Filter,
    Download,
    Edit,
    Trash2,
    TrendingDown,
    Calendar,
    Tag,
    ChevronLeft,
    BarChart3,
} from "lucide-react";

export default function ExpensesPage() {
    const [searchTerm, setSearchTerm] = useState("");
    const [categoryFilter, setCategoryFilter] = useState("all");
    const [selectedExpenses, setSelectedExpenses] = useState<string[]>([]);
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [editingId, setEditingId] = useState<string | null>(null);
    const [formData, setFormData] = useState({
        description: "",
        amount: "",
        category: "other",
        expenseDate: new Date().toISOString().split("T")[0],
    });

    // Mock expenses data
    const expensesList = [
        {
            id: "1",
            description: "Bureau fournitures",
            amount: 125.5,
            category: "office",
            expenseDate: "2026-01-20",
            status: "validee",
        },
        {
            id: "2",
            description: "Abonnement logiciel",
            amount: 49.99,
            category: "software",
            expenseDate: "2026-01-19",
            status: "validee",
        },
        {
            id: "3",
            description: "Carburant véhicule",
            amount: 65.0,
            category: "transport",
            expenseDate: "2026-01-18",
            status: "validee",
        },
        {
            id: "4",
            description: "Lunch meeting",
            amount: 35.5,
            category: "meals",
            expenseDate: "2026-01-17",
            status: "needs_review",
        },
        {
            id: "5",
            description: "Formation professionnelle",
            amount: 299.0,
            category: "training",
            expenseDate: "2026-01-15",
            status: "validee",
        },
        {
            id: "6",
            description: "Frais d'hébergement",
            amount: 89.99,
            category: "travel",
            expenseDate: "2026-01-14",
            status: "validee",
        },
        {
            id: "7",
            description: "Fournitures marketing",
            amount: 178.5,
            category: "marketing",
            expenseDate: "2026-01-13",
            status: "validee",
        },
        {
            id: "8",
            description: "Télécommunications",
            amount: 45.0,
            category: "telecom",
            expenseDate: "2026-01-12",
            status: "validee",
        },
    ];

    const categories = [
        {
            id: "office",
            label: "Bureau",
            color: "bg-blue-500/10 text-blue-400 border-blue-500/20",
        },
        {
            id: "software",
            label: "Logiciels",
            color: "bg-purple-500/10 text-purple-400 border-purple-500/20",
        },
        {
            id: "transport",
            label: "Transport",
            color: "bg-orange-500/10 text-orange-400 border-orange-500/20",
        },
        {
            id: "meals",
            label: "Repas",
            color: "bg-green-500/10 text-green-400 border-green-500/20",
        },
        {
            id: "training",
            label: "Formation",
            color: "bg-pink-500/10 text-pink-400 border-pink-500/20",
        },
        {
            id: "travel",
            label: "Déplacements",
            color: "bg-cyan-500/10 text-cyan-400 border-cyan-500/20",
        },
        {
            id: "marketing",
            label: "Marketing",
            color: "bg-red-500/10 text-red-400 border-red-500/20",
        },
        {
            id: "telecom",
            label: "Télécom",
            color: "bg-indigo-500/10 text-indigo-400 border-indigo-500/20",
        },
        {
            id: "other",
            label: "Autres",
            color: "bg-gray-500/10 text-gray-400 border-gray-500/20",
        },
    ];

    const filteredExpenses = expensesList.filter((expense) => {
        const matchesSearch = expense.description
            .toLowerCase()
            .includes(searchTerm.toLowerCase());
        const matchesCategory =
            categoryFilter === "all" || expense.category === categoryFilter;
        return matchesSearch && matchesCategory;
    });

    const stats = {
        total: expensesList.reduce((sum, e) => sum + e.amount, 0),
        byCategory: categories.map((cat) => ({
            ...cat,
            amount: expensesList
                .filter((e) => e.category === cat.id)
                .reduce((sum, e) => sum + e.amount, 0),
        })),
        validated: expensesList
            .filter((e) => e.status === "validee")
            .reduce((sum, e) => sum + e.amount, 0),
        needsReview: expensesList
            .filter((e) => e.status === "needs_review")
            .reduce((sum, e) => sum + e.amount, 0),
    };

    const getCategoryLabel = (categoryId: string) => {
        return categories.find((c) => c.id === categoryId)?.label || "Autres";
    };

    const getCategoryColor = (categoryId: string) => {
        return categories.find((c) => c.id === categoryId)?.color || "";
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

    const handleOpenModal = (expense?: any) => {
        if (expense) {
            setEditingId(expense.id);
            setFormData({
                description: expense.description,
                amount: expense.amount.toString(),
                category: expense.category,
                expenseDate: expense.expenseDate,
            });
        } else {
            setEditingId(null);
            setFormData({
                description: "",
                amount: "",
                category: "other",
                expenseDate: new Date().toISOString().split("T")[0],
            });
        }
        setIsModalOpen(true);
    };

    const handleCloseModal = () => {
        setIsModalOpen(false);
        setEditingId(null);
    };

    const handleSave = () => {
        if (!formData.description || !formData.amount) {
            alert("Description et montant requis");
            return;
        }
        console.log("Save expense:", editingId, formData);
        handleCloseModal();
    };

    const handleSelectAll = (checked: boolean) => {
        if (checked) {
            setSelectedExpenses(filteredExpenses.map((e) => e.id));
        } else {
            setSelectedExpenses([]);
        }
    };

    const handleSelectExpense = (id: string, checked: boolean) => {
        if (checked) {
            setSelectedExpenses((prev) => [...prev, id]);
        } else {
            setSelectedExpenses((prev) => prev.filter((eId) => eId !== id));
        }
    };

    const handleDelete = (id: string) => {
        console.log("Delete expense:", id);
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
                                Dépenses
                            </h1>
                            <p className="text-gray-400">
                                Suivez et classez vos dépenses professionnelles
                            </p>
                        </div>
                        <button
                            onClick={() => handleOpenModal()}
                            className="flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-600 via-teal-500 to-purple-600 text-white font-semibold rounded-xl hover:shadow-2xl hover:shadow-blue-500/50 transition-all duration-300 transform hover:scale-105"
                        >
                            <Plus className="w-5 h-5" />
                            <span>Ajouter une dépense</span>
                        </button>
                    </div>
                </div>

                {/* Stats Overview */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div className="bg-gradient-to-br from-red-500/10 to-red-600/5 backdrop-blur-xl border border-red-400/20 rounded-2xl p-6 hover:border-red-400/50 transition-all duration-300">
                        <div className="flex items-center justify-between">
                            <div>
                                <div className="text-sm text-gray-400 mb-2">
                                    Total dépenses
                                </div>
                                <div className="text-4xl font-black text-red-300">
                                    {formatCurrency(stats.total).split(",")[0]}
                                </div>
                            </div>
                            <div className="p-3 bg-red-500/20 rounded-xl">
                                <TrendingDown className="w-8 h-8 text-red-400" />
                            </div>
                        </div>
                    </div>

                    <div className="bg-gradient-to-br from-green-500/10 to-green-600/5 backdrop-blur-xl border border-green-400/20 rounded-2xl p-6 hover:border-green-400/50 transition-all duration-300">
                        <div className="flex items-center justify-between">
                            <div>
                                <div className="text-sm text-gray-400 mb-2">
                                    Validées
                                </div>
                                <div className="text-4xl font-black text-green-300">
                                    {
                                        formatCurrency(stats.validated).split(
                                            ",",
                                        )[0]
                                    }
                                </div>
                            </div>
                            <div className="p-3 bg-green-500/20 rounded-xl">
                                <BarChart3 className="w-8 h-8 text-green-400" />
                            </div>
                        </div>
                    </div>

                    <div className="bg-gradient-to-br from-yellow-500/10 to-yellow-600/5 backdrop-blur-xl border border-yellow-400/20 rounded-2xl p-6 hover:border-yellow-400/50 transition-all duration-300">
                        <div className="flex items-center justify-between">
                            <div>
                                <div className="text-sm text-gray-400 mb-2">
                                    À examiner
                                </div>
                                <div className="text-4xl font-black text-yellow-300">
                                    {
                                        formatCurrency(stats.needsReview).split(
                                            ",",
                                        )[0]
                                    }
                                </div>
                            </div>
                            <div className="p-3 bg-yellow-500/20 rounded-xl">
                                <Tag className="w-8 h-8 text-yellow-400" />
                            </div>
                        </div>
                    </div>
                </div>

                {/* Categories Breakdown */}
                <div className="bg-gradient-to-br from-slate-800/50 to-slate-900/50 backdrop-blur-xl border border-slate-700/50 rounded-2xl p-6 mb-6">
                    <h2 className="text-xl font-bold text-white mb-6">
                        Par catégorie
                    </h2>
                    <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                        {stats.byCategory
                            .filter((cat) => cat.amount > 0)
                            .sort((a, b) => b.amount - a.amount)
                            .map((cat) => (
                                <div
                                    key={cat.id}
                                    className={`p-4 rounded-xl border ${cat.color}`}
                                >
                                    <div className="text-sm font-medium mb-2">
                                        {cat.label}
                                    </div>
                                    <div className="text-2xl font-black text-white">
                                        {
                                            formatCurrency(cat.amount).split(
                                                " ",
                                            )[0]
                                        }
                                    </div>
                                </div>
                            ))}
                    </div>
                </div>

                {/* Search & Filters */}
                <div className="bg-gradient-to-br from-slate-800/50 to-slate-900/50 backdrop-blur-xl border border-slate-700/50 rounded-2xl p-6 mb-6">
                    <div className="flex flex-col md:flex-row gap-4">
                        {/* Search */}
                        <div className="flex-1 relative">
                            <Search className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-500" />
                            <input
                                type="text"
                                placeholder="Rechercher une dépense..."
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                                className="w-full pl-12 pr-4 py-3 bg-slate-700/30 border border-slate-600/50 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:border-blue-500/50 focus:bg-slate-700/50 transition-all duration-300"
                            />
                        </div>

                        {/* Category filter */}
                        <div className="relative">
                            <Filter className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-500 pointer-events-none" />
                            <select
                                value={categoryFilter}
                                onChange={(e) =>
                                    setCategoryFilter(e.target.value)
                                }
                                className="pl-12 pr-8 py-3 bg-slate-700/30 border border-slate-600/50 rounded-xl text-white focus:outline-none focus:border-blue-500/50 focus:bg-slate-700/50 transition-all duration-300 appearance-none cursor-pointer"
                            >
                                <option value="all">Toutes catégories</option>
                                {categories.map((cat) => (
                                    <option key={cat.id} value={cat.id}>
                                        {cat.label}
                                    </option>
                                ))}
                            </select>
                        </div>

                        {/* Export button */}
                        <button className="flex items-center gap-2 px-4 py-3 bg-slate-700/30 border border-slate-600/50 rounded-xl text-gray-300 hover:bg-slate-700/50 hover:border-slate-500/50 transition-all duration-300 font-medium">
                            <Download className="w-5 h-5" />
                            <span>Exporter</span>
                        </button>
                    </div>

                    {/* Selected actions */}
                    {selectedExpenses.length > 0 && (
                        <div className="mt-4 pt-4 border-t border-slate-600/30 flex items-center justify-between">
                            <span className="text-sm text-gray-400">
                                {selectedExpenses.length} dépense(s)
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

                {/* Expenses Table */}
                <div className="bg-gradient-to-br from-slate-800/50 to-slate-900/50 backdrop-blur-xl border border-slate-700/50 rounded-2xl overflow-hidden shadow-2xl shadow-blue-950/20">
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="bg-gradient-to-r from-slate-800/80 to-slate-900/80 border-b border-slate-700/50">
                                <tr>
                                    <th className="px-6 py-4 text-left">
                                        <input
                                            type="checkbox"
                                            checked={
                                                selectedExpenses.length ===
                                                    filteredExpenses.length &&
                                                filteredExpenses.length > 0
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
                                        Description
                                    </th>
                                    <th className="px-6 py-4 text-left text-sm font-semibold text-gray-300">
                                        Catégorie
                                    </th>
                                    <th className="px-6 py-4 text-left text-sm font-semibold text-gray-300">
                                        Montant
                                    </th>
                                    <th className="px-6 py-4 text-left text-sm font-semibold text-gray-300">
                                        Date
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
                                {filteredExpenses.map((expense) => (
                                    <tr
                                        key={expense.id}
                                        className="hover:bg-slate-700/20 transition-all duration-200 group"
                                    >
                                        <td className="px-6 py-4">
                                            <input
                                                type="checkbox"
                                                checked={selectedExpenses.includes(
                                                    expense.id,
                                                )}
                                                onChange={(e) =>
                                                    handleSelectExpense(
                                                        expense.id,
                                                        e.target.checked,
                                                    )
                                                }
                                                className="w-4 h-4 rounded border-slate-600 bg-slate-700/50 text-blue-500 focus:ring-blue-500 focus:ring-offset-0 cursor-pointer"
                                            />
                                        </td>
                                        <td className="px-6 py-4">
                                            <div className="text-white font-medium group-hover:text-blue-300 transition-colors">
                                                {expense.description}
                                            </div>
                                        </td>
                                        <td className="px-6 py-4">
                                            <span
                                                className={`inline-flex px-3 py-1 rounded-full text-xs font-semibold border ${getCategoryColor(
                                                    expense.category,
                                                )}`}
                                            >
                                                {getCategoryLabel(
                                                    expense.category,
                                                )}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4">
                                            <div className="text-white font-semibold group-hover:text-red-300 transition-colors">
                                                {formatCurrency(expense.amount)}
                                            </div>
                                        </td>
                                        <td className="px-6 py-4">
                                            <div className="flex items-center gap-2 text-gray-400 text-sm">
                                                <Calendar className="w-4 h-4" />
                                                <span>
                                                    {formatDate(
                                                        expense.expenseDate,
                                                    )}
                                                </span>
                                            </div>
                                        </td>
                                        <td className="px-6 py-4">
                                            <span
                                                className={`inline-flex px-3 py-1 rounded-full text-xs font-semibold border ${
                                                    expense.status === "validee"
                                                        ? "bg-green-500/10 text-green-400 border-green-500/20"
                                                        : "bg-yellow-500/10 text-yellow-400 border-yellow-500/20"
                                                }`}
                                            >
                                                {expense.status === "validee"
                                                    ? "Validée"
                                                    : "À examiner"}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4">
                                            <div className="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                                <button
                                                    onClick={() =>
                                                        handleOpenModal(expense)
                                                    }
                                                    className="p-2 text-gray-400 hover:text-blue-400 hover:bg-blue-500/10 rounded-lg transition-all duration-200"
                                                    title="Modifier"
                                                >
                                                    <Edit className="w-4 h-4" />
                                                </button>
                                                <button
                                                    onClick={() =>
                                                        handleDelete(expense.id)
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
                    {filteredExpenses.length === 0 && (
                        <div className="py-16 text-center">
                            <div className="flex justify-center mb-6">
                                <div className="p-4 bg-gradient-to-br from-red-500/20 to-red-600/10 rounded-2xl">
                                    <TrendingDown className="w-12 h-12 text-gray-400" />
                                </div>
                            </div>
                            <p className="text-gray-300 mb-2 font-semibold text-lg">
                                Aucune dépense trouvée
                            </p>
                            <p className="text-gray-500 text-sm mb-8">
                                {searchTerm || categoryFilter !== "all"
                                    ? "Essayez de modifier vos filtres"
                                    : "Commencez par ajouter votre première dépense"}
                            </p>
                            {!searchTerm && categoryFilter === "all" && (
                                <button
                                    onClick={() => handleOpenModal()}
                                    className="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-600 via-teal-500 to-purple-600 text-white font-semibold rounded-xl hover:shadow-2xl hover:shadow-blue-500/50 transition-all duration-300 transform hover:scale-105"
                                >
                                    <Plus className="w-5 h-5" />
                                    <span>Ajouter une dépense</span>
                                </button>
                            )}
                        </div>
                    )}
                </div>
            </div>

            {/* Modal Ajout/Modification */}
            {isModalOpen && (
                <div className="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4 z-50">
                    <div className="bg-slate-800 border border-slate-700/50 rounded-2xl max-w-md w-full max-h-[90vh] overflow-y-auto">
                        <div className="sticky top-0 bg-slate-800 border-b border-slate-700/50 p-6 flex items-center justify-between">
                            <h2 className="text-2xl font-bold text-white">
                                {editingId
                                    ? "Modifier dépense"
                                    : "Nouvelle dépense"}
                            </h2>
                            <button
                                onClick={handleCloseModal}
                                className="p-2 text-gray-400 hover:text-gray-300 hover:bg-slate-700/50 rounded-lg transition-all"
                            >
                                <Plus className="w-5 h-5 rotate-45" />
                            </button>
                        </div>

                        <div className="p-6 space-y-4">
                            {/* Description */}
                            <div>
                                <label className="block text-sm font-medium text-gray-300 mb-2">
                                    Description *
                                </label>
                                <input
                                    type="text"
                                    name="description"
                                    value={formData.description}
                                    onChange={handleInputChange}
                                    placeholder="Ex: Bureau fournitures..."
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

                            {/* Category */}
                            <div>
                                <label className="block text-sm font-medium text-gray-300 mb-2">
                                    Catégorie
                                </label>
                                <select
                                    name="category"
                                    value={formData.category}
                                    onChange={handleInputChange}
                                    className="w-full px-4 py-3 bg-slate-700/30 border border-slate-600/50 rounded-xl text-white focus:outline-none focus:border-blue-500/50 transition-all appearance-none"
                                >
                                    {categories.map((cat) => (
                                        <option key={cat.id} value={cat.id}>
                                            {cat.label}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            {/* Date */}
                            <div>
                                <label className="block text-sm font-medium text-gray-300 mb-2">
                                    Date
                                </label>
                                <input
                                    type="date"
                                    name="expenseDate"
                                    value={formData.expenseDate}
                                    onChange={handleInputChange}
                                    className="w-full px-4 py-3 bg-slate-700/30 border border-slate-600/50 rounded-xl text-white focus:outline-none focus:border-blue-500/50 transition-all"
                                />
                            </div>
                        </div>

                        {/* Modal Actions */}
                        <div className="sticky bottom-0 bg-slate-800 border-t border-slate-700/50 p-6 space-y-3">
                            <button
                                onClick={handleSave}
                                className="w-full flex items-center justify-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-600 via-teal-500 to-purple-600 text-white font-semibold rounded-xl hover:shadow-2xl hover:shadow-blue-500/50 transition-all duration-300"
                            >
                                <Plus className="w-5 h-5" />
                                {editingId ? "Modifier" : "Ajouter"}
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

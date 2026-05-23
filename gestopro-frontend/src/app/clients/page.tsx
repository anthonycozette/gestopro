"use client";

import { useState } from "react";
import Link from "next/link";
import {
    Plus,
    Search,
    Edit,
    Trash2,
    Mail,
    Phone,
    MapPin,
    Building2,
    ChevronLeft,
    X,
    Save,
} from "lucide-react";

export default function ClientsPage() {
    const [searchTerm, setSearchTerm] = useState("");
    const [clients, setClients] = useState([
        {
            id: "1",
            name: "SARL Dupont",
            email: "contact@dupont.fr",
            phone: "01 23 45 67 89",
            address: "123 Rue de Paris, 75000 Paris",
            siret: "12345678901235",
        },
        {
            id: "2",
            name: "SAS Martin",
            email: "info@martin.fr",
            phone: "02 34 56 78 90",
            address: "456 Avenue Lyon, 69000 Lyon",
            siret: "98765432109876",
        },
        {
            id: "3",
            name: "EI Dubois",
            email: "dubois@mail.fr",
            phone: "03 45 67 89 01",
            address: "789 Boulevard Marseille, 13000 Marseille",
            siret: "11223344556677",
        },
    ]);
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [editingId, setEditingId] = useState<string | null>(null);
    const [formData, setFormData] = useState({
        name: "",
        email: "",
        phone: "",
        address: "",
        siret: "",
    });

    const filteredClients = clients.filter(
        (client) =>
            client.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
            client.email.toLowerCase().includes(searchTerm.toLowerCase()) ||
            client.siret.includes(searchTerm),
    );

    const handleOpenModal = (client?: any) => {
        if (client) {
            setEditingId(client.id);
            setFormData(client);
        } else {
            setEditingId(null);
            setFormData({
                name: "",
                email: "",
                phone: "",
                address: "",
                siret: "",
            });
        }
        setIsModalOpen(true);
    };

    const handleCloseModal = () => {
        setIsModalOpen(false);
        setEditingId(null);
        setFormData({
            name: "",
            email: "",
            phone: "",
            address: "",
            siret: "",
        });
    };

    const handleSave = () => {
        if (!formData.name || !formData.email) {
            alert("Nom et email requis");
            return;
        }

        if (editingId) {
            setClients(
                clients.map((c) =>
                    c.id === editingId ? { ...formData, id: editingId } : c,
                ),
            );
        } else {
            const newId = (
                Math.max(...clients.map((c) => parseInt(c.id)), 0) + 1
            ).toString();
            setClients([...clients, { ...formData, id: newId }]);
        }

        handleCloseModal();
    };

    const handleDelete = (id: string) => {
        if (confirm("Êtes-vous sûr de vouloir supprimer ce client ?")) {
            setClients(clients.filter((c) => c.id !== id));
        }
    };

    const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
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
                                Clients
                            </h1>
                            <p className="text-gray-400">
                                Gérez tous vos clients en un seul endroit
                            </p>
                        </div>
                        <button
                            onClick={() => handleOpenModal()}
                            className="flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-600 via-teal-500 to-purple-600 text-white font-semibold rounded-xl hover:shadow-2xl hover:shadow-blue-500/50 transition-all duration-300 transform hover:scale-105"
                        >
                            <Plus className="w-5 h-5" />
                            <span>Nouveau client</span>
                        </button>
                    </div>
                </div>

                {/* Stats Cards */}
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div className="bg-gradient-to-br from-blue-500/10 to-blue-600/5 backdrop-blur-xl border border-blue-400/20 rounded-2xl p-5 hover:border-blue-400/50 transition-all duration-300 group">
                        <div className="text-sm text-gray-400 mb-2">Total</div>
                        <div className="text-3xl font-black text-blue-300 group-hover:text-blue-200 transition-colors">
                            {clients.length}
                        </div>
                        <div className="text-xs text-gray-500 mt-2">
                            clients
                        </div>
                    </div>

                    <div className="bg-gradient-to-br from-teal-500/10 to-teal-600/5 backdrop-blur-xl border border-teal-400/20 rounded-2xl p-5 hover:border-teal-400/50 transition-all duration-300 group">
                        <div className="text-sm text-gray-400 mb-2">Actifs</div>
                        <div className="text-3xl font-black text-teal-300 group-hover:text-teal-200 transition-colors">
                            {clients.length}
                        </div>
                        <div className="text-xs text-gray-500 mt-2">
                            cette année
                        </div>
                    </div>

                    <div className="bg-gradient-to-br from-purple-500/10 to-purple-600/5 backdrop-blur-xl border border-purple-400/20 rounded-2xl p-5 hover:border-purple-400/50 transition-all duration-300 group">
                        <div className="text-sm text-gray-400 mb-2">
                            Sociétés
                        </div>
                        <div className="text-3xl font-black text-purple-300 group-hover:text-purple-200 transition-colors">
                            {
                                clients.filter(
                                    (c) =>
                                        c.name.includes("SARL") ||
                                        c.name.includes("SAS"),
                                ).length
                            }
                        </div>
                        <div className="text-xs text-gray-500 mt-2">
                            enregistrées
                        </div>
                    </div>

                    <div className="bg-gradient-to-br from-orange-500/10 to-orange-600/5 backdrop-blur-xl border border-orange-400/20 rounded-2xl p-5 hover:border-orange-400/50 transition-all duration-300 group">
                        <div className="text-sm text-gray-400 mb-2">EI</div>
                        <div className="text-3xl font-black text-orange-300 group-hover:text-orange-200 transition-colors">
                            {
                                clients.filter((c) => c.name.includes("EI"))
                                    .length
                            }
                        </div>
                        <div className="text-xs text-gray-500 mt-2">
                            entreprises
                        </div>
                    </div>
                </div>

                {/* Search */}
                <div className="bg-gradient-to-br from-slate-800/50 to-slate-900/50 backdrop-blur-xl border border-slate-700/50 rounded-2xl p-6 mb-6">
                    <div className="relative">
                        <Search className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-500" />
                        <input
                            type="text"
                            placeholder="Rechercher par nom, email ou SIRET..."
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                            className="w-full pl-12 pr-4 py-3 bg-slate-700/30 border border-slate-600/50 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:border-blue-500/50 focus:bg-slate-700/50 transition-all duration-300"
                        />
                    </div>
                </div>

                {/* Clients Grid */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {filteredClients.length > 0 ? (
                        filteredClients.map((client) => (
                            <div
                                key={client.id}
                                className="bg-gradient-to-br from-slate-800/50 to-slate-900/50 backdrop-blur-xl border border-slate-700/50 rounded-2xl p-6 hover:border-slate-600/50 transition-all duration-300 group"
                            >
                                {/* Header avec actions */}
                                <div className="flex items-start justify-between mb-4">
                                    <div className="p-3 bg-gradient-to-br from-blue-500/20 to-blue-600/10 rounded-xl group-hover:from-blue-500/30 group-hover:to-blue-600/20 transition-colors">
                                        <Building2 className="w-6 h-6 text-blue-400" />
                                    </div>
                                    <div className="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <button
                                            onClick={() =>
                                                handleOpenModal(client)
                                            }
                                            className="p-2 text-gray-400 hover:text-blue-400 hover:bg-blue-500/10 rounded-lg transition-all duration-200"
                                            title="Modifier"
                                        >
                                            <Edit className="w-4 h-4" />
                                        </button>
                                        <button
                                            onClick={() =>
                                                handleDelete(client.id)
                                            }
                                            className="p-2 text-gray-400 hover:text-red-400 hover:bg-red-500/10 rounded-lg transition-all duration-200"
                                            title="Supprimer"
                                        >
                                            <Trash2 className="w-4 h-4" />
                                        </button>
                                    </div>
                                </div>

                                {/* Infos client */}
                                <h3 className="text-lg font-bold text-white mb-1">
                                    {client.name}
                                </h3>

                                <div className="space-y-2 mb-4">
                                    {/* Email */}
                                    <div className="flex items-center gap-2 text-sm text-gray-400 group-hover:text-gray-300 transition-colors">
                                        <Mail className="w-4 h-4 text-teal-400" />
                                        <span>{client.email}</span>
                                    </div>

                                    {/* Phone */}
                                    {client.phone && (
                                        <div className="flex items-center gap-2 text-sm text-gray-400 group-hover:text-gray-300 transition-colors">
                                            <Phone className="w-4 h-4 text-blue-400" />
                                            <span>{client.phone}</span>
                                        </div>
                                    )}

                                    {/* Address */}
                                    {client.address && (
                                        <div className="flex items-start gap-2 text-sm text-gray-400 group-hover:text-gray-300 transition-colors">
                                            <MapPin className="w-4 h-4 text-purple-400 mt-0.5 flex-shrink-0" />
                                            <span>{client.address}</span>
                                        </div>
                                    )}

                                    {/* SIRET */}
                                    {client.siret && (
                                        <div className="pt-2 border-t border-slate-600/30">
                                            <div className="text-xs text-gray-500">
                                                SIRET
                                            </div>
                                            <div className="text-sm font-mono text-gray-300">
                                                {client.siret}
                                            </div>
                                        </div>
                                    )}
                                </div>

                                {/* Actions rapides */}
                                <div className="pt-4 border-t border-slate-600/30 space-y-2">
                                    <Link
                                        href={`/invoices?client=${client.id}`}
                                        className="block w-full text-center px-3 py-2 bg-slate-700/30 border border-slate-600/50 rounded-lg text-sm text-gray-300 hover:bg-slate-700/50 hover:border-slate-500/50 transition-all duration-200 font-medium"
                                    >
                                        Voir factures
                                    </Link>
                                </div>
                            </div>
                        ))
                    ) : (
                        <div className="col-span-full text-center py-16">
                            <Building2 className="w-16 h-16 text-gray-600 mx-auto mb-4" />
                            <p className="text-gray-400 mb-2 text-lg">
                                Aucun client trouvé
                            </p>
                            <p className="text-gray-500 text-sm mb-6">
                                {searchTerm
                                    ? "Essayez une autre recherche"
                                    : "Commencez par ajouter votre premier client"}
                            </p>
                            {!searchTerm && (
                                <button
                                    onClick={() => handleOpenModal()}
                                    className="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-600 via-teal-500 to-purple-600 text-white font-semibold rounded-xl hover:shadow-2xl hover:shadow-blue-500/50 transition-all duration-300 transform hover:scale-105"
                                >
                                    <Plus className="w-5 h-5" />
                                    <span>Ajouter un client</span>
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
                                    ? "Modifier client"
                                    : "Nouveau client"}
                            </h2>
                            <button
                                onClick={handleCloseModal}
                                className="p-2 text-gray-400 hover:text-gray-300 hover:bg-slate-700/50 rounded-lg transition-all"
                            >
                                <X className="w-5 h-5" />
                            </button>
                        </div>

                        <div className="p-6 space-y-4">
                            {/* Nom */}
                            <div>
                                <label className="block text-sm font-medium text-gray-300 mb-2">
                                    Nom de l'entreprise *
                                </label>
                                <input
                                    type="text"
                                    name="name"
                                    value={formData.name}
                                    onChange={handleInputChange}
                                    placeholder="SARL Dupont..."
                                    className="w-full px-4 py-3 bg-slate-700/30 border border-slate-600/50 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:border-blue-500/50 transition-all"
                                />
                            </div>

                            {/* Email */}
                            <div>
                                <label className="block text-sm font-medium text-gray-300 mb-2">
                                    Email *
                                </label>
                                <input
                                    type="email"
                                    name="email"
                                    value={formData.email}
                                    onChange={handleInputChange}
                                    placeholder="contact@example.fr"
                                    className="w-full px-4 py-3 bg-slate-700/30 border border-slate-600/50 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:border-blue-500/50 transition-all"
                                />
                            </div>

                            {/* Téléphone */}
                            <div>
                                <label className="block text-sm font-medium text-gray-300 mb-2">
                                    Téléphone
                                </label>
                                <input
                                    type="tel"
                                    name="phone"
                                    value={formData.phone}
                                    onChange={handleInputChange}
                                    placeholder="01 23 45 67 89"
                                    className="w-full px-4 py-3 bg-slate-700/30 border border-slate-600/50 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:border-blue-500/50 transition-all"
                                />
                            </div>

                            {/* Adresse */}
                            <div>
                                <label className="block text-sm font-medium text-gray-300 mb-2">
                                    Adresse
                                </label>
                                <input
                                    type="text"
                                    name="address"
                                    value={formData.address}
                                    onChange={handleInputChange}
                                    placeholder="123 Rue de Paris, 75000 Paris"
                                    className="w-full px-4 py-3 bg-slate-700/30 border border-slate-600/50 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:border-blue-500/50 transition-all"
                                />
                            </div>

                            {/* SIRET */}
                            <div>
                                <label className="block text-sm font-medium text-gray-300 mb-2">
                                    SIRET/SIREN
                                </label>
                                <input
                                    type="text"
                                    name="siret"
                                    value={formData.siret}
                                    onChange={handleInputChange}
                                    placeholder="12345678901234"
                                    className="w-full px-4 py-3 bg-slate-700/30 border border-slate-600/50 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:border-blue-500/50 transition-all"
                                />
                            </div>
                        </div>

                        {/* Actions Modal */}
                        <div className="sticky bottom-0 bg-slate-800 border-t border-slate-700/50 p-6 space-y-3">
                            <button
                                onClick={handleSave}
                                className="w-full flex items-center justify-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-600 via-teal-500 to-purple-600 text-white font-semibold rounded-xl hover:shadow-2xl hover:shadow-blue-500/50 transition-all duration-300"
                            >
                                <Save className="w-5 h-5" />
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

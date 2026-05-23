"use client";

import { useState } from "react";
import Link from "next/link";
import { usePathname } from "next/navigation";
import {
    LayoutDashboard,
    FileText,
    Users,
    CreditCard,
    Calculator,
    Brain,
    ScanLine,
    Settings,
    LogOut,
    Menu,
    X,
    ChevronDown,
} from "lucide-react";

export default function DashboardLayout({
    children,
}: {
    children: React.ReactNode;
}) {
    const pathname = usePathname();
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const [accountingOpen, setAccountingOpen] = useState(true);

    const navigation = [
        { name: "Tableau de bord", href: "/dashboard", icon: LayoutDashboard },
        { name: "devis", href: "/devis", icon: FileText },
        { name: "Factures", href: "/invoices", icon: FileText },
        { name: "Clients", href: "/clients", icon: Users },
        { name: "Dépenses", href: "/expenses", icon: CreditCard },
    ];

    const accountingNav = [
        { name: "Vue d'ensemble", href: "/accounting", icon: Calculator },
        { name: "URSSAF", href: "/accounting/urssaf", icon: Calculator },
        { name: "Bilan IA", href: "/accounting/bilan-ia", icon: Brain },
        { name: "Analyses", href: "/accounting/analyses", icon: ScanLine },
    ];

    const isActive = (href: string) => {
        return pathname === href || pathname?.startsWith(href + "/");
    };

    const handleLogout = () => {
        // TODO: Implémenter la déconnexion
        if (typeof window !== "undefined") {
            localStorage.removeItem("token");
            window.location.href = "/login";
        }
    };

    return (
        <div className="min-h-screen bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950">
            {/* Mobile sidebar backdrop */}
            {sidebarOpen && (
                <div
                    className="fixed inset-0 bg-black/50 backdrop-blur-sm z-40 lg:hidden"
                    onClick={() => setSidebarOpen(false)}
                />
            )}

            {/* Sidebar */}
            <aside
                className={`fixed top-0 left-0 z-50 h-screen w-72 bg-slate-900/95 backdrop-blur-lg border-r border-white/10 transition-transform duration-300 lg:translate-x-0 ${
                    sidebarOpen ? "translate-x-0" : "-translate-x-full"
                }`}
            >
                <div className="flex flex-col h-full">
                    {/* Logo */}
                    <div className="flex items-center justify-between p-6 border-b border-white/10">
                        <Link
                            href="/dashboard"
                            className="flex items-center space-x-2"
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
                        <button
                            onClick={() => setSidebarOpen(false)}
                            className="lg:hidden text-gray-400 hover:text-white transition-smooth"
                        >
                            <X className="w-6 h-6" />
                        </button>
                    </div>

                    {/* Navigation */}
                    <nav className="flex-1 px-4 py-6 overflow-y-auto">
                        <div className="space-y-1">
                            {navigation.map((item) => {
                                const Icon = item.icon;
                                const active = isActive(item.href);
                                return (
                                    <Link
                                        key={item.name}
                                        href={item.href}
                                        className={`flex items-center gap-3 px-4 py-3 rounded-lg transition-smooth ${
                                            active
                                                ? "bg-gradient-brand text-white"
                                                : "text-gray-400 hover:bg-white/5 hover:text-white"
                                        }`}
                                        onClick={() => setSidebarOpen(false)}
                                    >
                                        <Icon className="w-5 h-5 flex-shrink-0" />
                                        <span className="font-medium">
                                            {item.name}
                                        </span>
                                    </Link>
                                );
                            })}
                        </div>

                        {/* Accounting submenu */}
                        <div className="mt-6">
                            <button
                                onClick={() =>
                                    setAccountingOpen(!accountingOpen)
                                }
                                className="flex items-center justify-between w-full px-4 py-3 text-gray-400 hover:text-white transition-smooth"
                            >
                                <div className="flex items-center gap-3">
                                    <Calculator className="w-5 h-5" />
                                    <span className="font-medium">
                                        Comptabilité
                                    </span>
                                </div>
                                <ChevronDown
                                    className={`w-4 h-4 transition-transform ${
                                        accountingOpen ? "rotate-180" : ""
                                    }`}
                                />
                            </button>

                            {accountingOpen && (
                                <div className="mt-1 ml-4 space-y-1">
                                    {accountingNav.map((item) => {
                                        const Icon = item.icon;
                                        const active = isActive(item.href);
                                        return (
                                            <Link
                                                key={item.name}
                                                href={item.href}
                                                className={`flex items-center gap-3 px-4 py-2 rounded-lg text-sm transition-smooth ${
                                                    active
                                                        ? "bg-white/10 text-brand-turquoise"
                                                        : "text-gray-500 hover:bg-white/5 hover:text-gray-300"
                                                }`}
                                                onClick={() =>
                                                    setSidebarOpen(false)
                                                }
                                            >
                                                <Icon className="w-4 h-4 flex-shrink-0" />
                                                <span>{item.name}</span>
                                            </Link>
                                        );
                                    })}
                                </div>
                            )}
                        </div>

                        {/* Settings */}
                        <div className="mt-6 pt-6 border-t border-white/10">
                            <Link
                                href="/settings"
                                className={`flex items-center gap-3 px-4 py-3 rounded-lg transition-smooth ${
                                    isActive("/settings")
                                        ? "bg-gradient-brand text-white"
                                        : "text-gray-400 hover:bg-white/5 hover:text-white"
                                }`}
                                onClick={() => setSidebarOpen(false)}
                            >
                                <Settings className="w-5 h-5 flex-shrink-0" />
                                <span className="font-medium">Paramètres</span>
                            </Link>
                        </div>
                    </nav>

                    {/* User info & Logout */}
                    <div className="p-4 border-t border-white/10">
                        <div className="flex items-center gap-3 px-4 py-3 bg-white/5 rounded-lg mb-2">
                            <div className="w-10 h-10 bg-gradient-brand rounded-full flex items-center justify-center">
                                <span className="text-white font-semibold text-sm">
                                    JD
                                </span>
                            </div>
                            <div className="flex-1 min-w-0">
                                <div className="text-white font-medium text-sm truncate">
                                    Jean Dupont
                                </div>
                                <div className="text-gray-400 text-xs truncate">
                                    jean@exemple.fr
                                </div>
                            </div>
                        </div>
                        <button
                            onClick={handleLogout}
                            className="flex items-center gap-3 w-full px-4 py-3 text-gray-400 hover:bg-red-500/10 hover:text-red-400 rounded-lg transition-smooth"
                        >
                            <LogOut className="w-5 h-5" />
                            <span className="font-medium">Déconnexion</span>
                        </button>
                    </div>
                </div>
            </aside>

            {/* Main content */}
            <div className="lg:pl-72">
                {/* Mobile header */}
                <header className="sticky top-0 z-30 lg:hidden bg-slate-900/95 backdrop-blur-lg border-b border-white/10">
                    <div className="flex items-center justify-between px-6 py-4">
                        <button
                            onClick={() => setSidebarOpen(true)}
                            className="text-gray-400 hover:text-white transition-smooth"
                        >
                            <Menu className="w-6 h-6" />
                        </button>
                        <Link
                            href="/dashboard"
                            className="flex items-center space-x-2"
                        >
                            <div className="w-8 h-8 bg-gradient-brand rounded-lg flex items-center justify-center">
                                <span className="text-white font-bold text-lg">
                                    G
                                </span>
                            </div>
                            <span className="text-lg font-bold text-gradient">
                                GestoPro
                            </span>
                        </Link>
                        <div className="w-6" /> {/* Spacer for alignment */}
                    </div>
                </header>

                {/* Page content */}
                <main>{children}</main>
            </div>
        </div>
    );
}

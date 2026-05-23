"use client";

import { useState } from "react";
import { apiClient } from "@/lib/api";

export default function TestApiPage() {
    const [result, setResult] = useState<any>(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState("");

    const testApi = async () => {
        setLoading(true);
        setError("");
        try {
            const data = await apiClient.get("/test");
            setResult(data);
        } catch (err: any) {
            setError(err.message);
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="min-h-screen bg-slate-950 p-8">
            <div className="max-w-2xl mx-auto">
                <h1 className="text-3xl font-bold text-white mb-8">
                    Test Connexion API
                </h1>

                <button
                    onClick={testApi}
                    disabled={loading}
                    className="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
                >
                    {loading ? "Chargement..." : "Tester l'API"}
                </button>

                {error && (
                    <div className="mt-4 p-4 bg-red-500/10 border border-red-500/20 rounded-lg">
                        <p className="text-red-400">Erreur : {error}</p>
                        <p className="text-gray-400 text-sm mt-2">
                            Vérifie que Symfony tourne sur http://localhost:8000
                        </p>
                    </div>
                )}

                {result && (
                    <div className="mt-4 p-4 bg-green-500/10 border border-green-500/20 rounded-lg">
                        <p className="text-green-400 font-semibold mb-2">
                            ✅ Connexion réussie !
                        </p>
                        <pre className="text-gray-300 text-sm">
                            {JSON.stringify(result, null, 2)}
                        </pre>
                    </div>
                )}
            </div>
        </div>
    );
}

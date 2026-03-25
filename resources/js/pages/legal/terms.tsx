import { Head, Link } from '@inertiajs/react';
import React from 'react';
import { ArrowLeft, ShieldCheck } from 'lucide-react';

export default function Terms() {
    return (
        <div className="min-h-screen bg-black text-zinc-300 selection:bg-[#16a34a] selection:text-white font-sans py-20 px-6">
            <Head title="Terms of Service - AIRA LOGIX" />

            <div className="max-w-3xl mx-auto space-y-12">
                {/* Header */}
                <div className="flex flex-col space-y-4">
                    <Link
                        href={route('login')}
                        className="flex items-center gap-2 text-zinc-500 hover:text-[#16a34a] transition-colors w-fit group"
                    >
                        <ArrowLeft className="w-4 h-4 group-hover:-translate-x-1 transition-transform" />
                        <span className="text-xs font-bold uppercase tracking-widest">Back to Login</span>
                    </Link>

                    <div className="flex items-center gap-4">
                        <div className="w-12 h-12 rounded-2xl bg-[#16a34a]/10 border border-[#16a34a]/20 flex items-center justify-center">
                            <ShieldCheck className="w-6 h-6 text-[#16a34a]" />
                        </div>
                        <div>
                            <h1 className="text-3xl font-black text-white tracking-tighter uppercase">Terms of Service</h1>
                            <p className="text-xs text-zinc-500 font-bold uppercase tracking-widest">Last Updated: March 2026</p>
                        </div>
                    </div>
                </div>

                {/* Content */}
                <div className="space-y-10 text-sm leading-relaxed border-t border-white/5 pt-10">
                    <section className="space-y-4">
                        <h2 className="text-white font-bold uppercase tracking-widest text-base">1. Acceptance of Terms</h2>
                        <p>
                            By accessing and using the <strong>CLSU AIRA-LOGIX</strong> system (the "System"), you agree to be bound by these Terms of Service. This system is designed for official use by authorized personnel of Central Luzon State University (CLSU) and its affiliated departments.
                        </p>
                    </section>

                    <section className="space-y-4">
                        <h2 className="text-white font-bold uppercase tracking-widest text-base">2. Authorized Use</h2>
                        <p>
                            Access to this system is restricted to registered users with valid credentials. Any attempt to access unauthorized modules or data is strictly prohibited and may result in administrative or legal actions in accordance with University policies and applicable laws.
                        </p>
                    </section>

                    <section className="space-y-4">
                        <h2 className="text-white font-bold uppercase tracking-widest text-base">3. ICT Service Requests</h2>
                        <p>
                            All service requests submitted via the System must be accurate and truthful. Unauthorized submission of requests on behalf of others without permission is prohibited. Users are responsible for the confidentiality of their login credentials.
                        </p>
                    </section>

                    <section className="space-y-4">
                        <h2 className="text-white font-bold uppercase tracking-widest text-base">4. Acceptable Behavior</h2>
                        <p>
                            Users shall not use the System to upload malicious software, engage in unauthorized data mining, or attempt to compromise the integrity of the AIRA-LOGIX analytics hub.
                        </p>
                    </section>

                    <section className="space-y-4">
                        <h2 className="text-white font-bold uppercase tracking-widest text-base">5. Governing Law</h2>
                        <p>
                            These terms are governed by the laws of the Republic of the Philippines and the institutional policies of Central Luzon State University.
                        </p>
                    </section>
                </div>

                {/* Footer */}
                <div className="pt-10 border-t border-white/5 text-center">
                    <p className="text-[10px] text-zinc-600 font-bold uppercase tracking-[0.2em]">
                        MISO • CLSU © {new Date().getFullYear()}
                    </p>
                </div>
            </div>
        </div>
    );
}

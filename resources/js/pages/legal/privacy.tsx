import { Head, Link } from '@inertiajs/react';
import React from 'react';
import { ArrowLeft, LockKeyhole } from 'lucide-react';

export default function Privacy() {
    return (
        <div className="min-h-screen bg-black text-zinc-300 selection:bg-[#16a34a] selection:text-white font-sans py-20 px-6">
            <Head title="Privacy Policy - AIRA LOGIX" />
            
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
                        <div className="w-12 h-12 rounded-2xl bg-[#16a34a]/10 border border-[#16a34a]/20 flex items-center justify-center text-[#16a34a]">
                            <LockKeyhole className="w-6 h-6" />
                        </div>
                        <div>
                            <h1 className="text-3xl font-black text-white tracking-tighter uppercase">Privacy Policy</h1>
                            <p className="text-xs text-zinc-500 font-bold uppercase tracking-widest">Last Updated: March 2026</p>
                        </div>
                    </div>
                </div>

                {/* Content */}
                <div className="space-y-10 text-sm leading-relaxed border-t border-white/5 pt-10">
                    <section className="space-y-4">
                        <h2 className="text-white font-bold uppercase tracking-widest text-base">1. Information Collection</h2>
                        <p>
                            We collect basic information required for service processing and security, including your name, institutional email address, department, and ICT service request details. This ensures accountability and efficient service delivery by the MISO department.
                        </p>
                    </section>

                    <section className="space-y-4">
                        <h2 className="text-white font-bold uppercase tracking-widest text-base">2. Use of Information</h2>
                        <p>
                            Your data is strictly used for managing ICT requests, research intelligence analytics, and monitoring system usage. We do not sell or share your data with unauthorized third parties.
                        </p>
                    </section>

                    <section className="space-y-4">
                        <h2 className="text-white font-bold uppercase tracking-widest text-base">3. Data Security</h2>
                        <p>
                            AIRA-LOGIX implements modern encryption and access controls to protect your information. Secure authentication is required for all access points, and all data transmission is encrypted via SSL/TLS.
                        </p>
                    </section>

                    <section className="space-y-4">
                        <h2 className="text-white font-bold uppercase tracking-widest text-base">4. Compliance</h2>
                        <p>
                            The System complies with the <strong>Data Privacy Act of 2012 (Republic Act No. 10173)</strong> as mandated by the Philippine government and CLSU institutional data handling policies.
                        </p>
                    </section>

                    <section className="space-y-4">
                        <h2 className="text-white font-bold uppercase tracking-widest text-base">5. Your Rights</h2>
                        <p>
                            Authorized users have the right to review their active and archived service requests and request corrections of erroneous information.
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

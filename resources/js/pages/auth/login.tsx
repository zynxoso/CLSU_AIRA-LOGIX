import { Head, useForm, Link } from '@inertiajs/react';
import { LoaderCircle, Check } from 'lucide-react';
import React, { FormEventHandler } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface LoginForm {
    email: string;
    password: string;
    remember: boolean;
    [key: string]: string | boolean;
}

interface LoginProps {
    status?: string;
    canResetPassword: boolean;
}

export default function Login({ canResetPassword }: LoginProps) {
    const { data, setData, post, processing, errors, reset } = useForm<LoginForm>({
        email: '',
        password: '',
        remember: false,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <div className="min-h-screen flex flex-col lg:grid lg:grid-cols-2 bg-black selection:bg-[#16a34a] selection:text-white font-sans">
            <Head title="Sign In - AIRA LOGIX" />

            {/* Left Side: Branding/Quote */}
            <div className="hidden lg:flex flex-col justify-between p-12 bg-[#0c0c0e] border-r border-white/5 relative overflow-hidden">
                {/* Subtle Background Glows */}
                <div className="absolute -top-24 -left-24 w-[500px] h-[500px] bg-[#16a34a]/10 rounded-full blur-[120px]"></div>
                <div className="absolute -bottom-24 -right-24 w-[500px] h-[500px] bg-[#16a34a]/5 rounded-full blur-[120px]"></div>

                {/* Logo Section */}
                <div className="relative z-10 flex items-center gap-3 group cursor-default">
                    <div className="flex items-center justify-center w-10 h-10 rounded-xl bg-white/5 border border-white/10 group-hover:border-[#16a34a]/40 transition-all duration-500 shadow-[0_0_20px_rgba(22,163,74,0.1)] overflow-hidden">
                        <img
                            src="/clsu-logo-green.png"
                            alt="CLSU Logo"
                            className="w-7 h-7 object-contain opacity-90 group-hover:opacity-100 transition-opacity duration-300"
                        />
                    </div>
                    <div className="flex flex-col">
                        <span className="text-sm font-black text-white tracking-tighter uppercase leading-tight">
                            CLSU <span className="text-[#16a34a]">ICT</span>
                        </span>
                        <span className="text-[10px] text-zinc-500 font-bold uppercase tracking-widest leading-tight">AIRA LOGIX</span>
                    </div>
                </div>

                {/* Testimonial Section */}
                <div className="relative z-10 max-w-lg">
                    <blockquote className="space-y-6">
                        <p className="text-xl font-medium text-white/90 leading-relaxed italic border-l-2 border-[#16a34a]/30 pl-6 py-2">
                            “This platform has revolutionized how we handle ICT service requests, transforming a manual, paper-based process into a seamless, AI-driven digital experience.”
                        </p>
                        <footer className="flex flex-col gap-1 pl-6">
                            <div className="h-px w-12 bg-[#16a34a] mb-2"></div>
                            <span className="text-xs font-bold text-[#16a34a] uppercase tracking-[0.2em]">
                                MISO | Jan Harry Madrona
                            </span>
                            <span className="text-[10px] font-bold text-zinc-500 uppercase tracking-widest">
                                OJT INTERN 2026
                            </span>
                        </footer>
                    </blockquote>
                </div>
            </div>

            {/* Right Side: Login Form */}
            <div className="flex flex-col justify-center items-center p-8 bg-black relative min-h-screen lg:min-h-0">

                {/* Mobile Logo */}
                <div className="lg:hidden mb-12 flex flex-col items-center">
                    <div className="flex items-center justify-center w-20 h-20 rounded-3xl bg-white/5 border border-white/10 shadow-2xl mb-6 backdrop-blur-xl overflow-hidden">
                        <img
                            src="/clsu-logo-green.png"
                            alt="CLSU Logo"
                            className="w-14 h-14 object-contain"
                        />
                    </div>
                    <h1 className="text-2xl font-black text-white tracking-tighter uppercase">CLSU <span className="text-[#16a34a]">ICT</span></h1>
                </div>

                <div className="w-full max-w-[380px] space-y-10 animate-in fade-in slide-in-from-bottom-6 duration-1000">
                    <div className="flex flex-col space-y-3 text-center lg:text-left">
                        <h1 className="text-4xl font-black tracking-tighter text-white">Sign In</h1>
                        <p className="text-sm text-zinc-500 font-medium">Enter your credentials below to access your account</p>
                    </div>

                    <form onSubmit={submit} className="space-y-6">
                        <div className="space-y-4">
                            {/* Email */}
                            <div className="space-y-2 group">
                                <Label className="sr-only" htmlFor="email">Email</Label>
                                <div className="relative">
                                    <Input
                                        id="email"
                                        type="email"
                                        placeholder="name@example.com"
                                        value={data.email}
                                        onChange={(e) => setData('email', e.target.value)}
                                        className="h-12 w-full rounded-xl border-zinc-800 bg-zinc-900/50 px-4 text-sm text-white placeholder:text-zinc-600 focus:outline-none focus:ring-1 focus:ring-[#16a34a] focus:border-[#16a34a] transition-all hover:bg-zinc-900 duration-300"
                                        required
                                        autoFocus
                                    />
                                </div>
                                <InputError message={errors.email} />
                            </div>

                            {/* Password */}
                            <div className="space-y-2 group">
                                <div className="flex items-center justify-between">
                                    <Label className="sr-only" htmlFor="password">Password</Label>
                                </div>
                                <div className="relative">
                                    <Input
                                        id="password"
                                        type="password"
                                        placeholder="••••••••"
                                        value={data.password}
                                        onChange={(e) => setData('password', e.target.value)}
                                        className="h-12 w-full rounded-xl border-zinc-800 bg-zinc-900/50 px-4 text-sm text-white placeholder:text-zinc-600 focus:outline-none focus:ring-1 focus:ring-[#16a34a] focus:border-[#16a34a] transition-all hover:bg-zinc-900 duration-300"
                                        required
                                    />
                                </div>
                                <InputError message={errors.password} />
                            </div>
                        </div>

                        {/* Remember Me & Reset */}
                        <div className="flex items-center justify-between px-1">
                            <label className="flex items-center gap-3 cursor-pointer group select-none">
                                <div className="relative flex items-center">
                                    <input
                                        type="checkbox"
                                        checked={data.remember}
                                        onChange={(e) => setData('remember', e.target.checked)}
                                        className="sr-only peer"
                                    />
                                    <div className="w-5 h-5 rounded-full border-2 border-zinc-800 bg-zinc-950 peer-checked:bg-[#16a34a] peer-checked:border-[#16a34a] flex items-center justify-center transition-all duration-300 group-hover:border-zinc-600">
                                        <Check className="text-white w-3 h-3 opacity-0 peer-checked:opacity-100 transition-opacity transform scale-75 peer-checked:scale-100" />
                                    </div>
                                </div>
                                <span className="text-[11px] font-bold text-zinc-500 group-hover:text-zinc-300 transition-colors uppercase tracking-[0.1em]">
                                    Remember me
                                </span>
                            </label>

                            {canResetPassword && (
                                <Link
                                    href={route('password.request')}
                                    className="text-[11px] font-bold text-zinc-500 hover:text-white transition-colors uppercase tracking-[0.1em]"
                                >
                                    Forgot Password?
                                </Link>
                            )}
                        </div>

                        <Button
                            type="submit"
                            disabled={processing}
                            className="relative h-12 w-full rounded-xl bg-[#16a34a] text-sm font-black uppercase tracking-widest text-white hover:bg-[#15803d] shadow-[0_4px_20px_rgba(22,163,74,0.3)] hover:shadow-[0_4px_25px_rgba(22,163,74,0.5)] transition-all active:scale-[0.98] overflow-hidden"
                        >
                            {processing ? (
                                <LoaderCircle className="h-5 w-5 animate-spin" />
                            ) : (
                                "Sign In"
                            )}
                        </Button>
                    </form>

                    <div className="relative py-4">
                        <div className="absolute inset-0 flex items-center">
                            <span className="w-full border-t border-zinc-800/50"></span>
                        </div>
                        <div className="relative flex justify-center text-[10px] uppercase tracking-[0.3em]">
                            <span className="bg-black px-4 text-zinc-600 font-bold">System Information</span>
                        </div>
                    </div>

                    <div className="text-center space-y-4">
                        <p className="text-[11px] text-zinc-500 leading-relaxed font-medium uppercase tracking-wider">
                            By accessing this system, you agree to our <br />
                            <a href="#" className="text-white/60 underline underline-offset-4 hover:text-white transition-colors">Terms of Service</a>
                            {" "}and{" "}
                            <a href="#" className="text-white/60 underline underline-offset-4 hover:text-white transition-colors">Privacy Policy</a>
                        </p>
                    </div>
                </div>

                {/* Footer Copyright */}
                <p className="absolute bottom-10 text-[9px] font-bold text-zinc-700 uppercase tracking-[0.4em] lg:tracking-[0.6em]">
                    MISO • CLSU © {new Date().getFullYear()}
                </p>
            </div>
        </div>
    );
}

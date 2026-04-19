import React, { useEffect, useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { PostButton } from '../Components/Form';

export default function Shell({ children }) {
    const { auth, flash } = usePage().props;
    const [theme, setTheme] = useState(() => {
        if (typeof window === 'undefined') {
            return 'light';
        }

        return localStorage.getItem('theme')
            ?? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    });

    useEffect(() => {
        document.documentElement.dataset.theme = theme;
        localStorage.setItem('theme', theme);
    }, [theme]);

    const nextTheme = theme === 'dark' ? 'light' : 'dark';

    return (
        <main className="app-shell">
            <nav className="topbar" aria-label="Principal">
                <Link className="brand" href="/products">
                    <span className="brand-mark" aria-hidden="true">C1</span>
                    <span>Catálogo</span>
                </Link>
                <div className="topbar-actions">
                    <button
                        className="theme-toggle"
                        type="button"
                        onClick={() => setTheme(nextTheme)}
                        aria-label={`Ativar modo ${nextTheme === 'dark' ? 'noturno' : 'claro'}`}
                    >
                        <span aria-hidden="true">{theme === 'dark' ? '☀' : '☾'}</span>
                    </button>
                    {auth?.user ? (
                        <>
                            <span className="user-chip">{auth.user.name}</span>
                            <PostButton action="/logout" label="Sair" />
                        </>
                    ) : (
                        <>
                            <Link href="/login">Entrar</Link>
                            <Link className="button secondary" href="/register">Criar conta</Link>
                        </>
                    )}
                </div>
            </nav>
            {flash?.success && <div className="notice success">{flash.success}</div>}
            {children}
        </main>
    );
}

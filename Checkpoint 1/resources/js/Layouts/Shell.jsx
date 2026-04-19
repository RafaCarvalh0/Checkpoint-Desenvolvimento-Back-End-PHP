import React from 'react';
import { Link, usePage } from '@inertiajs/react';
import { PostButton } from '../Components/Form';

export default function Shell({ children }) {
    const { auth, flash } = usePage().props;

    return (
        <main className="app-shell">
            <nav className="topbar" aria-label="Principal">
                <Link className="brand" href="/products">Catalogo</Link>
                <div className="topbar-actions">
                    {auth?.user ? (
                        <>
                            <span>{auth.user.name}</span>
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

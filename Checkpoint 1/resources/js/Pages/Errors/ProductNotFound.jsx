import React from 'react';
import { Head, Link } from '@inertiajs/react';
import Shell from '../../Layouts/Shell';

export default function ProductNotFound({ message }) {
    return (
        <Shell>
            <Head title="Produto nao encontrado" />
            <section className="empty-state">
                <h1>{message}</h1>
                <p>Confira o codigo informado ou volte para o catalogo.</p>
                <Link href="/products">Ver catalogo</Link>
            </section>
        </Shell>
    );
}

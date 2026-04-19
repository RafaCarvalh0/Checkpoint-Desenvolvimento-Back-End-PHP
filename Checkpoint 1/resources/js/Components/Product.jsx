import React from 'react';
import { Csrf } from './Form';

export const money = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
});

export function StatusBadge({ status }) {
    return <span className={`status ${status}`}>{status === 'active' ? 'Ativo' : 'Inativo'}</span>;
}

export function DeleteProduct({ id, csrfToken }) {
    return (
        <form method="POST" action={`/products/${id}`} onSubmit={(event) => {
            if (!window.confirm('Remover este produto?')) {
                event.preventDefault();
            }
        }}>
            <Csrf token={csrfToken} />
            <input type="hidden" name="_method" value="DELETE" />
            <button className="button danger" type="submit">Remover</button>
        </form>
    );
}

export function initials(value = '') {
    return value
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((word) => word[0]?.toUpperCase())
        .join('') || 'P';
}

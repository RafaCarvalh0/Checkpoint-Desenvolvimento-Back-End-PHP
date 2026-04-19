import React from 'react';
import { usePage } from '@inertiajs/react';

export function Field({ label, ...inputProps }) {
    const normalizedProps = {
        ...inputProps,
        defaultValue: inputProps.defaultValue ?? '',
    };

    return (
        <label>
            {label}
            <input {...normalizedProps} />
        </label>
    );
}

export function Errors({ errors }) {
    const messages = Object.values(errors ?? {}).flat().filter(Boolean);

    if (!messages.length) {
        return null;
    }

    return (
        <div className="notice error">
            <p>Corrija os campos abaixo.</p>
            <ul>
                {messages.map((error) => <li key={error}>{error}</li>)}
            </ul>
        </div>
    );
}

export function Csrf({ token }) {
    const { csrfToken } = usePage().props;

    return <input type="hidden" name="_token" value={token ?? csrfToken ?? ''} />;
}

export function PostButton({ action, label }) {
    return (
        <form method="POST" action={action}>
            <Csrf />
            <button className="link-button" type="submit">{label}</button>
        </form>
    );
}

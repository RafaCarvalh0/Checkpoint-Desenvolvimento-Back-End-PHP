import React from 'react';
import { Link, Head } from '@inertiajs/react';
import { Csrf, Errors, Field } from '../../Components/Form';
import Shell from '../../Layouts/Shell';

export default function Register({ errors = {}, old = {} }) {
    return (
        <Shell>
            <Head title="Cadastrar usuario" />
            <section className="auth-layout">
                <div>
                    <p className="eyebrow">Novo acesso</p>
                    <h1>Cadastrar usuario</h1>
                    <p className="lead">Crie uma conta para comecar a administrar o catalogo.</p>
                </div>
                <form className="panel form-stack" method="POST" action="/register">
                    <Csrf />
                    <Errors errors={errors} />
                    <Field label="Nome" name="name" defaultValue={old.name} required maxLength="255" />
                    <Field label="E-mail" name="email" type="email" defaultValue={old.email} required />
                    <Field label="Senha" name="password" type="password" required minLength="8" />
                    <Field label="Confirmar senha" name="password_confirmation" type="password" required minLength="8" />
                    <button className="button" type="submit">Cadastrar</button>
                    <Link href="/login">Ja tenho cadastro</Link>
                </form>
            </section>
        </Shell>
    );
}

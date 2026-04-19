import React from 'react';
import { Link, Head } from '@inertiajs/react';
import { Csrf, Errors, Field } from '../../Components/Form';
import Shell from '../../Layouts/Shell';

export default function Login({ errors = {}, old = {} }) {
    return (
        <Shell>
            <Head title="Login" />
            <section className="auth-layout">
                <div>
                    <p className="eyebrow">Área administrativa</p>
                    <h1>Entrar</h1>
                    <p className="lead">Acesse sua conta para cadastrar, editar e controlar os produtos.</p>
                </div>
                <form className="panel form-stack" method="POST" action="/login">
                    <Csrf />
                    <Errors errors={errors} />
                    <Field label="E-mail" name="email" type="email" defaultValue={old.email} required autoFocus />
                    <Field label="Senha" name="password" type="password" required />
                    <label className="check">
                        <input type="checkbox" name="remember" value="1" />
                        Manter conectado
                    </label>
                    <button className="button" type="submit">Entrar</button>
                    <Link href="/register">Criar cadastro</Link>
                </form>
            </section>
        </Shell>
    );
}

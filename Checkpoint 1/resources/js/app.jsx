import React from 'react';
import { createRoot } from 'react-dom/client';

const money = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
});

function App() {
    const props = window.__APP_PROPS__ ?? {};

    return (
        <Shell auth={props.auth} success={props.success}>
            {props.page === 'login' && <LoginPage {...props} />}
            {props.page === 'register' && <RegisterPage {...props} />}
            {props.page === 'products.index' && <ProductsIndex {...props} />}
            {props.page === 'products.show' && <ProductShow {...props} />}
            {props.page === 'products.form' && <ProductFormPage {...props} />}
        </Shell>
    );
}

function Shell({ auth, success, children }) {
    return (
        <main className="app-shell">
            <nav className="topbar" aria-label="Principal">
                <a className="brand" href="/products">Catálogo</a>
                <div className="topbar-actions">
                    {auth?.user ? (
                        <>
                            <span>{auth.user.name}</span>
                            <PostButton action="/logout" label="Sair" />
                        </>
                    ) : (
                        <>
                            <a href="/login">Entrar</a>
                            <a className="button secondary" href="/register">Criar conta</a>
                        </>
                    )}
                </div>
            </nav>
            {success && <div className="notice success">{success}</div>}
            {children}
        </main>
    );
}

function LoginPage({ errors = [], old = {}, csrfToken }) {
    return (
        <section className="auth-layout">
            <div>
                <p className="eyebrow">Área administrativa</p>
                <h1>Entrar</h1>
                <p className="lead">Acesse sua conta para cadastrar, editar e controlar os produtos.</p>
            </div>
            <form className="panel form-stack" method="POST" action="/login">
                <Csrf token={csrfToken} />
                <Errors errors={errors} />
                <Field label="E-mail" name="email" type="email" defaultValue={old.email} required autoFocus />
                <Field label="Senha" name="password" type="password" required />
                <label className="check">
                    <input type="checkbox" name="remember" value="1" />
                    Manter conectado
                </label>
                <button className="button" type="submit">Entrar</button>
                <a href="/register">Criar cadastro</a>
            </form>
        </section>
    );
}

function RegisterPage({ errors = [], old = {}, csrfToken }) {
    return (
        <section className="auth-layout">
            <div>
                <p className="eyebrow">Novo acesso</p>
                <h1>Cadastrar usuário</h1>
                <p className="lead">Crie uma conta para começar a administrar o catálogo.</p>
            </div>
            <form className="panel form-stack" method="POST" action="/register">
                <Csrf token={csrfToken} />
                <Errors errors={errors} />
                <Field label="Nome" name="name" defaultValue={old.name} required maxLength="255" />
                <Field label="E-mail" name="email" type="email" defaultValue={old.email} required />
                <Field label="Senha" name="password" type="password" required minLength="8" />
                <Field label="Confirmar senha" name="password_confirmation" type="password" required minLength="8" />
                <button className="button" type="submit">Cadastrar</button>
                <a href="/login">Já tenho cadastro</a>
            </form>
        </section>
    );
}

function ProductsIndex({ auth, products = [], filters = {} }) {
    return (
        <section className="catalog-page">
            <div className="page-heading">
                <div>
                    <p className="eyebrow">Produtos</p>
                    <h1>Catálogo de Produtos</h1>
                    <p className="lead">Consulte estoque, preço, SKU e status em uma visão direta.</p>
                </div>
                {auth?.user && <a className="button" href="/products/create">Novo produto</a>}
            </div>

            <form className="filters" method="GET" action="/products">
                <Field label="Nome" name="name" defaultValue={filters.name} />
                <Field label="SKU" name="sku" defaultValue={filters.sku} />
                <label>
                    Status
                    <select name="status" defaultValue={filters.status ?? ''}>
                        <option value="">Todos</option>
                        <option value="active">Ativo</option>
                        <option value="inactive">Inativo</option>
                    </select>
                </label>
                <button className="button secondary" type="submit">Filtrar</button>
            </form>

            {products.length > 0 ? (
                <div className="product-grid">
                    {products.map((product) => (
                        <ProductCard key={product.id} product={product} auth={auth} />
                    ))}
                </div>
            ) : (
                <div className="empty-state">Nenhum produto cadastrado.</div>
            )}
        </section>
    );
}

function ProductCard({ product, auth }) {
    return (
        <article className="product-card">
            <div className="product-thumb" aria-hidden="true">{initials(product.name)}</div>
            <div className="product-card-body">
                <div className="card-title-row">
                    <h2><a href={`/products/${product.id}`}>{product.name}</a></h2>
                    <StatusBadge status={product.status} />
                </div>
                <p>{product.description || 'Sem descrição.'}</p>
                <dl className="details">
                    <div><dt>Preço</dt><dd>{money.format(Number(product.price))}</dd></div>
                    <div><dt>Estoque</dt><dd>{product.stock}</dd></div>
                    <div><dt>SKU</dt><dd>{product.sku}</dd></div>
                    <div><dt>Slug</dt><dd>{product.slug}</dd></div>
                </dl>
                {auth?.user && (
                    <div className="card-actions">
                        <a className="button secondary" href={`/products/${product.id}/edit`}>Editar</a>
                        {auth.isAdmin && <DeleteProduct id={product.id} />}
                    </div>
                )}
            </div>
        </article>
    );
}

function ProductShow({ auth, product, images = [], csrfToken }) {
    return (
        <section className="product-detail">
            <a href="/products">Voltar ao catálogo</a>
            <div className="detail-layout">
                <div className="gallery">
                    {images.length > 0 ? images.map((image) => (
                        <img key={image.id ?? image.url} src={image.thumbnail_url || image.url} alt={`Imagem de ${product.name}`} />
                    )) : <div className="product-hero-mark" aria-hidden="true">{initials(product.name)}</div>}
                </div>
                <div className="panel detail-panel">
                    <StatusBadge status={product.status} />
                    <h1>{product.name}</h1>
                    <p className="lead">{product.description || 'Sem descrição.'}</p>
                    <dl className="details large">
                        <div><dt>Preço</dt><dd>{money.format(Number(product.price))}</dd></div>
                        <div><dt>Estoque</dt><dd>{product.stock}</dd></div>
                        <div><dt>SKU</dt><dd>{product.sku}</dd></div>
                        <div><dt>Slug</dt><dd>{product.slug}</dd></div>
                    </dl>
                    {auth?.user ? (
                        <div className="card-actions">
                            <a className="button" href={`/products/${product.id}/edit`}>Editar</a>
                            {auth.isAdmin && <DeleteProduct id={product.id} csrfToken={csrfToken} />}
                        </div>
                    ) : (
                        <a className="button" href="/login">Entrar para administrar</a>
                    )}
                </div>
            </div>
        </section>
    );
}

function ProductFormPage({ csrfToken, errors = [], product = null, images = [], statuses = [], mode }) {
    const isEdit = mode === 'edit';
    const action = isEdit ? `/products/${product.id}` : '/products';

    return (
        <section className="form-page">
            <a href={isEdit ? `/products/${product.id}` : '/products'}>
                {isEdit ? 'Voltar ao produto' : 'Voltar ao catálogo'}
            </a>
            <div className="page-heading compact">
                <div>
                    <p className="eyebrow">{isEdit ? 'Edição' : 'Cadastro'}</p>
                    <h1>{isEdit ? 'Editar Produto' : 'Novo Produto'}</h1>
                </div>
            </div>
            <form className="panel product-form" method="POST" action={action} encType="multipart/form-data">
                <Csrf token={csrfToken} />
                {isEdit && <input type="hidden" name="_method" value="PUT" />}
                <Errors errors={errors} />
                <Field label="Nome" name="name" defaultValue={product?.name} required maxLength="120" />
                <label>
                    Descrição
                    <textarea name="description" defaultValue={product?.description ?? ''} />
                </label>
                <Field label="Preço" name="price" type="number" defaultValue={product?.price} required min="0" step="0.01" />
                <Field label="SKU" name="sku" defaultValue={product?.sku} required maxLength="60" />
                <Field label="Estoque" name="stock" type="number" defaultValue={product?.stock} required min="0" step="1" />
                <label>
                    Status
                    <select name="status" defaultValue={product?.status ?? 'active'} required>
                        {statuses.map((status) => (
                            <option key={status.value} value={status.value}>{status.label}</option>
                        ))}
                    </select>
                </label>
                <label>
                    Imagem do produto
                    <input type="file" name="image" accept="image/jpeg,image/png,image/webp" />
                </label>
                {images.length > 0 && (
                    <div className="current-images">
                        <span>Imagem atual</span>
                        {images.map((image) => (
                            <img key={image.id ?? image.url} src={image.thumbnail_url || image.url} alt={`Imagem de ${product.name}`} />
                        ))}
                    </div>
                )}
                <button className="button" type="submit">{isEdit ? 'Salvar produto' : 'Criar produto'}</button>
            </form>
        </section>
    );
}

function Field({ label, ...inputProps }) {
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

function Errors({ errors }) {
    if (!errors?.length) {
        return null;
    }

    return (
        <div className="notice error">
            <p>Corrija os campos abaixo.</p>
            <ul>
                {errors.map((error) => <li key={error}>{error}</li>)}
            </ul>
        </div>
    );
}

function StatusBadge({ status }) {
    return <span className={`status ${status}`}>{status === 'active' ? 'Ativo' : 'Inativo'}</span>;
}

function PostButton({ action, label }) {
    return (
        <form method="POST" action={action}>
            <Csrf token={window.__APP_PROPS__?.csrfToken} />
            <button className="link-button" type="submit">{label}</button>
        </form>
    );
}

function DeleteProduct({ id, csrfToken }) {
    return (
        <form method="POST" action={`/products/${id}`} onSubmit={(event) => {
            if (!window.confirm('Remover este produto?')) {
                event.preventDefault();
            }
        }}>
            <Csrf token={csrfToken ?? window.__APP_PROPS__?.csrfToken} />
            <input type="hidden" name="_method" value="DELETE" />
            <button className="button danger" type="submit">Remover</button>
        </form>
    );
}

function Csrf({ token }) {
    return <input type="hidden" name="_token" value={token ?? ''} />;
}

function initials(value = '') {
    return value
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((word) => word[0]?.toUpperCase())
        .join('') || 'P';
}

createRoot(document.getElementById('app')).render(<App />);

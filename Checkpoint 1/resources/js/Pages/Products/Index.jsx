import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { Field } from '../../Components/Form';
import { DeleteProduct, initials, money, StatusBadge } from '../../Components/Product';
import Shell from '../../Layouts/Shell';

export default function Index({ auth, products = [], filters = {} }) {
    return (
        <Shell>
            <Head title="Catalogo de Produtos" />
            <section className="catalog-page">
                <div className="page-heading">
                    <div>
                        <p className="eyebrow">Produtos</p>
                        <h1>Catalogo de Produtos</h1>
                        <p className="lead">Consulte estoque, preco, SKU e status em uma visao direta.</p>
                    </div>
                    {auth?.user && <Link className="button" href="/products/create">Novo produto</Link>}
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
        </Shell>
    );
}

function ProductCard({ product, auth }) {
    return (
        <article className="product-card">
            <div className="product-thumb" aria-hidden="true">{initials(product.name)}</div>
            <div className="product-card-body">
                <div className="card-title-row">
                    <h2><Link href={`/products/${product.id}`}>{product.name}</Link></h2>
                    <StatusBadge status={product.status} />
                </div>
                <p>{product.description || 'Sem descricao.'}</p>
                <dl className="details">
                    <div><dt>Preco</dt><dd>{money.format(Number(product.price))}</dd></div>
                    <div><dt>Estoque</dt><dd>{product.stock}</dd></div>
                    <div><dt>SKU</dt><dd>{product.sku}</dd></div>
                    <div><dt>Slug</dt><dd>{product.slug}</dd></div>
                </dl>
                {auth?.user && (
                    <div className="card-actions">
                        <Link className="button secondary" href={`/products/${product.id}/edit`}>Editar</Link>
                        {auth.isAdmin && <DeleteProduct id={product.id} />}
                    </div>
                )}
            </div>
        </article>
    );
}

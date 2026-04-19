import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { DeleteProduct, initials, money, StatusBadge } from '../../Components/Product';
import Shell from '../../Layouts/Shell';

export default function Show({ auth, product, images = [], csrfToken }) {
    return (
        <Shell>
            <Head title={product.name} />
            <section className="product-detail">
                <Link href="/products">Voltar ao catalogo</Link>
                <div className="detail-layout">
                    <div className="gallery">
                        {images.length > 0 ? images.map((image) => (
                            <img key={image.id ?? image.url} src={image.thumbnail_url || image.url} alt={`Imagem de ${product.name}`} />
                        )) : <div className="product-hero-mark" aria-hidden="true">{initials(product.name)}</div>}
                    </div>
                    <div className="panel detail-panel">
                        <StatusBadge status={product.status} />
                        <h1>{product.name}</h1>
                        <p className="lead">{product.description || 'Sem descricao.'}</p>
                        <dl className="details large">
                            <div><dt>Preco</dt><dd>{money.format(Number(product.price))}</dd></div>
                            <div><dt>Estoque</dt><dd>{product.stock}</dd></div>
                            <div><dt>SKU</dt><dd>{product.sku}</dd></div>
                            <div><dt>Slug</dt><dd>{product.slug}</dd></div>
                        </dl>
                        {auth?.user ? (
                            <div className="card-actions">
                                <Link className="button" href={`/products/${product.id}/edit`}>Editar</Link>
                                {auth.isAdmin && <DeleteProduct id={product.id} csrfToken={csrfToken} />}
                            </div>
                        ) : (
                            <Link className="button" href="/login">Entrar para administrar</Link>
                        )}
                    </div>
                </div>
            </section>
        </Shell>
    );
}

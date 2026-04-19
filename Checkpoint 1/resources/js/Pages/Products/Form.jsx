import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { Csrf, Errors, Field } from '../../Components/Form';
import Shell from '../../Layouts/Shell';

export default function Form({ errors = {}, product = null, images = [], statuses = [], mode }) {
    const isEdit = mode === 'edit';
    const action = isEdit ? `/products/${product.id}` : '/products';

    return (
        <Shell>
            <Head title={isEdit ? 'Editar Produto' : 'Novo Produto'} />
            <section className="form-page">
                <Link href={isEdit ? `/products/${product.id}` : '/products'}>
                    {isEdit ? 'Voltar ao produto' : 'Voltar ao catalogo'}
                </Link>
                <div className="page-heading compact">
                    <div>
                        <p className="eyebrow">{isEdit ? 'Edicao' : 'Cadastro'}</p>
                        <h1>{isEdit ? 'Editar Produto' : 'Novo Produto'}</h1>
                    </div>
                </div>
                <form className="panel product-form" method="POST" action={action} encType="multipart/form-data">
                    <Csrf />
                    {isEdit && <input type="hidden" name="_method" value="PUT" />}
                    <Errors errors={errors} />
                    <Field label="Nome" name="name" defaultValue={product?.name} required maxLength="120" />
                    <label>
                        Descricao
                        <textarea name="description" defaultValue={product?.description ?? ''} />
                    </label>
                    <Field label="Preco" name="price" type="number" defaultValue={product?.price} required min="0" step="0.01" />
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
        </Shell>
    );
}

@csrf

<label>
    Nome
    <input type="text" name="name" value="{{ old('name', $product?->getName()) }}" required maxlength="120">
</label>
@error('name')
    <p>{{ $message }}</p>
@enderror

<label>
    Descrição
    <textarea name="description">{{ old('description', $product?->getDescription()) }}</textarea>
</label>
@error('description')
    <p>{{ $message }}</p>
@enderror

<label>
    Preço
    <input type="number" name="price" value="{{ old('price', $product?->getPrice()) }}" required min="0" step="0.01">
</label>
@error('price')
    <p>{{ $message }}</p>
@enderror

<label>
    SKU
    <input type="text" name="sku" value="{{ old('sku', $product?->getSku()) }}" required maxlength="60">
</label>
@error('sku')
    <p>{{ $message }}</p>
@enderror

<label>
    Estoque
    <input type="number" name="stock" value="{{ old('stock', $product?->getStock()) }}" required min="0" step="1">
</label>
@error('stock')
    <p>{{ $message }}</p>
@enderror

<label>
    Status
    <select name="status" required>
        @foreach ($statuses as $status)
            <option value="{{ $status->value }}" @selected(old('status', $product?->getStatus()->value ?? 'active') === $status->value)>
                {{ $status->value }}
            </option>
        @endforeach
    </select>
</label>
@error('status')
    <p>{{ $message }}</p>
@enderror

<button type="submit">{{ $buttonLabel }}</button>

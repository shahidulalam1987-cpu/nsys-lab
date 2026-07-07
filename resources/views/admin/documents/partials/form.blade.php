@php($metadataOnly = $metadataOnly ?? false)
<div>
    <label>Title</label>
    <input type="text" name="title" value="{{ old('title', $document?->title) }}" required>
</div>
<div>
    <label>Category</label>
    <select name="category" required>
        @foreach($categories as $category)
            <option value="{{ $category }}" @selected(old('category', $document?->category ?? ($prefill['category'] ?? 'General')) === $category)>{{ $category }}</option>
        @endforeach
    </select>
</div>
<div style="grid-column:1 / -1;">
    <label>Description</label>
    <textarea name="description" rows="3">{{ old('description', $document?->description) }}</textarea>
</div>
<div>
    <label>Tags</label>
    <input type="text" name="tags" value="{{ old('tags', $document?->tags ? implode(', ', $document->tags) : '') }}" placeholder="contract, hr, receipt">
</div>
<div>
    <label>Expiry Date</label>
    <input type="date" name="expiry_date" value="{{ old('expiry_date', $document?->expiry_date?->toDateString()) }}">
</div>
@unless($metadataOnly)
<div>
    <label>Owner Module</label>
    <select name="owner_module">
        <option value="">General</option>
        @foreach($ownerModules as $value => $label)
            <option value="{{ $value }}" @selected(old('owner_module', $prefill['owner_module'] ?? '') === $value)>{{ $label }}</option>
        @endforeach
    </select>
</div>
<div>
    <label>Owner Record ID</label>
    <input type="number" name="owner_record_id" value="{{ old('owner_record_id', $prefill['owner_record_id'] ?? '') }}" min="1" placeholder="Optional record ID">
</div>
<div style="grid-column:1 / -1;">
    <label>Document File</label>
    <input type="file" name="document" accept=".pdf,.docx,.xlsx,.png,.jpg,.jpeg,.zip" required>
    <p class="text-muted" style="margin:6px 0 0;">Allowed: PDF, DOCX, XLSX, PNG, JPG, ZIP. Max 10 MB.</p>
</div>
@endunless

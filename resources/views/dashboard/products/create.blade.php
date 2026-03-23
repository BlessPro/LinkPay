@php
    $title = 'Create Product';
    $defaultProduct = [
        'name' => '',
        'description' => '',
        'price' => '',
        'stock_quantity' => 0,
        'low_stock_threshold' => 5,
        'status' => \App\Models\Product::STATUS_IN_STOCK,
        'is_active' => 1,
    ];
    $oldProducts = old('products');
    if (! is_array($oldProducts) || count($oldProducts) < 1) {
        $oldProducts = [$defaultProduct];
    }
@endphp
@extends('layouts.dashboard')

@section('content')
    <div class="max-w-4xl rounded-2xl border border-slate-200 bg-white p-6 pb-24 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-900">Add products</h2>
        <p class="mt-1 text-sm text-slate-500">You can upload multiple products at once.</p>
        @error('upload')
            <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $message }}</div>
        @enderror
        <div id="upload-summary" class="mt-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
            <p class="font-semibold">Upload summary</p>
            <p id="upload-summary-text" class="mt-1 text-xs text-slate-500">No image selected yet.</p>
            <p class="mt-2 text-xs text-slate-500">
                If a file cannot be compressed enough, use
                <a href="https://tinypng.com" target="_blank" rel="noreferrer noopener" class="font-semibold text-emerald-700 underline">TinyPNG</a>
                in a new tab, then upload again.
            </p>
        </div>
        <form id="bulk-product-form" class="mt-6 space-y-5" method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data">
            @csrf
            <div id="product-items" class="space-y-4">
                @foreach($oldProducts as $index => $item)
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/50 p-4 product-item" data-index="{{ $index }}">
                        <div class="mb-3 flex items-center justify-between">
                            <p class="text-sm font-semibold text-slate-800">Product <span class="product-number">{{ $index + 1 }}</span></p>
                            <button type="button" class="rounded-full border border-rose-200 px-3 py-1 text-xs font-semibold text-rose-600 hover:bg-rose-50 remove-product" @if($loop->count === 1) hidden @endif>
                                Remove
                            </button>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-slate-700">Name</label>
                            <input name="products[{{ $index }}][name]" value="{{ $item['name'] ?? '' }}" required class="mt-2 w-full rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500" />
                            @error("products.$index.name") <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
                        </div>
                        <div class="mt-4">
                            <label class="text-sm font-medium text-slate-700">Description</label>
                            <textarea name="products[{{ $index }}][description]" rows="3" class="mt-2 w-full rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500">{{ $item['description'] ?? '' }}</textarea>
                            @error("products.$index.description") <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
                        </div>
                        <div class="mt-4">
                            <label class="text-sm font-medium text-slate-700">Price</label>
                            <input name="products[{{ $index }}][price]" value="{{ $item['price'] ?? '' }}" required type="number" step="0.01" class="mt-2 w-full rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500" />
                            @error("products.$index.price") <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
                        </div>
                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="text-sm font-medium text-slate-700">Stock quantity</label>
                                <input name="products[{{ $index }}][stock_quantity]" value="{{ $item['stock_quantity'] ?? 0 }}" type="number" min="0" class="mt-2 w-full rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500" />
                                @error("products.$index.stock_quantity") <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="text-sm font-medium text-slate-700">Low stock threshold</label>
                                <input name="products[{{ $index }}][low_stock_threshold]" value="{{ $item['low_stock_threshold'] ?? 5 }}" type="number" min="0" class="mt-2 w-full rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500" />
                                @error("products.$index.low_stock_threshold") <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div class="mt-4">
                            <label class="text-sm font-medium text-slate-700">Image (optional)</label>
                            <input name="products[{{ $index }}][image]" type="file" accept="image/*" capture="environment" data-product-image="true" class="mt-2 w-full rounded-xl border-slate-200 file:mr-4 file:rounded-full file:border-0 file:bg-emerald-50 file:px-4 file:py-2 file:text-xs file:font-semibold file:text-emerald-700" />
                            @error("products.$index.image") <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
                            <p class="mt-1 text-xs text-slate-500 upload-hint">Image will be auto-compressed before upload.</p>
                        </div>
                        <div class="mt-4">
                            <label class="text-sm font-medium text-slate-700">Stock status</label>
                            <select name="products[{{ $index }}][status]" class="mt-2 w-full rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500">
                                @foreach(\App\Models\Product::statusOptions() as $value => $label)
                                    <option value="{{ $value }}" @selected(($item['status'] ?? \App\Models\Product::STATUS_IN_STOCK) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error("products.$index.status") <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
                        </div>
                        <div class="mt-4 flex items-center gap-3">
                            <input type="hidden" name="products[{{ $index }}][is_active]" value="0" />
                            <input id="is_active_{{ $index }}" name="products[{{ $index }}][is_active]" type="checkbox" value="1" @checked(($item['is_active'] ?? 1) == 1) class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" />
                            <label for="is_active_{{ $index }}" class="text-sm text-slate-600">Active</label>
                        </div>
                    </div>
                @endforeach
            </div>
            <div>
                <button type="button" id="add-product" class="rounded-full border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-100">
                    + Next product
                </button>
            </div>
            <div class="hidden items-center gap-4 sm:flex">
                <button type="submit" class="rounded-full bg-emerald-600 px-6 py-3 text-sm font-semibold text-white hover:bg-emerald-500">
                    Save products
                </button>
                <a href="{{ route('products.index') }}" class="text-sm font-semibold text-slate-500 hover:text-slate-700">Cancel</a>
            </div>
            <div class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white/95 p-3 backdrop-blur sm:hidden">
                <div class="mx-auto flex max-w-4xl items-center gap-2">
                    <a href="{{ route('products.index') }}" class="inline-flex flex-1 items-center justify-center rounded-full border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-700">
                        Cancel
                    </a>
                    <button type="submit" class="inline-flex flex-1 items-center justify-center rounded-full bg-emerald-600 px-4 py-2 text-xs font-semibold text-white">
                        Save products
                    </button>
                </div>
            </div>
        </form>
    </div>

    <template id="product-item-template">
        <div class="rounded-2xl border border-slate-200 bg-slate-50/50 p-4 product-item" data-index="__INDEX__">
            <div class="mb-3 flex items-center justify-between">
                <p class="text-sm font-semibold text-slate-800">Product <span class="product-number">__NUMBER__</span></p>
                <button type="button" class="rounded-full border border-rose-200 px-3 py-1 text-xs font-semibold text-rose-600 hover:bg-rose-50 remove-product">
                    Remove
                </button>
            </div>
            <div>
                <label class="text-sm font-medium text-slate-700">Name</label>
                <input name="products[__INDEX__][name]" required class="mt-2 w-full rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500" />
            </div>
            <div class="mt-4">
                <label class="text-sm font-medium text-slate-700">Description</label>
                <textarea name="products[__INDEX__][description]" rows="3" class="mt-2 w-full rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500"></textarea>
            </div>
            <div class="mt-4">
                <label class="text-sm font-medium text-slate-700">Price</label>
                <input name="products[__INDEX__][price]" required type="number" step="0.01" class="mt-2 w-full rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500" />
            </div>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="text-sm font-medium text-slate-700">Stock quantity</label>
                    <input name="products[__INDEX__][stock_quantity]" value="0" type="number" min="0" class="mt-2 w-full rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500" />
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-700">Low stock threshold</label>
                    <input name="products[__INDEX__][low_stock_threshold]" value="5" type="number" min="0" class="mt-2 w-full rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500" />
                </div>
            </div>
            <div class="mt-4">
                <label class="text-sm font-medium text-slate-700">Image (optional)</label>
                <input name="products[__INDEX__][image]" type="file" accept="image/*" capture="environment" data-product-image="true" class="mt-2 w-full rounded-xl border-slate-200 file:mr-4 file:rounded-full file:border-0 file:bg-emerald-50 file:px-4 file:py-2 file:text-xs file:font-semibold file:text-emerald-700" />
                <p class="mt-1 text-xs text-slate-500 upload-hint">Image will be auto-compressed before upload.</p>
            </div>
            <div class="mt-4">
                <label class="text-sm font-medium text-slate-700">Stock status</label>
                <select name="products[__INDEX__][status]" class="mt-2 w-full rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500">
                    @foreach(\App\Models\Product::statusOptions() as $value => $label)
                        <option value="{{ $value }}" @selected(\App\Models\Product::STATUS_IN_STOCK === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mt-4 flex items-center gap-3">
                <input type="hidden" name="products[__INDEX__][is_active]" value="0" />
                <input id="is_active___INDEX__" name="products[__INDEX__][is_active]" type="checkbox" value="1" checked class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" />
                <label for="is_active___INDEX__" class="text-sm text-slate-600">Active</label>
            </div>
        </div>
    </template>

    <script>
        (() => {
            const list = document.getElementById('product-items');
            const addButton = document.getElementById('add-product');
            const template = document.getElementById('product-item-template');
            const form = document.getElementById('bulk-product-form');
            const summaryText = document.getElementById('upload-summary-text');

            if (!list || !addButton || !template || !form || !summaryText) return;

            const TOTAL_SOFT_LIMIT_BYTES = 25 * 1024 * 1024;
            const PER_IMAGE_TARGET_BYTES = 1.5 * 1024 * 1024;
            const PER_IMAGE_HARD_WARN_BYTES = 6 * 1024 * 1024;

            const formatBytes = (bytes) => {
                if (bytes <= 0) return '0 B';
                const units = ['B', 'KB', 'MB', 'GB'];
                let value = bytes;
                let unitIndex = 0;
                while (value >= 1024 && unitIndex < units.length - 1) {
                    value /= 1024;
                    unitIndex++;
                }
                return `${value.toFixed(unitIndex === 0 ? 0 : 1)} ${units[unitIndex]}`;
            };

            const imageInputElements = () => Array.from(form.querySelectorAll('input[type="file"][data-product-image="true"]'));

            const updateSummary = () => {
                const files = imageInputElements().map((input) => input.files?.[0]).filter(Boolean);
                const total = files.reduce((sum, file) => sum + file.size, 0);
                if (files.length === 0) {
                    summaryText.textContent = 'No image selected yet.';
                    summaryText.className = 'mt-1 text-xs text-slate-500';
                    return;
                }
                const warning = total > TOTAL_SOFT_LIMIT_BYTES;
                summaryText.textContent = `${files.length} image(s), total ${formatBytes(total)}.`;
                summaryText.className = warning
                    ? 'mt-1 text-xs text-rose-600 font-semibold'
                    : 'mt-1 text-xs text-emerald-700';
                if (warning) {
                    summaryText.textContent += ' Total is high; compress images or split upload.';
                }
            };

            const loadImage = (file) => new Promise((resolve, reject) => {
                const url = URL.createObjectURL(file);
                const img = new Image();
                img.onload = () => {
                    URL.revokeObjectURL(url);
                    resolve(img);
                };
                img.onerror = () => {
                    URL.revokeObjectURL(url);
                    reject(new Error('Image load failed'));
                };
                img.src = url;
            });

            const canvasToBlob = (canvas, quality) => new Promise((resolve, reject) => {
                canvas.toBlob((blob) => {
                    if (!blob) {
                        reject(new Error('Canvas conversion failed'));
                        return;
                    }
                    resolve(blob);
                }, 'image/jpeg', quality);
            });

            const compressFile = async (file) => {
                if (!file.type.startsWith('image/')) return null;
                if (file.type === 'image/gif') return null;

                const img = await loadImage(file);
                const maxWidth = 1800;
                const maxHeight = 1800;
                const scale = Math.min(1, maxWidth / img.width, maxHeight / img.height);
                const width = Math.max(1, Math.round(img.width * scale));
                const height = Math.max(1, Math.round(img.height * scale));
                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext('2d');
                if (!ctx) return null;
                ctx.drawImage(img, 0, 0, width, height);

                let bestBlob = null;
                for (const quality of [0.85, 0.8, 0.75, 0.7, 0.65, 0.6]) {
                    const blob = await canvasToBlob(canvas, quality);
                    bestBlob = blob;
                    if (blob.size <= PER_IMAGE_TARGET_BYTES) break;
                }
                if (!bestBlob || bestBlob.size >= file.size) return null;

                return new File([bestBlob], file.name.replace(/\.\w+$/, '.jpg'), {
                    type: 'image/jpeg',
                    lastModified: Date.now(),
                });
            };

            const setHint = (input, message, tone = 'default') => {
                const hint = input.closest('div')?.querySelector('.upload-hint');
                if (!hint) return;
                hint.textContent = message;
                hint.className = tone === 'error'
                    ? 'mt-1 text-xs text-rose-600 upload-hint'
                    : tone === 'ok'
                        ? 'mt-1 text-xs text-emerald-700 upload-hint'
                        : 'mt-1 text-xs text-slate-500 upload-hint';
            };

            const handleImageChange = async (input) => {
                const file = input.files?.[0];
                if (!file) {
                    setHint(input, 'Image will be auto-compressed before upload.');
                    updateSummary();
                    return;
                }

                setHint(input, `Selected ${formatBytes(file.size)}. Compressing...`);
                try {
                    const compressed = await compressFile(file);
                    if (compressed) {
                        const dt = new DataTransfer();
                        dt.items.add(compressed);
                        input.files = dt.files;
                        setHint(input, `Compressed: ${formatBytes(file.size)} -> ${formatBytes(compressed.size)}`, 'ok');
                    } else {
                        const needsExternal = file.size > PER_IMAGE_HARD_WARN_BYTES;
                        setHint(
                            input,
                            needsExternal
                                ? `Could not reduce enough (${formatBytes(file.size)}). Use TinyPNG in a new tab.`
                                : `Using original image (${formatBytes(file.size)}).`,
                            needsExternal ? 'error' : 'default'
                        );
                    }
                } catch (error) {
                    setHint(input, 'Compression failed. Using original image.', 'error');
                }
                updateSummary();
            };

            const renumber = () => {
                const items = Array.from(list.querySelectorAll('.product-item'));
                items.forEach((item, idx) => {
                    const numberEl = item.querySelector('.product-number');
                    if (numberEl) numberEl.textContent = String(idx + 1);
                    const removeBtn = item.querySelector('.remove-product');
                    if (removeBtn) removeBtn.hidden = items.length === 1;
                });
            };

            addButton.addEventListener('click', () => {
                const index = list.querySelectorAll('.product-item').length;
                const html = template.innerHTML
                    .replaceAll('__INDEX__', String(index))
                    .replaceAll('__NUMBER__', String(index + 1));
                const wrapper = document.createElement('div');
                wrapper.innerHTML = html.trim();
                list.appendChild(wrapper.firstElementChild);
                renumber();
                updateSummary();
            });

            list.addEventListener('click', (event) => {
                const target = event.target;
                if (!(target instanceof HTMLElement)) return;
                const removeButton = target.closest('.remove-product');
                if (!removeButton) return;
                const item = removeButton.closest('.product-item');
                if (!item) return;
                item.remove();
                renumber();
                updateSummary();
            });

            list.addEventListener('change', (event) => {
                const target = event.target;
                if (!(target instanceof HTMLInputElement)) return;
                if (target.matches('input[type="file"][data-product-image="true"]')) {
                    handleImageChange(target);
                }
            });

            renumber();
            updateSummary();
        })();
    </script>
@endsection

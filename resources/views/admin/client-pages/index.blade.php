@extends('layouts.admin')

@section('content')
    <style>
        .page-management-header {
            align-items: center;
            display: flex;
            gap: 16px;
            justify-content: space-between;
            margin-bottom: 18px;
        }

        .page-management-header p {
            margin: 6px 0 0;
            color: var(--muted);
        }

        .page-filter-form {
            align-items: end;
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
        }

        .page-filter-form select,
        .page-filter-form button,
        .page-filter-form a {
            min-height: 40px;
        }

        .page-filter-actions {
            align-items: center;
            display: flex;
            gap: 10px;
        }

        .page-filter-reset {
            color: var(--muted);
            font-size: 13px;
            text-decoration: none;
        }

        .page-filter-reset:hover {
            color: var(--cyan);
        }

        .page-name-cell {
            min-width: 220px;
            position: relative;
        }

        .page-title-link,
        .page-title-text {
            color: #f8fbff;
            display: inline-block;
            font-size: 14px;
            font-weight: 800;
            line-height: 1.3;
            text-decoration: none;
        }

        .page-title-link:hover {
            color: var(--cyan);
            text-decoration: underline;
            text-underline-offset: 4px;
        }

        .page-subtitle {
            color: var(--muted);
            display: block;
            font-size: 12px;
            margin-top: 4px;
        }

        .page-tooltip {
            background: #071225;
            border: 1px solid var(--line);
            border-radius: 10px;
            box-shadow: 0 16px 36px rgba(0, 0, 0, .35);
            color: var(--text);
            display: none;
            font-size: 12px;
            left: 0;
            min-width: 240px;
            padding: 10px 12px;
            position: absolute;
            top: calc(100% + 8px);
            z-index: 20;
        }

        .page-name-cell:hover .page-tooltip {
            display: block;
        }

        .page-tooltip strong {
            color: var(--cyan);
            display: block;
            margin-bottom: 6px;
        }

        .status-badge {
            border-radius: 999px;
            display: inline-block;
            font-size: 12px;
            font-weight: 800;
            padding: 6px 10px;
            text-transform: capitalize;
        }

        .status-badge.active {
            background: rgba(34, 197, 94, .16);
            color: #86efac;
        }

        .status-badge.inactive {
            background: rgba(169, 183, 207, .16);
            color: #cbd5e1;
        }

        .page-actions {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .btn.btn-compact {
            border-radius: 9px;
            font-size: 12px;
            min-height: 34px;
            padding: 8px 11px;
        }

        .btn.btn-outline {
            background: rgba(255,255,255,.04);
            border: 1px solid var(--line);
            color: var(--text);
        }

        .btn.btn-outline:hover {
            border-color: var(--cyan);
            color: var(--cyan);
        }

        .page-modal-backdrop {
            align-items: center;
            background: rgba(2, 6, 23, .72);
            display: none;
            inset: 0;
            justify-content: center;
            padding: 20px;
            position: fixed;
            z-index: 100;
        }

        .page-modal-backdrop.is-open {
            display: flex;
        }

        .page-modal {
            background: #081226;
            border: 1px solid var(--line);
            border-radius: 14px;
            box-shadow: 0 24px 60px rgba(0, 0, 0, .45);
            max-width: 620px;
            padding: 22px;
            width: min(100%, 620px);
        }

        .page-modal-header {
            align-items: start;
            display: flex;
            gap: 16px;
            justify-content: space-between;
            margin-bottom: 18px;
        }

        .page-modal-header h2 {
            margin: 0;
        }

        .page-modal-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
        }

        .page-modal-item {
            background: rgba(255,255,255,.06);
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 12px;
        }

        .page-modal-item span {
            color: var(--muted);
            display: block;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .06em;
            margin-bottom: 6px;
            text-transform: uppercase;
        }

        .page-modal-link {
            color: var(--cyan);
            overflow-wrap: anywhere;
        }

        @media (max-width: 760px) {
            .page-management-header {
                align-items: stretch;
                flex-direction: column;
            }

            .page-filter-actions {
                grid-column: 1 / -1;
            }
        }
    </style>

    <h1>Page Management</h1>
    <div class="page-management-header">
        <p>Manage client pages for Facebook operations, campaign setup, and employee assignments.</p>
        <a class="btn" href="/admin/client-pages/create">Add Page</a>
    </div>

    <div class="card">
        <form class="page-filter-form" method="GET" action="/admin/client-pages">
            <label>Client<br>
                <select name="client_id">
                    <option value="">All Clients</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}" {{ request('client_id') == $client->id ? 'selected' : '' }}>{{ $client->company_name }}</option>
                    @endforeach
                </select>
            </label>
            <label>BM<br>
                <select name="business_manager_id">
                    <option value="">All BM</option>
                    @foreach($businessManagers as $bm)
                        <option value="{{ $bm->id }}" {{ request('business_manager_id') == $bm->id ? 'selected' : '' }}>{{ $bm->bm_name }}</option>
                    @endforeach
                </select>
            </label>
            <label>Ad Account<br>
                <select name="ad_account_id">
                    <option value="">All Ad Accounts</option>
                    @foreach($adAccounts as $account)
                        <option value="{{ $account->id }}" {{ request('ad_account_id') == $account->id ? 'selected' : '' }}>{{ $account->ad_account_name }}</option>
                    @endforeach
                </select>
            </label>
            <label>Platform<br>
                <select name="platform">
                    <option value="">All Platforms</option>
                    @foreach($platforms as $platform)
                        <option value="{{ $platform }}" {{ request('platform') === $platform ? 'selected' : '' }}>{{ $platform }}</option>
                    @endforeach
                </select>
            </label>
            <label>Status<br>
                <select name="status">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </label>
            <div class="page-filter-actions">
                <button class="btn" type="submit">Filter</button>
                <a class="page-filter-reset" href="/admin/client-pages">Reset</a>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <tr>
                    <th>Client</th>
                    <th>Page Name</th>
                    <th>BM</th>
                    <th>Ad Account</th>
                    <th>Platform</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
                @forelse($pages as $page)
                    <tr>
                        <td>{{ $page->client?->company_name ?: '-' }}</td>
                        <td class="page-name-cell">
                            @if($page->page_url)
                                <a class="page-title-link" href="{{ $page->page_url }}" target="_blank" rel="noopener">{{ $page->page_name }}</a>
                            @else
                                <span class="page-title-text">{{ $page->page_name }}</span>
                            @endif
                            <span class="page-subtitle">{{ $page->platform }} Page</span>
                            <div class="page-tooltip">
                                <strong>{{ $page->page_name }}</strong>
                                Client: {{ $page->client?->company_name ?: '-' }}<br>
                                BM: {{ $page->businessManager?->bm_name ?: '-' }}<br>
                                Ad Account: {{ $page->adAccount?->ad_account_name ?: '-' }}<br>
                                Status: {{ ucfirst($page->status) }}
                            </div>
                        </td>
                        <td>{{ $page->businessManager?->bm_name ?: '-' }}</td>
                        <td>{{ $page->adAccount?->ad_account_name ?: '-' }}</td>
                        <td>{{ $page->platform }}</td>
                        <td><span class="status-badge {{ $page->status }}">{{ ucfirst($page->status) }}</span></td>
                        <td>
                            <div class="page-actions">
                            <button
                                class="btn btn-outline btn-compact js-page-view"
                                type="button"
                                data-page-name="{{ $page->page_name }}"
                                data-client="{{ $page->client?->company_name ?: '-' }}"
                                data-bm="{{ $page->businessManager?->bm_name ?: '-' }}"
                                data-ad-account="{{ $page->adAccount?->ad_account_name ?: '-' }}"
                                data-platform="{{ $page->platform }}"
                                data-status="{{ ucfirst($page->status) }}"
                                data-url="{{ $page->page_url ?: '' }}"
                            >View</button>
                            <a class="btn btn-outline btn-compact" href="/admin/client-pages/{{ $page->id }}/edit">Edit</a>
                            <form method="POST" action="/admin/client-pages/{{ $page->id }}/delete" style="display:inline;">
                                @csrf
                                <button class="btn btn-danger btn-compact" type="submit" onclick="return confirm('Delete this client page?');">Delete</button>
                            </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7">No client pages found.</td></tr>
                @endforelse
            </table>
        </div>
    </div>

    <div class="page-modal-backdrop" id="page-detail-modal" aria-hidden="true">
        <div class="page-modal" role="dialog" aria-modal="true" aria-labelledby="page-modal-title">
            <div class="page-modal-header">
                <div>
                    <h2 id="page-modal-title">Page Details</h2>
                    <p style="color:var(--muted);margin:6px 0 0;">Quick view for Facebook page operations.</p>
                </div>
                <button class="btn btn-outline btn-compact" type="button" data-page-modal-close>Close</button>
            </div>
            <div class="page-modal-grid">
                <div class="page-modal-item"><span>Page Name</span><strong data-page-modal="name">-</strong></div>
                <div class="page-modal-item"><span>Client</span><strong data-page-modal="client">-</strong></div>
                <div class="page-modal-item"><span>BM</span><strong data-page-modal="bm">-</strong></div>
                <div class="page-modal-item"><span>Ad Account</span><strong data-page-modal="adAccount">-</strong></div>
                <div class="page-modal-item"><span>Platform</span><strong data-page-modal="platform">-</strong></div>
                <div class="page-modal-item"><span>Status</span><strong data-page-modal="status">-</strong></div>
                <div class="page-modal-item" style="grid-column:1 / -1;"><span>Page URL</span><strong data-page-modal="url">-</strong></div>
            </div>
        </div>
    </div>

    <script>
        const pageModal = document.getElementById('page-detail-modal');
        const pageModalFields = {
            name: document.querySelector('[data-page-modal="name"]'),
            client: document.querySelector('[data-page-modal="client"]'),
            bm: document.querySelector('[data-page-modal="bm"]'),
            adAccount: document.querySelector('[data-page-modal="adAccount"]'),
            platform: document.querySelector('[data-page-modal="platform"]'),
            status: document.querySelector('[data-page-modal="status"]'),
            url: document.querySelector('[data-page-modal="url"]'),
        };

        function closePageModal() {
            pageModal?.classList.remove('is-open');
            pageModal?.setAttribute('aria-hidden', 'true');
        }

        document.querySelectorAll('.js-page-view').forEach((button) => {
            button.addEventListener('click', () => {
                const data = button.dataset;
                pageModalFields.name.textContent = data.pageName || '-';
                pageModalFields.client.textContent = data.client || '-';
                pageModalFields.bm.textContent = data.bm || '-';
                pageModalFields.adAccount.textContent = data.adAccount || '-';
                pageModalFields.platform.textContent = data.platform || '-';
                pageModalFields.status.textContent = data.status || '-';
                pageModalFields.url.textContent = '';
                if (data.url) {
                    const urlLink = document.createElement('a');
                    urlLink.className = 'page-modal-link';
                    urlLink.href = data.url;
                    urlLink.target = '_blank';
                    urlLink.rel = 'noopener';
                    urlLink.textContent = data.url;
                    pageModalFields.url.appendChild(urlLink);
                } else {
                    pageModalFields.url.textContent = '-';
                }
                pageModal?.classList.add('is-open');
                pageModal?.setAttribute('aria-hidden', 'false');
            });
        });

        document.querySelectorAll('[data-page-modal-close]').forEach((button) => button.addEventListener('click', closePageModal));
        pageModal?.addEventListener('click', (event) => {
            if (event.target === pageModal) closePageModal();
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') closePageModal();
        });
    </script>
@endsection

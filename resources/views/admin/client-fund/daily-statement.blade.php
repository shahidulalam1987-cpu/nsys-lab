@extends('layouts.admin')

@section('content')
    <style>
        .statement-header {
            align-items: flex-start;
            display: flex;
            gap: 14px;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .statement-grid {
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .statement-form-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .statement-workflow {
            display: grid;
            gap: 8px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            margin-bottom: 12px;
        }

        .statement-step {
            background: rgba(59, 130, 246, 0.08);
            border: 1px solid rgba(96, 165, 250, 0.22);
            border-radius: 8px;
            padding: 10px 12px;
        }

        .statement-step strong {
            color: #e5edff;
            display: block;
            font-size: 13px;
            line-height: 1.3;
            margin-bottom: 3px;
        }

        .statement-step .statement-muted {
            display: block;
            font-size: 11px;
            line-height: 1.35;
        }

        .statement-form-grid label {
            display: grid;
            gap: 7px;
            font-weight: 800;
        }

        .statement-form-grid input,
        .statement-form-grid select,
        .statement-message {
            width: 100%;
        }

        .statement-message {
            min-height: 440px;
            white-space: pre-wrap;
        }

        .statement-muted {
            color: var(--muted);
            font-size: 12px;
            margin-top: 4px;
        }

        .statement-positive {
            color: #86efac;
        }

        .statement-negative {
            color: #fca5a5;
        }

        .statement-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 14px;
        }

        @media (max-width: 980px) {
            .statement-grid,
            .statement-form-grid,
            .statement-workflow {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 680px) {
            .statement-header {
                display: block;
            }

            .statement-grid,
            .statement-form-grid,
            .statement-workflow {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="statement-header">
        <div>
            <h1>Client Daily Statement</h1>
            <p>Calculate daily ads spend, client credit, due, advance, and WhatsApp-ready closing message.</p>
        </div>
        <div class="statement-actions" style="margin-top:0;">
            <a class="btn" href="/admin/client-fund">Client Funds</a>
            <a class="btn" href="/admin/salary-payments/create">Receive Payment</a>
        </div>
    </div>

    @if ($errors->any())
        <div class="card" style="border-color:#ef4444;color:#fecaca;">
            <h3>Please fix the following:</h3>
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div class="card">
        <h2>Daily Closing Inputs</h2>
        <div class="statement-workflow">
            <div class="statement-step">
                <strong>1. Select client and campaign</strong>
                <span class="statement-muted">Page, saved rate, and last spend snapshot help fill the form.</span>
            </div>
            <div class="statement-step">
                <strong>2. Paste current lifetime spend</strong>
                <span class="statement-muted">System calculates today's spend from current total minus previous total.</span>
            </div>
            <div class="statement-step">
                <strong>3. Preview and copy</strong>
                <span class="statement-muted">Copy the WhatsApp-ready due or advance message for the client.</span>
            </div>
        </div>
        <form method="GET" action="/admin/client-fund/daily-statement" class="statement-form-grid">
            <label>
                Client
                <select name="client_id" id="daily-client" required>
                    <option value="">Select Client</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}" data-rate="{{ $client->client_rate }}" @selected((string) ($filters['client_id'] ?? '') === (string) $client->id)>{{ $client->company_name }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                Page
                <select id="daily-page">
                    <option value="">All Pages</option>
                    @foreach($pages as $page)
                        <option value="{{ $page->id }}" data-client-id="{{ $page->client_id }}">{{ $page->page_name }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                Campaign
                <select name="campaign_id" id="daily-campaign" required>
                    <option value="">Select Campaign</option>
                    @foreach($campaigns as $campaign)
                        @php($snapshot = $latestSnapshots->get($campaign->id))
                        <option value="{{ $campaign->id }}"
                            data-client-id="{{ $campaign->client_id }}"
                            data-page-id="{{ $campaign->client_page_id }}"
                            data-page="{{ $campaign->page?->page_name }}"
                            data-rate="{{ $campaign->client?->client_rate }}"
                            data-previous-spend="{{ $snapshot?->spend_usd }}"
                            data-previous-date="{{ $snapshot?->snapshot_date?->format('Y-m-d') }}"
                            @selected((string) ($filters['campaign_id'] ?? '') === (string) $campaign->id)>
                            {{ $campaign->campaign_name }} #{{ $campaign->campaign_id }}
                        </option>
                    @endforeach
                </select>
            </label>
            <label>
                Date
                <input type="date" name="statement_date" value="{{ $filters['statement_date'] ?? now('Asia/Dhaka')->toDateString() }}" required>
            </label>
            <label>
                Current Lifetime Spend USD
                <input type="number" step="0.01" min="0" name="current_total_spend_usd" value="{{ $filters['current_total_spend_usd'] ?? '' }}" required>
                <span class="statement-muted">Paste the latest total spend from Ads Manager, not today's spend.</span>
            </label>
            <label>
                Previous Lifetime Spend USD
                <input type="number" step="0.01" min="0" name="previous_total_spend_usd" value="{{ $filters['previous_total_spend_usd'] ?? '' }}">
                <span class="statement-muted" id="daily-snapshot-hint">Auto-filled from latest saved snapshot when available.</span>
            </label>
            <label>
                Orders
                <input type="number" min="0" name="orders" value="{{ $filters['orders'] ?? 0 }}" required>
            </label>
            <label>
                Rate BDT/USD
                <input type="number" step="0.01" min="0" name="rate_bdt" id="daily-rate" value="{{ $filters['rate_bdt'] ?? '' }}" placeholder="Auto from client">
            </label>
            <div style="align-self:end;">
                <button class="btn" type="submit">Preview WhatsApp Statement</button>
            </div>
        </form>
    </div>

    @if($statement)
        <div class="statement-grid">
            <div class="stat-card">
                <p>Today Spend USD</p>
                <h2>${{ number_format($statement['today_spend_usd'], 2) }}</h2>
                <p>{{ number_format($statement['previous_total_spend_usd'], 2) }} -> {{ number_format($statement['current_total_spend_usd'], 2) }}</p>
            </div>
            <div class="stat-card">
                <p>Today Spend BDT</p>
                <h2>BDT {{ number_format($statement['today_spend_bdt'], 2) }}</h2>
                <p>Rate {{ number_format($statement['rate_bdt'], 2) }}</p>
            </div>
            <div class="stat-card">
                <p>Credit Today</p>
                <h2 class="statement-positive">BDT {{ number_format($statement['credit_today'], 2) }}</h2>
                <p>Approved Ads Fund credits on this date</p>
            </div>
            <div class="stat-card">
                <p>Final {{ $statement['final_due'] > 0 ? 'Due' : 'Advance' }}</p>
                <h2 class="{{ $statement['final_due'] > 0 ? 'statement-negative' : 'statement-positive' }}">
                    BDT {{ number_format($statement['final_due'] > 0 ? $statement['final_due'] : $statement['final_advance'], 2) }}
                </h2>
                <p>{{ $statement['final_due'] > 0 ? 'Client payable' : 'Client advance' }}</p>
            </div>
        </div>

        <div class="card">
            <h2>Closing Breakdown</h2>
            <div class="statement-grid">
                <div>
                    <p><strong>Previous Due:</strong> BDT {{ number_format($statement['opening_due'], 2) }}</p>
                    <p><strong>Previous Advance:</strong> BDT {{ number_format($statement['opening_advance'], 2) }}</p>
                </div>
                <div>
                    <p><strong>Remaining Due After Credit:</strong> BDT {{ number_format($statement['remaining_previous_due'], 2) }}</p>
                    <p><strong>Remaining Advance After Credit:</strong> BDT {{ number_format($statement['remaining_previous_advance'], 2) }}</p>
                </div>
                <div>
                    <p><strong>Orders:</strong> {{ number_format($statement['orders']) }}</p>
                    <p><strong>Campaign:</strong> {{ $statement['campaign']->campaign_name }}</p>
                </div>
                <div>
                    <p><strong>Page:</strong> {{ $statement['campaign']->page?->page_name ?: '-' }}</p>
                    <p><strong>Snapshot Source:</strong> {{ $statement['previous_snapshot'] ? 'Previous saved snapshot' : 'Manual/previous reports fallback' }}</p>
                </div>
            </div>
        </div>

        <div class="card">
            <h2>WhatsApp Message</h2>
            <textarea class="statement-message" id="whatsapp-message" readonly>{{ $statement['whatsapp_message'] }}</textarea>
            <div class="statement-actions">
                <button class="btn" type="button" id="copy-whatsapp-message">Copy WhatsApp Message</button>
                <form method="POST" action="/admin/client-fund/daily-statement" style="display:inline;">
                    @csrf
                    @foreach(['client_id', 'campaign_id', 'statement_date', 'previous_total_spend_usd', 'current_total_spend_usd', 'orders', 'rate_bdt'] as $field)
                        <input type="hidden" name="{{ $field }}" value="{{ $filters[$field] ?? '' }}">
                    @endforeach
                    <label style="display:inline-flex;align-items:center;gap:6px;color:var(--muted);margin-right:8px;">
                        <input type="checkbox" name="update_existing" value="1">
                        Update existing daily performance
                    </label>
                    <button class="btn" type="submit">Save Daily Performance</button>
                </form>
            </div>
            <p class="statement-muted">Save creates/updates the spend snapshot and Daily Performance. Client payments should still be recorded from Receive Payment.</p>
        </div>
    @endif

    <script>
        const clientSelect = document.getElementById('daily-client');
        const pageSelect = document.getElementById('daily-page');
        const campaignSelect = document.getElementById('daily-campaign');
        const rateInput = document.getElementById('daily-rate');
        const previousSpendInput = document.querySelector('input[name="previous_total_spend_usd"]');
        const snapshotHint = document.getElementById('daily-snapshot-hint');

        function syncCampaignDefaults(force = false) {
            const option = campaignSelect?.selectedOptions[0];

            if (!option || !option.value) {
                if (snapshotHint) {
                    snapshotHint.textContent = 'Auto-filled from latest saved snapshot when available.';
                }

                return;
            }

            if (option.dataset.rate && (force || !rateInput.value)) {
                rateInput.value = option.dataset.rate;
            }

            if (option.dataset.previousSpend && (force || !previousSpendInput.value)) {
                previousSpendInput.value = option.dataset.previousSpend;
            }

            if (snapshotHint) {
                snapshotHint.textContent = option.dataset.previousSpend
                    ? `Last saved snapshot: ${option.dataset.previousDate || 'date unknown'} | USD ${Number(option.dataset.previousSpend).toFixed(2)}`
                    : 'No previous snapshot found. Enter previous lifetime spend manually if needed.';
            }
        }

        function syncDailyStatementOptions() {
            const clientId = clientSelect?.value || '';
            const pageId = pageSelect?.value || '';

            pageSelect?.querySelectorAll('option[data-client-id]').forEach((option) => {
                option.hidden = clientId && option.dataset.clientId !== clientId;
            });

            if (pageSelect?.selectedOptions[0]?.hidden) {
                pageSelect.value = '';
            }

            campaignSelect?.querySelectorAll('option[data-client-id]').forEach((option) => {
                const clientMatches = !clientId || option.dataset.clientId === clientId;
                const pageMatches = !pageId || option.dataset.pageId === pageId;
                option.hidden = !(clientMatches && pageMatches);
            });

            if (campaignSelect?.selectedOptions[0]?.hidden) {
                campaignSelect.value = '';
            }

            if (clientSelect?.selectedOptions[0]?.dataset.rate && !rateInput.value) {
                rateInput.value = clientSelect.selectedOptions[0].dataset.rate;
            }

            syncCampaignDefaults(false);
        }

        clientSelect?.addEventListener('change', () => {
            rateInput.value = clientSelect.selectedOptions[0]?.dataset.rate || '';
            syncDailyStatementOptions();
        });
        pageSelect?.addEventListener('change', syncDailyStatementOptions);
        campaignSelect?.addEventListener('change', () => syncCampaignDefaults(true));
        syncDailyStatementOptions();

        document.getElementById('copy-whatsapp-message')?.addEventListener('click', async () => {
            const message = document.getElementById('whatsapp-message')?.value || '';
            await navigator.clipboard.writeText(message);
        });
    </script>
@endsection

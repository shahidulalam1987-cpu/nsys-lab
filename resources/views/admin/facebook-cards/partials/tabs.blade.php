<style>
    .card-management-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin: 14px 0 18px;
    }
    .card-management-tabs a {
        background: rgba(255,255,255,.08);
        border: 1px solid var(--line);
        border-radius: 999px;
        color: var(--muted);
        font-size: 13px;
        font-weight: 700;
        padding: 8px 12px;
        text-decoration: none;
    }
    .card-management-tabs a.active {
        background: linear-gradient(90deg, var(--blue), var(--cyan));
        color: #fff;
    }
</style>

<div class="card-management-tabs">
    <a class="{{ request()->is('admin/facebook-cards') ? 'active' : '' }}" href="/admin/facebook-cards#overview">Overview</a>
    <a class="{{ request()->is('admin/facebook-cards*') ? 'active' : '' }}" href="/admin/facebook-cards#cards">Cards</a>
    <a class="{{ request()->is('admin/facebook-financial/card-loads*') ? 'active' : '' }}" href="/admin/facebook-financial/card-loads">Loads</a>
    <a class="{{ request()->is('admin/facebook-financial/card-transactions*') ? 'active' : '' }}" href="/admin/facebook-financial/card-transactions">Transactions</a>
    <a class="{{ request()->is('admin/facebook-financial/binance-purchases*') ? 'active' : '' }}" href="/admin/facebook-financial/binance-purchases">Binance Purchases</a>
    <a class="{{ request()->is('admin/payment-providers*') ? 'active' : '' }}" href="/admin/payment-providers">Providers</a>
    <a class="{{ request()->is('admin/provider-transactions*') ? 'active' : '' }}" href="/admin/provider-transactions">Provider Transactions</a>
    <a class="{{ request()->is('admin/provider-fees*') ? 'active' : '' }}" href="/admin/provider-fees">Provider Fees</a>
    <a href="/admin/facebook-cards#statement">Statement</a>
</div>

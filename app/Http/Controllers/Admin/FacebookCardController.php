<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdAccount;
use App\Models\FacebookCard;
use App\Services\FinanceLedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FacebookCardController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(['provider', 'status']);
        $cards = FacebookCard::with('adAccount')
            ->withCount(['loads', 'transactions'])
            ->when($filters['provider'] ?? null, function ($query, $provider) {
                $provider === 'Tevau'
                    ? $query->whereIn('provider', ['Tevau', 'Tavao'])
                    : $query->where('provider', $provider);
            })
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->get();

        return view('admin.facebook-cards.index', [
            'cards' => $cards,
            'filters' => $filters,
            'providers' => ['RedotPay' => 'RedotPay', 'Tevau' => 'Tevau', 'Other' => 'Other'],
            'summary' => [
                'total_balance' => (float) $cards->sum('current_balance'),
                'redotpay_balance' => (float) $cards->filter(fn (FacebookCard $card) => strcasecmp((string) $card->provider, 'RedotPay') === 0)->sum('current_balance'),
                'tavao_balance' => (float) $cards->filter(fn (FacebookCard $card) => in_array(strtolower((string) $card->provider), ['tevau', 'tavao'], true))->sum('current_balance'),
                'low_balance' => $cards->filter(fn (FacebookCard $card) => $card->effectiveStatus() === 'low_balance')->count(),
                'disabled' => $cards->where('status', 'disabled')->count(),
                'expired' => $cards->where('status', 'expired')->count(),
            ],
        ]);
    }

    public function create()
    {
        return view('admin.facebook-cards.create', $this->formData(new FacebookCard([
            'currency' => FacebookCard::CURRENCY,
            'status' => 'active',
        ])));
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $openingBalance = round((float) $data['current_balance'], 2);
        $data['current_balance'] = 0;
        $card = DB::transaction(function () use ($data, $openingBalance, $request) {
            $card = FacebookCard::create($data + ['currency' => FacebookCard::CURRENCY]);
            if ($openingBalance > 0) {
                app(FinanceLedgerService::class)->credit($card, $openingBalance, $this->cardContext($card, $request, 'opening_balance', 'Card opening balance.'));
            }

            return $card;
        });

        return redirect('/admin/facebook-cards/' . $card->id)->with('success', 'Card saved successfully.');
    }

    public function show(FacebookCard $card)
    {
        return view('admin.facebook-cards.show', [
            'card' => $card->load('adAccount'),
            'recentLoads' => $card->loads()->with('binancePurchase')->latest('load_date')->latest()->take(5)->get(),
            'recentTransactions' => $card->transactions()->with(['client', 'adAccount', 'campaign'])->latest('transaction_date')->latest()->take(5)->get(),
        ]);
    }

    public function edit(FacebookCard $card)
    {
        return view('admin.facebook-cards.edit', $this->formData($card));
    }

    public function update(Request $request, FacebookCard $card)
    {
        $data = $this->validatedData($request);
        $newBalance = round((float) $data['current_balance'], 2);
        $previousBalance = round((float) $card->current_balance, 2);
        if ($newBalance !== $previousBalance) {
            $request->validate(['adjustment_reason' => ['required', 'string', 'max:1000']]);
        }

        DB::transaction(function () use ($card, $data, $newBalance, $previousBalance, $request) {
            unset($data['current_balance']);
            $card->update($data + ['currency' => FacebookCard::CURRENCY]);
            $this->adjustCardBalance($card, $previousBalance, $newBalance, $request);
        });

        return redirect('/admin/facebook-cards/' . $card->id)->with('success', 'Card updated successfully.');
    }

    public function updateBalance(Request $request, FacebookCard $card)
    {
        $data = $request->validate([
            'current_balance' => ['required', 'numeric'],
            'adjustment_reason' => ['required', 'string', 'max:1000'],
        ]);
        $this->adjustCardBalance($card, (float) $card->current_balance, (float) $data['current_balance'], $request);

        return redirect('/admin/facebook-cards')->with('success', 'Card balance updated successfully.');
    }

    private function formData(FacebookCard $card): array
    {
        return [
            'card' => $card,
            'adAccounts' => AdAccount::orderBy('ad_account_name')->get(),
            'statuses' => FacebookCard::STATUSES,
        ];
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'card_name' => ['required', 'string', 'max:255'],
            'card_type' => ['nullable', 'string', 'max:255'],
            'card_last_four' => ['nullable', 'digits:4'],
            'provider' => ['nullable', 'string', 'max:255'],
            'current_balance' => ['required', 'numeric'],
            'ad_account_id' => ['nullable', 'exists:ad_accounts,id'],
            'status' => ['required', Rule::in(array_keys(FacebookCard::STATUSES))],
            'notes' => ['nullable', 'string'],
        ]);
    }

    private function adjustCardBalance(FacebookCard $card, float $previousBalance, float $newBalance, Request $request): void
    {
        $previousBalance = round($previousBalance, 2);
        $newBalance = round($newBalance, 2);
        if ($newBalance === $previousBalance) {
            return;
        }

        $context = $this->cardContext($card, $request, 'manual_adjustment', $request->string('adjustment_reason')->toString());
        $newBalance > $previousBalance
            ? app(FinanceLedgerService::class)->credit($card, $newBalance - $previousBalance, $context)
            : app(FinanceLedgerService::class)->debit($card, $previousBalance - $newBalance, $context + ['allow_negative' => true]);
    }

    private function cardContext(FacebookCard $card, Request $request, string $type, string $description): array
    {
        return [
            'transaction_type' => $type,
            'currency' => 'USD',
            'reference_type' => FacebookCard::class,
            'reference_id' => $card->id,
            'description' => $description,
            'transaction_reference' => 'facebook-card:' . $card->id,
            'created_by' => $request->user()?->id,
        ];
    }
}

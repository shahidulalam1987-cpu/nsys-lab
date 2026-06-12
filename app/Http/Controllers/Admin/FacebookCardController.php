<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdAccount;
use App\Models\FacebookCard;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FacebookCardController extends Controller
{
    public function index()
    {
        $cards = FacebookCard::with('adAccount')->latest()->get();

        return view('admin.facebook-cards.index', [
            'cards' => $cards,
            'summary' => [
                'total_balance' => (float) $cards->sum('current_balance'),
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
        $card = FacebookCard::create($this->validatedData($request) + [
            'currency' => FacebookCard::CURRENCY,
        ]);

        return redirect('/admin/facebook-cards/' . $card->id)->with('success', 'Card saved successfully.');
    }

    public function show(FacebookCard $card)
    {
        return view('admin.facebook-cards.show', [
            'card' => $card->load('adAccount'),
        ]);
    }

    public function edit(FacebookCard $card)
    {
        return view('admin.facebook-cards.edit', $this->formData($card));
    }

    public function update(Request $request, FacebookCard $card)
    {
        $card->update($this->validatedData($request) + [
            'currency' => FacebookCard::CURRENCY,
        ]);

        return redirect('/admin/facebook-cards/' . $card->id)->with('success', 'Card updated successfully.');
    }

    public function updateBalance(Request $request, FacebookCard $card)
    {
        $data = $request->validate([
            'current_balance' => ['required', 'numeric'],
        ]);

        $card->update($data + [
            'currency' => FacebookCard::CURRENCY,
        ]);

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
}

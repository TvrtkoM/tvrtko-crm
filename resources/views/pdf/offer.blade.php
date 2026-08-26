<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $offer->offer_number }}</title>
    <style>
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 12px;
            color: #1f2933;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table {
            margin-bottom: 24px;
        }

        .header-table .brand {
            font-size: 20px;
            font-weight: bold;
        }

        .header-table .meta {
            text-align: right;
        }

        .meta table {
            width: auto;
            margin-left: auto;
        }

        .meta td {
            padding: 2px 0;
        }

        .meta td.label {
            color: #616e7c;
            padding-right: 12px;
        }

        .bill-to {
            margin-bottom: 24px;
        }

        .bill-to td {
            width: 50%;
            vertical-align: top;
        }

        .bill-to h3 {
            margin: 0 0 6px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #616e7c;
        }

        .items-table {
            margin-bottom: 16px;
        }

        .items-table th {
            text-align: left;
            border-bottom: 2px solid #1f2933;
            padding: 6px 4px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .items-table td {
            padding: 6px 4px;
            border-bottom: 1px solid #e4e7eb;
        }

        .items-table th.numeric,
        .items-table td.numeric {
            text-align: right;
        }

        .totals-table {
            width: auto;
            margin-left: auto;
            margin-bottom: 24px;
        }

        .totals-table td {
            padding: 3px 0;
        }

        .totals-table td.label {
            color: #616e7c;
            padding-right: 24px;
        }

        .totals-table td.value {
            text-align: right;
        }

        .totals-table tr.total td {
            border-top: 2px solid #1f2933;
            padding-top: 6px;
            font-weight: bold;
            font-size: 14px;
        }

        .terms h3 {
            margin: 0 0 6px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #616e7c;
        }

        .terms p {
            white-space: pre-line;
        }
    </style>
</head>
<body>
    @php
        /**
         * Format a money amount as EUR, Croatian style (e.g. `1.234,56 €`),
         * matching the UI's `formatCurrency` helper (resources/js/lib/format.ts).
         */
        $money = fn (float $amount): string => number_format($amount, 2, ',', '.').' €';
    @endphp

    <table class="header-table">
        <tr>
            <td class="brand">Tvrtko CRM</td>
            <td class="meta">
                <table>
                    <tr>
                        <td class="label">Offer #</td>
                        <td>{{ $offer->offer_number }}</td>
                    </tr>
                    <tr>
                        <td class="label">Issue date</td>
                        <td>{{ $offer->issue_date?->format('d.m.Y.') }}</td>
                    </tr>
                    <tr>
                        <td class="label">Valid until</td>
                        <td>{{ $offer->valid_until?->format('d.m.Y.') ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Status</td>
                        <td>{{ $offer->status->label() }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="bill-to">
        <tr>
            <td>
                <h3>Bill to</h3>
                @if ($offer->deal?->company)
                    <div>{{ $offer->deal->company->name }}</div>
                    @if ($offer->deal->company->address)
                        <div>{{ $offer->deal->company->address }}</div>
                    @endif
                    <div>
                        {{ collect([$offer->deal->company->city, $offer->deal->company->country])->filter()->implode(', ') }}
                    </div>
                    @if ($offer->deal->company->email)
                        <div>{{ $offer->deal->company->email }}</div>
                    @endif
                @else
                    <div>—</div>
                @endif
            </td>
            <td>
                <h3>Contact</h3>
                @if ($offer->deal?->contact)
                    <div>{{ trim($offer->deal->contact->first_name.' '.$offer->deal->contact->last_name) }}</div>
                    @if ($offer->deal->contact->email)
                        <div>{{ $offer->deal->contact->email }}</div>
                    @endif
                    @if ($offer->deal->contact->phone)
                        <div>{{ $offer->deal->contact->phone }}</div>
                    @endif
                @else
                    <div>—</div>
                @endif
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th>Description</th>
                <th class="numeric">Quantity</th>
                <th class="numeric">Unit price</th>
                <th class="numeric">Line total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($offer->items->sortBy('position') as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td class="numeric">{{ number_format((float) $item->quantity, 2, ',', '.') }}</td>
                    <td class="numeric">{{ $money((float) $item->unit_price) }}</td>
                    <td class="numeric">{{ $money($item->line_total) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td class="label">Subtotal</td>
            <td class="value">{{ $money($offer->subtotal) }}</td>
        </tr>
        <tr>
            <td class="label">Tax ({{ number_format((float) $offer->tax_rate, 2) }} %)</td>
            <td class="value">{{ $money($offer->tax_amount) }}</td>
        </tr>
        <tr class="total">
            <td class="label">Total</td>
            <td class="value">{{ $money($offer->total) }}</td>
        </tr>
    </table>

    @if ($offer->notes)
        <div class="terms">
            <h3>Terms &amp; notes</h3>
            <p>{{ $offer->notes }}</p>
        </div>
    @endif
</body>
</html>

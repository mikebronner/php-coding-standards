<div
    x-data="{
        showFeesListing: false,
        showInterestsListing: false,
        showLoanDetails: {{ $showDetailsByDefault ? "true" : "false" }},
    }"
    class="flex flex-col"
>
    <div
        class="relative flex flex-col"
    >

        @if ($loan->status === "Funded")
            @if (! $showDetailsByDefault)
                <div
                    class="pt-5 pb-2 pl-4 pr-6 w-full flex flex-row items-center justify-between cursor-pointer sm:pl-6 lg:pl-8 xl:pl-6"
                    x-on:click="showLoanDetails = ! showLoanDetails"
                >
            @else
                <div
                    class="pt-5 pb-2 pl-4 pr-6 w-full flex flex-row items-center justify-between sm:pl-6 lg:pl-8 xl:pl-6"
                >
            @endif
        @else
            <div
                class="pt-5 pb-2 pl-4 pr-6 w-full flex flex-row items-center justify-between sm:pl-6 lg:pl-8 xl:pl-6"
            >
        @endif

            <div
                class="w-1/3 flex flex-col items-start justify-center space-x-4"
            >
                <div
                    class="flex items-center space-x-2"
                >
                    <x-status-indicator
                        class="block"
                        :status="$loan->status"
                    />
                    <p
                        class="ml-2 font-light text-gray-700"
                    >
                        Loan #{{ $loan->getKey() }} is
                        <span
                            class="font-semibold"
                        >
                            {{ ucwords($loan->status) }}
                        </span>
                        as of {{ $loan->status_date->format("d M Y") }}
                    </p>
                </div>
            </div>
            <div>

                @if ($loan->daysLate > 0)
                    <div
                        class="p-4 rounded-md bg-red-50"
                    >
                        <div
                            class="flex"
                        >
                            <div
                                class="flex-shrink-0"
                            >
                                <x-icons.solid.exclamation-triangle
                                    class="w-5 h-5 flex-shrink-0 text-red-600"
                                />
                            </div>
                            <div class="ml-3">
                                <div class="text-sm text-red-700">
                                    <p>
                                        Your payment is <strong>{{ $loan->daysLate }} days late</strong>. Please make a payment as soon as possible.

                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

            </div>

            <x-money
                :amount="$loan->payoff_amount"
                :class="'w-1/3 text-right text-4xl font-extralight' . ($loan->status === 'Withdrawn' ? ' line-through text-gray-500' : '')"
                decimals="2"
            />
        </div>

        <div
            @if ($loan->status === "Funded")
                x-on:click="showLoanDetails = ! showLoanDetails"
                class="px-6 pb-2 flex items-center space-x-8 cursor-pointer"
            @else
                class="px-6 pb-2 flex items-center space-x-8"
            @endif
        >
            @if ($loan->status === "Pending")
                @if ($loan->contracts->last() && $hs_sig)
                    <a
                        class="font-medium text-green-600 cursor-pointer hover:text-green-800"
                        x-on:click="
                            fetch('/signatures/{{ $hs_sig->id }}/signurl')
                                .then(res => res.json())
                                .then(data => {
                                    if (data.sign_url) {
                                        const client = new HelloSign;
                                        client.on('sign', function() {
                                            fetch('/signatures/{{ $hs_sig->id }}/signed');
                                        });
                                        client.open(data.sign_url, {
                                            clientId: '{{ env('HELLOSIGN_CLIENT_ID') }}'
                                        });
                                    }
                                });"
                    >
                        Sign Contract & Finalize Loan
                    </a>
                @endif

                <x-form-button
                    wire:click="withdraw"
                    class="text-sm text-gray-500 bg-transparent cursor-pointer hover:text-gray-700"
                    value="Withdraw Loan"
                />
            @endif

            @if ($loan->isAvailableForRefinance())
                {{-- TODO: functionality available to customer to be determined --}}
                <x-form-button
                    type="button"
                    class="text-blue-600 cursor-pointer hover:text-blue-800"
                    value="Refinance"
                />
            @endif

            @if ($loan->status === "Funded" && $loan->currentPrincipleBalance)
                <livewire:payment
                    :allowPaymentToBeScheduled="false"
                    :loan="$loan"
                    :showPaymentMethods="false"
                    button-class="font-medium text-blue-600 bg-transparent hover:text-blue-800"
                    button-text="Make an Additional Payment"
                />
            @endif

            @if ($loan->contracts->last() && $hs_sig && $hs_sig->signed_at)
                <a
                    class="text-sm font-light text-blue-600 hover:text-blue-600"
                    download
                    href="{{ url("/loans/{$loan->id}/contract") }}"
                >
                    Download Contract
                </a>
            @endif

        </div>
        <div
            x-cloak
            x-show="showLoanDetails"
            class="mx-6 pb-8 max-w-full prose"
        >
            <div
                class="mt-4 flex justify-between text-sm font-medium leading-5 text-gray-500"
            >
                <span>Principle</span>
                <span
                    data-tippy-content="Original Principle Amount"
                    class="text-green-600"
                >${{ number_format($loan->original_principle_amount / 100, 2) }}</span>
            </div>
            <div
                class="mt-1 text-sm leading-5 text-gray-900"
            >
                <div
                    class="w-full h-5 relative z-10 overflow-hidden bg-green-100 rounded-full shadow-inner"
                >

                    @if ($loan->payments->sum("principle"))
                        <div
                            style="min-width: {{ $loan->original_principle_amount ? min([$loan->payments->sum("principle") / $loan->original_principle_amount * 100, 100]) : 0 }}%;"
                            class="px-2 h-full relative left-0 items-center inline-block text-xs text-right text-green-100 bg-green-600"
                        >
                            ${{ number_format($loan->payments->sum("principle") / 100, 2) }}
                        </div>
                    @else
                        <div
                            data-tippy-content="Principle Paid"
                            width="{{ $loan->original_principle_amount ? $loan->payments->sum("amount") / $loan->original_principle_amount : 0 }}%"
                            class="px-3 h-full absolute left-0 flex items-center text-xs text-right text-green-600 bg-transparent z-1"
                        >
                            $0
                        </div>
                    @endif

                </div>
            </div>
            <div
                class="mt-8 text-sm font-medium leading-5 text-gray-500"
            >
                <div
                    class="flex justify-between"
                >
                    <span>Total Interest</span>
                    <span
                        data-tippy-content="Total Interest Accrued"
                        class="text-yellow-600"
                    >
                        ${{ number_format($loan->interests->sum("amount") / 100, 2) }}
                    </span>
                </div>
            </div>
            <div
                class="mt-1 text-sm leading-5 text-gray-900"
            >
                <div
                    class="w-full h-5 relative overflow-hidden bg-yellow-100 rounded-full shadow-inner"
                >

                    @if ($loan->payments->sum("interest"))
                        <div
                            data-tippy-content="Total Interest Paid"
                            style="min-width: {{ $loan->interests->sum("amount") ? min([$loan->payments->sum("interest") / $loan->interests->sum("amount") * 100, 100]) : 0 }}%;"
                            class="px-2 h-full relative left-0 items-center inline-block text-xs text-right text-yellow-100 bg-yellow-600"
                        >
                            ${{ number_format($loan->payments->sum("interest") / 100, 2) }}
                        </div>
                    @else
                        <div
                            data-tippy-content="Total Interest Paid"
                            class="px-3 h-full absolute left-0 flex items-center text-xs text-right text-yellow-600 bg-transparent z-1"
                        >
                            $0
                        </div>
                    @endif

                </div>
            </div>

            @foreach ($loan->interests->unique("type") as $interestType)
                <div
                    class="mt-1 text-xs font-medium leading-5 text-gray-500"
                >
                    <div
                        class="flex justify-between"
                    >
                        <span>{{  $interestType->type ?? "General" }}</span>
                        <span class="text-yellow-600">
                            <span
                                data-tippy-content="Total {{  $interestType->type ?? "General" }} Paid"
                            >
                                ${{ number_format($loan->payments->sum("interest") / 100, 2) }}
                            </span>
                            /
                            <span
                                data-tippy-content="Total {{  $interestType->type ?? "General" }} Accrued"
                            >
                                ${{ number_format($loan->interests->where("type", $interestType->type)->sum("amount") / 100, 2) }}
                            </span>
                        </span>
                    </div>
                </div>
                <div
                    class="mt-1 text-sm leading-5 text-gray-900"
                >
                    <div
                        class="w-full h-2 relative overflow-hidden bg-yellow-100 rounded-full shadow-inner"
                    >

                        @if ($loan->payments->sum("interest"))
                            <div
                                style="min-width: {{ $loan->interests->sum("amount") ? min([$loan->payments->sum("interest") / $loan->interests->sum("amount") * 100, 100]) : 0 }}%;"
                                class="h-2 absolute top-0 left-0 text-yellow-100 bg-yellow-400"
                            >
                            </div>
                        @endif

                    </div>
                </div>
            @endforeach

            <div
                class="mt-8 text-sm font-medium leading-5 text-gray-500"
            >
                <div
                    class="flex justify-between"
                >
                    <span>Total Fees</span>
                    <span
                        data-tippy-content="Total Fees Accrued"
                        class="text-blue-600"
                    >${{ number_format($loan->fees->sum("amount") / 100, 2) }}</span>
                </div>
            </div>
            <div
                class="mt-1 text-sm leading-5 text-gray-900"
            >
                <div
                    class="w-full h-5 relative overflow-hidden bg-blue-100 rounded-full shadow-inner"
                >

                    @if ($loan->payments->sum("fees"))
                        <div
                            data-tippy-content="Total Fees Paid"
                            style="min-width: {{ $loan->fees->sum("amount") ? min([$loan->payments->sum("fees") / $loan->fees->sum("amount") * 100, 100]) : 0 }}%;"
                            class="px-2 h-full relative left-0 items-center inline-block text-xs text-right text-blue-100 bg-blue-600"
                        >
                            ${{ number_format($loan->payments->sum("fees") / 100, 2) }}
                        </div>
                    @else
                        <div
                            data-tippy-content="Total Fees Paid"
                            class="px-3 h-full absolute left-0 flex items-center text-xs text-right text-blue-600 bg-transparent z-1"
                        >
                            $0
                        </div>
                    @endif

                </div>
            </div>

            @foreach ($loan->fees->unique("type") as $feeType)
                <div
                    class="mt-1 text-xs font-medium leading-5 text-gray-500"
                >
                    <div
                        class="flex justify-between"
                    >
                        <span>{{  $feeType->type ?? "General" }}</span>
                        <span class="text-blue-600">
                            <span
                                data-tippy-content="Total {{  $feeType->type ?? "General" }} Paid"
                            >
                                ${{ number_format($loan->payments->sum("fees") / 100, 2) }}
                            </span>
                            /
                            <span
                                data-tippy-content="Total {{  $feeType->type ?? "General" }} Accrued"
                            >
                                ${{ number_format($loan->fees->where("type", $feeType->type)->sum("amount") / 100, 2) }}
                            </span>
                        </span>
                    </div>
                </div>
                <div
                    class="mt-1 text-sm leading-5 text-gray-900"
                >
                    <div
                        class="w-full h-2 relative overflow-hidden bg-blue-100 rounded-full shadow-inner"
                    >

                        @if ($loan->payments->sum("fees"))
                            <div
                                style="min-width: {{ $loan->fees->sum("amount") ? min([$loan->payments->sum("fees") / $loan->fees->sum("amount") * 100, 100]) : 0 }}%;"
                                class="h-2 absolute top-0 left-0 text-blue-100 bg-blue-400"
                            >
                            </div>
                        @endif

                    </div>
                </div>
            @endforeach

            <h3>
                Completed Payments
            </h3>

            @if ($loan->payments->isEmpty())
                <p
                    class="mt-0 text-gray-400"
                >
                    No payments completed yet.
                </p>
            @else
                <div class="flex flex-col">
                    <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                        <div class="py-2 min-w-full inline-block align-middle sm:px-6 lg:px-8">
                            <div class="overflow-hidden">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>
                                                Date
                                            </th>
                                            <th>
                                                Payment
                                            </th>
                                            <th>
                                                Principle
                                            </th>
                                            <th>
                                                Interest
                                            </th>
                                            <th>
                                                Fees
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>

                                        @foreach ($loan->payments->sortByDesc("created_at") as $payment)
                                            <tr>
                                                <td>
                                                    {{ $payment->created_at->format("d M Y") }}
                                                </td>
                                                <td>
                                                    ${{ number_format($payment->amount / 100, 2) }}
                                                </td>
                                                <td>
                                                    ${{ number_format($payment->principle / 100, 2) }}
                                                </td>
                                                <td>
                                                    ${{ number_format($payment->interest / 100, 2) }}
                                                </td>
                                                <td>
                                                    ${{ number_format($payment->fees / 100, 2) }}
                                                </td>
                                            </tr>
                                        @endforeach

                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if ($loan->scheduledPayments->isNotEmpty())
                <h3>
                    Scheduled Payments
                </h3>
                <div class="flex flex-col">
                    <div class="overflow-x-auto">
                        <div class="min-w-full inline-block align-middle">
                            <div class="overflow-hidden">
                                <table
                                    class="mt-0"
                                >
                                    <thead>
                                        <tr>
                                            <th>
                                                Date
                                            </th>
                                            <th>
                                                Payment
                                            </th>
                                            <th>
                                                Principle
                                            </th>
                                            <th>
                                                Interest
                                            </th>
                                            <th>
                                                Fees
                                            </th>
                                            <th>
                                                Back Interest
                                            </th>
                                            <th>
                                                Balance
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>

                                        @foreach ($loan->scheduledPayments->sortBy("payment_date") as $scheduledPayment)
                                            <tr>
                                                <td>
                                                    {{ $scheduledPayment->payment_date->format("d M Y") }}
                                                </td>
                                                <td>
                                                    ${{ number_format($scheduledPayment->payment / 100, 2) }}
                                                </td>
                                                <td>
                                                    ${{ number_format($scheduledPayment->principle / 100, 2) }}
                                                </td>
                                                <td>
                                                    ${{ number_format($scheduledPayment->interest / 100, 2) }}
                                                </td>
                                                <td>
                                                    ${{ number_format($scheduledPayment->fees / 100, 2) }}
                                                </td>
                                                <td>
                                                    ${{ number_format($scheduledPayment->current_interest / 100, 2) }}
                                                </td>
                                                <td>
                                                    ${{ number_format($scheduledPayment->balance / 100, 2) }}
                                                </td>
                                            </tr>
                                        @endforeach

                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if ($loan->interests->isEmpty())
                <h3>
                    Interests
                </h3>
                <p
                    class="mt-0 text-gray-400"
                >
                    No interest has been applied.
                </p>
            @else
                <h3
                    x-on:click="showInterestsListing = ! showInterestsListing"
                >
                    Interest Charges
                    <span
                        x-show="! showInterestsListing"
                        class="ml-6 text-sm text-gray-400"
                    >
                        Show
                        <x-icons.solid.angle-down
                            class="w-5 h-5 inline-block"
                        />
                    </span>
                    <span
                        x-show="showInterestsListing"
                        class="ml-6 text-sm text-gray-400"
                    >
                        Hide
                        <x-icons.solid.angle-up
                            class="w-5 h-5 inline-block"
                        />
                    </span>
                </h3>
                <div
                    x-show="showInterestsListing"
                >
                    <div class="overflow-x-auto">
                        <div class="min-w-full inline-block align-middle">
                            <div class="overflow-hidden">
                                <table
                                    class="mt-0"
                                >
                                    <thead>
                                        <tr>
                                            <th>
                                                Date
                                            </th>
                                            <th>
                                                Type
                                            </th>
                                            <th>
                                                Amount
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>

                                        @foreach ($loan->interests->sortByDesc("applied_on") as $interest)
                                            <tr>
                                                <td>
                                                    {{ $interest->applied_on->format("d M Y") }}
                                                </td>
                                                <td>
                                                    {{ $interest->type }}
                                                </td>
                                                <td>
                                                    ${{ number_format($interest->amount / 100, 2) }}
                                                </td>
                                            </tr>
                                        @endforeach

                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if ($loan->fees->isEmpty())
                <h3>
                    Fees
                </h3>
                <p
                    class="mt-0 text-gray-400"
                >
                    No fees have been applied.
                </p>
            @else
                <h3
                    x-on:click="showFeesListing = ! showFeesListing"
                >
                    Fees
                    <span
                        x-show="! showFeesListing"
                        class="ml-6 text-sm text-gray-400"
                    >
                        Show
                        <x-icons.solid.angle-down
                            class="w-5 h-5 inline-block"
                        />
                    </span>
                    <span
                        x-show="showFeesListing"
                        class="ml-6 text-sm text-gray-400"
                    >
                        Hide
                        <x-icons.solid.angle-up
                            class="w-5 h-5 inline-block"
                        />
                    </span>
                </h3>
                <div
                    x-show="showFeesListing"
                >
                    <div class="overflow-x-auto">
                        <div class="min-w-full inline-block align-middle sm:px-6 lg:px-8">
                            <div class="overflow-hidden">
                                <table
                                    class="mt-0"
                                >
                                    <thead>
                                        <tr>
                                            <th>
                                                Date
                                            </th>
                                            <th>
                                                Type
                                            </th>
                                            <th>
                                                Amount
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>

                                        @foreach ($loan->fees->sortByDesc("created_at") as $fee)
                                            <tr>
                                                <td>
                                                    {{ $fee->created_at->format("d M Y") }}
                                                </td>
                                                <td>
                                                    {{ $fee->type }}
                                                </td>
                                                <td>
                                                    ${{ number_format($fee->amount / 100, 2) }}
                                                </td>
                                            </tr>
                                        @endforeach

                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>
</div>

@push ("js")
    <script src="https://cdn.hellosign.com/public/js/embedded/v2.9.0/embedded.production.min.js"></script>
@endpush

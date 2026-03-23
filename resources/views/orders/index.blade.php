@extends('layouts.dash')
@section('content')

    <style>
        /* POS line table: predictable columns — unit price compact, total emphasized */
        .orders-pos-table-wrap .orders-pos-table {
            table-layout: fixed;
            width: 100%;
        }
        .orders-pos-table-wrap .orders-pos-table th,
        .orders-pos-table-wrap .orders-pos-table td {
            vertical-align: middle;
        }
        .orders-pos-table-wrap .orders-pos-table .form-control,
        .orders-pos-table-wrap .orders-pos-table .form-select {
            min-width: 0;
            width: 100%;
            padding: 0.28rem 0.45rem;
            font-size: 0.875rem;
        }
        .orders-pos-table-wrap .orders-pos-table .pos-total-input {
            font-weight: 600;
            text-align: right;
        }
        .orders-pos-table-wrap .select2-container {
            width: 100% !important;
            max-width: 100%;
        }
    </style>

    <!-- Modal -->
    <div class="modal fade" id="printMode" tabindex="-1" aria-hidden="true">
        @include('reports.receipt')
    </div>

    <div class="wrapper">
        <div class="page-wrapper">
            <div class="page-content">
                <!--breadcrumb-->
                <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                    <div class="breadcrumb-title pe-3">ORDER PRODUCTS</div>
                    <div class="ps-3">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="#"><i class="bx bx-home-alt"></i></a>
                                </li>
                                <li class="breadcrumb-item active" aria-current="page">Order</li>
                            </ol>
                        </nav>
                    </div>
                </div>
                <!--end breadcrumb-->
                @include('inc.msg')
                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-lg-flex align-items-center mb-4 gap-3">
                                    <div class="position-relative">
                                        <input type="text" class="form-control ps-5 radius-30" placeholder="Search Order">
                                        <span class="position-absolute top-50 product-show translate-middle-y"><i
                                                class="bx bx-search"></i></span>
                                    </div>
                                    <div class="ms-auto"><a href="javascript:;"
                                            class="btn btn-primary radius-30 mt-2 mt-lg-0 add_more"
                                            title="Add another product row to this sale"
                                            role="button"><i
                                                class="bx bxs-plus-square"></i> Add line item</a>
                                    </div>

                                </div>
                                <form action="{{ route('orders.store') }}" method="POST">
                                    @csrf
                                <div class="table-responsive orders-pos-table-wrap">
                                    <table class="table table-sm mb-0 orders-pos-table">
                                        <colgroup>
                                            <col style="width: 3rem;"><!-- # -->
                                            <col><!-- product -->
                                            <col style="width: 5rem;"><!-- qty -->
                                            <col style="width: 6.25rem;"><!-- unit -->
                                            <col style="width: 4.5rem;"><!-- disc -->
                                            <col style="width: 8.5rem;"><!-- total -->
                                            <col style="width: 3.25rem;"><!-- actions -->
                                        </colgroup>
                                        <thead class="table-light">
                                            <tr>
                                                <th scope="col" class="text-center">#</th>
                                                <th scope="col">Product</th>
                                                <th scope="col" class="text-end" title="Quantity sold in the product's unit of measure (e.g. tablets, bottles)">Qty <span class="d-block small text-muted fw-normal" style="font-size: 0.68rem;">(stock UOM)</span></th>
                                                <th scope="col" class="text-end text-nowrap" title="Unit price from product catalog">Unit</th>
                                                <th scope="col" class="text-end">%</th>
                                                <th scope="col" class="text-end">Line total</th>
                                                <th scope="col" class="text-center"></th>
                                            </tr>
                                        </thead>
                                        <tbody class="addMoreProduct">
                                            <tr>
                                                <td class="text-center text-muted small">1</td>
                                                <td class="align-middle">
                                                    <select name="product_id[]" id="product_id"
                                                        class="single-select product_id w-100">
                                                        <option value="">Select item</option>
                                                        @foreach ($products as $product)
                                                            <option data-price="{{ $product->price }}"
                                                                value="{{ $product->id }}">
                                                                {{ $product->product_name }}@if ($product->alias) ({{ $product->alias }})@endif @if ($product->packaging_label) — {{ $product->packaging_label }}@endif
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="number" required name="quantity[]" id="quantity"
                                                        min="1" step="1"
                                                        class="form-control quantity text-end">
                                                </td>
                                                <td>
                                                    <input type="number" name="price[]" id="price"
                                                        class="form-control price bg-light text-end"
                                                        readonly step="0.01" min="0"
                                                        inputmode="decimal"
                                                        tabindex="-1"
                                                        title="Price comes from the product catalog">
                                                </td>
                                                <td>
                                                    <input type="number" name="discount[]" id="discount"
                                                        min="0" max="100" step="0.01"
                                                        class="form-control discount text-end"
                                                        placeholder="0">
                                                </td>
                                                <td>
                                                    <input type="number" name="total_amount[]" id="line_total"
                                                        class="form-control total_amount pos-total-input bg-light text-end"
                                                        readonly step="0.01" min="0"
                                                        tabindex="-1"
                                                        title="Calculated from quantity, unit, and discount">
                                                </td>
                                                <td class="text-center p-0">
                                                    <a class="btn btn-sm btn-light border-0 delete" href="javascript:void(0)" title="Remove row"><i class="bx bxs-trash"></i></a>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            
                            </div>
                        </div>

                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <div class="bg-success p-4">
                                    <h2 class="text-center text-white"> Total:
                                        <b>{{ $currencySymbol }}</b><b class="total"><input type="hidden" name="total" value="0"><span class="total-amount">0.00</span></b>
                                    </h2>
                                </div>
                                <div class="table-responsive">
                                    <table class="table mb-0 table-striped">
                                        <tbody>
                                            <tr>
                                                <td>
                                                    <label for="customerName" class=""><b>Customer Name:</b></label>
                                                    <input type="text" name="customerName" id="customerName"
                                                        class="form-control customerName" autocomplete="name"
                                                        placeholder="Walk-in name">
                                                </td>
                                                <td>
                                                    <label for="customerMobile" class=""><b>Customer Mobile:</b></label>
                                                    <input type="tel" name="customerMobile" id="customerMobile"
                                                        class="form-control customerMobile" autocomplete="tel"
                                                        inputmode="tel" placeholder="e.g. 0244123456">
                                                </td>
                                            </tr>

                                        </tbody>
                                    </table>
                                    <table class="table table-striped">

                                        <td class="p-1"><b>Payment Method:</b>
                                            <br>
                                            <select class="form-select" name="paymentMethod" id="paymentMethod" required>
                                                <option value="">Select Payment Method</option>
                                                <option value="Cash">Cash</option>
                                                <option value="BankTransfer">Bank Transfer</option>
                                                <option value="CreditCard">Credit Card</option>
                                            </select>
                                            {{-- <span class="form-check">
                                                <input class="form-check-input" type="checkbox" name="paymentMethod"
                                                    value="cash" id="paymentMethod">
                                                <label class="" for="paymentMethod"><i
                                                        class="bx bx-money text-success"></i>Cash</label>

                                            </span>
                                            <span class="form-check">
                                                <input class="form-check-input" type="checkbox" name="bankTransfer"
                                                    value="bankTransfer" id="bankTransfer">
                                                <label class="" for="bankTransfer"><i
                                                        class="bx bxs-bank text-danger"></i>Bank Transfer</label>

                                            </span>
                                            <span class="form-check">
                                                <input class="form-check-input" type="checkbox" name="creditCard"
                                                    value="creditCard" id="creditCard">
                                                <label class="" for="creditCard"><i
                                                        class="bx bxs-credit-card text-primary"></i>Credit Card</label>
                                            </span> --}}
                                        </td>
                                    </table>
                                    <div class="pb-4">
                                        <label for="payment"><b>Payment:</b></label>
                                        <input class="form-control" name="paidAmount" id="paidAmount" type="number"
                                            min="0" step="0.01" inputmode="decimal" required />
                                    </div>
                                    <div class="pb-4">
                                        <label for="change"><b>Change:</b></label>
                                        <input class="form-control" name="balance" id="balance" type="number"
                                            step="0.01" inputmode="decimal" required
                                            title="Payment minus total (negative if underpaid); rounded to 2 decimals">
                                    </div>
                                    <div class="pb-4">
                                        <button type="submit" class="btn btn-primary w-100">Save</button>
                                    </div>
                                    {{-- <div class="pb-4">
                                        <button type="submit" class="btn btn-danger  w-100">Calculator</button>
                                    </div> --}}
                                </div>
                            </form>
                                <button onclick="ReceiptContent('printMode')" class="btn btn-dark btn-sm" data-bs-toggle="modal"
                                data-bs-target="#print"><i class="bx bxs-printer" ></i>Print</button>

                                <button class="btn btn-primary btn-sm" data-bs-toggle="modal"
                                data-bs-target="#print"><i class="bx bx-history" onclick="Receipt('print')"></i>History</button>

                                <button class="btn btn-danger btn-sm" data-bs-toggle="modal"
                                data-bs-target="#print"><i class="bx bxs-printer" onclick="Receipt('print')"></i>Report</button>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>
    

@endsection

@section('script')

    <script>
        $('.add_more').on('click', function() {
            var product = $('.orders-pos-table .product_id').first().html();
            var numberofrow = $('.addMoreProduct tr').length + 1;
            var tr = '<tr>' +
                '<td class="text-center text-muted small">' + numberofrow + '</td>' +
                '<td><select class="product_id form-select w-100" name="product_id[]">' + product + '</select></td>' +
                '<td><input type="number" name="quantity[]" min="1" step="1" class="form-control quantity text-end" required></td>' +
                '<td><input type="number" name="price[]" class="form-control price bg-light text-end" readonly step="0.01" min="0" inputmode="decimal" tabindex="-1" title="Price comes from the product catalog"></td>' +
                '<td><input type="number" name="discount[]" min="0" max="100" step="0.01" class="form-control discount text-end" placeholder="0"></td>' +
                '<td><input type="number" name="total_amount[]" class="form-control total_amount pos-total-input bg-light text-end" readonly step="0.01" min="0" tabindex="-1" title="Calculated from quantity, unit, and discount"></td>' +
                '<td class="text-center p-0"><a class="btn btn-sm btn-light border-0 delete" href="javascript:void(0)" title="Remove row"><i class="bx bxs-trash"></i></a></td>' +
                '</tr>';
            $('.addMoreProduct').append(tr);
        });
        $('.addMoreProduct').delegate('.delete', 'click', function() {
            $(this).parent().parent().remove();
        })


        function roundMoney(n) {
            return Math.round((parseFloat(n) || 0) * 100) / 100;
        }

        function getLineItemsTotal() {
            var total = 0;
            $('.total_amount').each(function() {
                total += parseFloat($(this).val()) || 0;
            });
            return roundMoney(total);
        }

        function TotalAmount() {
            var total = getLineItemsTotal();
            $('.total').find('input[name="total"]').val(total.toFixed(2));
            $('.total .total-amount').text(total.toFixed(2));
            updateChange();
        }

        function updateChange() {
            var total = getLineItemsTotal();
            var paid = parseFloat($('#paidAmount').val());
            if (isNaN(paid)) {
                paid = 0;
            }
            var change = roundMoney(paid - total);
            $('#balance').val(change.toFixed(2));
        }

        $('.addMoreProduct').delegate('.product_id', 'change', function() {
            var tr = $(this).parent().parent();
            var price = tr.find('.product_id option:selected').attr('data-price');
            tr.find('.price').val(price);
            var qty = tr.find('.quantity').val() - 0;
            var disc = tr.find('.discount').val() - 0;
            var price = tr.find('.price').val() - 0;
            var total_amount = roundMoney((qty * price) - ((qty * price * disc) / 100));
            tr.find('.total_amount').val(total_amount.toFixed(2));
            TotalAmount();
        });
        $('.addMoreProduct').delegate('.delete', 'click', function() {
            $(this).parent().parent().remove();
        })

        $('.addMoreProduct').delegate('.quantity , .discount', 'keyup change', function() {
            var tr = $(this).parent().parent();
            var qty = tr.find('.quantity').val() - 0;
            var disc = tr.find('.discount').val() - 0;
            var price = tr.find('.price').val() - 0;
            var total_amount = roundMoney((qty * price) - ((qty * price * disc) / 100));
            tr.find('.total_amount').val(total_amount.toFixed(2));
            TotalAmount();
        })
        $('.addMoreProduct').delegate('.delete', 'click', function() {
            $(this).parent().parent().remove();
        });

        $('#paidAmount').on('keyup change input', function() {
            updateChange();
        });

        (function posCustomerLookup() {
            var lookupUrl = @json(route('orders.customers.lookup'));
            var debounceTimer = null;

            function runLookup() {
                var phone = ($('#customerMobile').val() || '').trim();
                if (phone.length < 6) {
                    return;
                }
                fetch(lookupUrl + '?phone=' + encodeURIComponent(phone), {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                }).then(function(r) { return r.json(); }).then(function(data) {
                    if (data && data.found && data.name) {
                        $('#customerName').val(data.name);
                    }
                }).catch(function() { /* ignore */ });
            }

            $('#customerMobile').on('blur', function() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(runLookup, 200);
            });
        })();

        //Report printing Section
        function ReceiptContent(el){
            var data = '<input type="button" id="PrintReceiptButton class="PrintReceiptButton" style="display: block; bottom: 10px; width: 100%; border: none; background-color: #000; background-repeat: no-repeat; color: #fff; padding: 14px 28px; font-size: 16px; cursor:pointer; text-align: center" value="Print Receipt"" onClick="window.printMode()">';
            data += document.getElementById(el).innerHTML
            Receipt = window.open("", "myWin", "left=450, top=130, width=400, height=500");
            Receipt.screnX = 0;
            Receipt.screnY = 0;
            Receipt.document.write(data);
            Receipt.document.title = "Print Receipt"
            Receipt.focus();
            setTimeout(() => {
                Receipt.close(); 
            }, 8000);
        }

    </script>

@endsection

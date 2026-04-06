{{-- @extends('layouts.dash')
@section('content')
    <style>
        path {
            display: none;
        }

        svg {
            display: none;
        }

        .bottomright {
            position: absolute;
            bottom: 8px;
            right: 16px;
            font-size: 18px;
        }
    </style>
    <div class="wrapper">

        <div class="page-wrapper">
            <div class="page-content">

                <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                    <div class="breadcrumb-title pe-3">Employee</div>
                </div>
                <hr />
                @include('inc.msg')
                <form action="periodic" method="GET">
                    <div class="row">
                        <div class="input-group mb-3">
                            <input type="date" class="form-control noprint" name="start_date" required>
                            <input type="date" class="form-control noprint" name="end_date" required>
                            <button class="btn btn-primary ml-2 noprint" type="submit">Generate</button>
                            <a href="{{ route('reports.periodic') }}" class="btn btn-primary pull-left ml-4 noprint">
                                <h6>Reset</h6>
                            </a>
                        </div>
                    </div>
                </form>
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="example2" class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Product</th>
                                        <th>Amount</th>
                                        <th>Date</th>
                                        <th>Total. {{ $total }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($debt as $debt)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>@if ($debt->product){{ $debt->product->product_name }}@if ($debt->product->alias) ({{ $debt->product->alias }})@endif @php $pl = $debt->packaging_label ?? $debt->product->packaging_label; @endphp @if ($pl)<br><small class="text-muted">{{ $pl }}</small>@endif @else Name Not Found @endif</td>
                                            <td>{{ $currencySymbol }}{{ number_format($debt->amount ?? 0, 2) }}</td>
                                            <td>{{ $debt->created_at }}</td>
                                            <td class="bottomright"></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection --}}

@extends('layouts.dash')
@section('content')
<html> 
    <header>

    </header>
<style>
    path{
        display: none;
    }
    svg{
        display: none;
    }
    @media print{
            .noprint{
                display: none;
            }
        }
</style>
<body>
    

    <!--wrapper-->
    <div class="wrapper">
        <!--start page wrapper -->
        <div class="page-wrapper">
            <div class="page-content">
                <!--breadcrumb-->
                <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                    <div class="breadcrumb-title pe-3">Line items report</div>
                </div>
                <hr />
                @include('inc.msg')
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('reports.periodic') }}" method="GET" id="periodic-range-form">
                            <div class="row">
                                <div class="input-group flex-wrap mb-3 gap-2">
                                    <input type="date" class="form-control noprint" name="start_date" id="periodic-start" value="{{ $start_date }}" required aria-label="Start date" form="periodic-range-form">
                                    <input type="date" class="form-control noprint" name="end_date" id="periodic-end" value="{{ $end_date }}" required aria-label="End date" form="periodic-range-form">
                                    <button class="btn btn-primary noprint" type="submit">Generate</button>
                                    <a href="{{ route('reports.periodic') }}" class="btn btn-outline-secondary noprint">Today</a>
                                    <button type="button" class="btn btn-outline-primary noprint" onclick="window.print()">Print view</button>
                                    @can('reports.export')
                                    <a href="{{ route('reports.periodic.export', ['start_date' => $start_date, 'end_date' => $end_date]) }}" class="btn btn-outline-secondary noprint">Export CSV</a>
                                    <a href="{{ route('reports.periodic.pdf', ['start_date' => $start_date, 'end_date' => $end_date]) }}" class="btn btn-outline-secondary noprint">Export PDF</a>
                                    @endcan
                                </div>
                            </div>
                        </form>
                        <p class="text-muted small noprint mb-0">Choose dates, generate the table, then use <strong>Print view</strong> for the current range, or export CSV/PDF for the same range.</p>
                    </div>
                </div>
                
                <table id="example1" class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Product</th>
    
                            <th>Amount</th>
                            {{-- <th>Order By</th> --}}
    
                            <th>Date</th>
    
                        </tr>
                    </thead>
                    <tbody>
    
                        @foreach ($debt as $debt)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>@if ($debt->product){{ $debt->product->product_name }}@if ($debt->product->alias) ({{ $debt->product->alias }})@endif @php $pl = $debt->packaging_label ?? $debt->product->packaging_label; @endphp @if ($pl)<br><small class="text-muted">{{ $pl }}</small>@endif @else Name Not Found @endif</td>
    
    
                                <td>{{ $currencySymbol }}{{ number_format($debt->amount ?? 0, 2) }}</td>
                                {{-- <td>{{ $debt->user->name ?? 'not set' }}</td> --}}
    
                                <td>{{ $debt->created_at }}</td>
                            </tr>
                        @endforeach
                        <tr>
                            <td colspan="2"><strong>Total</strong></td>
                            <td><strong>{{ $currencySymbol }}{{ number_format($total ?? 0, 2) }}</strong></td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
                        {{-- <nav aria-label="..." class="py-5">
							<ul class="pagination">
								{{$users->links()}}
								
								
								
							</ul>
						</nav> --}}
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{-- <script>
        window.open(
           "https://www.geeksforgeeks.org", "_blank");
</script> --}}


</body>
</html>



@endsection
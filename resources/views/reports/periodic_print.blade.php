<head>
    <title></title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<style>
    path{
        display: none;
    }
    svg{
        display: none;
    }
</style>
    <!--wrapper-->
    <div class="wrapper">
        <!--start page wrapper -->
        <div class="page-wrapper">
            <div class="page-content">
                <!--breadcrumb-->
                <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                    <div class="breadcrumb-title pe-3"></div>
                  
                </div>
                <h2 style="text-align:center;">Period Report</h2>

                <hr />
               
                <div class="card">
                    <div class="card-body">
                        
    
                       
                    </div>
                </div>
                <table class="table table-bordered">
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
                                <td>@if ($debt->product){{ $debt->product->product_name }}@if ($debt->product->alias) ({{ $debt->product->alias }})@endif @else Name Not Found @endif</td>
    
    
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
    window.print();
</script> --}}







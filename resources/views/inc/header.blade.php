
<!--wrapper-->
<div class="wrapper">
    <!--sidebar wrapper -->
    <div class="sidebar-wrapper" data-simplebar="true">
        <div class="sidebar-header">
            <!-- <div>
                <img src="assets/images/logo-icon.png" class="logo-icon" alt="logo icon">
            </div> -->
            <div>
                <h4 class="logo-text">PMS</h4>
            </div>
            <div class="toggle-icon ms-auto"><i class='bx bx-arrow-to-left'></i>
            </div>
        </div>
        <!--navigation-->
        <ul class="metismenu" id="menu">

            <li class="menu-label">Main</li>
            <li>
                <a href="{{ route('dashboard') }}">
                    <div class="parent-icon"><i class='bx bx-home-circle'></i></div>
                    <div class="menu-title">Dashboard</div>
                </a>
            </li>
            @if (auth()->check() && auth()->user()->isSuperAdmin())
            <li>
                <a href="{{ route('dashboard.cross-site') }}">
                    <div class="parent-icon"><i class='bx bx-git-compare'></i></div>
                    <div class="menu-title">Cross-site</div>
                </a>
            </li>
            <li class="menu-label">Super Admin</li>
            <li>
                <a href="{{ route('super-admin.dashboard') }}">
                    <div class="parent-icon"><i class='bx bx-shield-quarter'></i></div>
                    <div class="menu-title">Platform dashboard</div>
                </a>
            </li>
            <li>
                <a href="javascript:;" class="has-arrow">
                    <div class="parent-icon"><i class='bx bx-globe'></i></div>
                    <div class="menu-title">Tenants &amp; billing</div>
                </a>
                <ul>
                    <li>
                        <a href="{{ route('super-admin.companies.index') }}"><i class="bx bx-right-arrow-alt"></i> Companies (tenants)</a>
                    </li>
                    <li>
                        <a href="{{ route('super-admin.subscriptions.index') }}"><i class="bx bx-right-arrow-alt"></i> Subscriptions</a>
                    </li>
                    <li>
                        <a href="{{ route('super-admin.packages.index') }}"><i class="bx bx-right-arrow-alt"></i> Packages</a>
                    </li>
                    <li>
                        <a href="{{ route('super-admin.domain') }}"><i class="bx bx-right-arrow-alt"></i> Domain</a>
                    </li>
                    <li>
                        <a href="{{ route('super-admin.payments.index') }}"><i class="bx bx-right-arrow-alt"></i> Purchase transactions</a>
                    </li>
                </ul>
            </li>
            @endif
            <li>
                <a href="{{ route('orders.index') }}">
                    <div class="parent-icon"><i class='bx bx-edit'></i></div>
                    <div class="menu-title">POS</div>
                </a>
            </li>

            <li class="menu-label">Inventory</li>
            <li>
                <a href="javascript:;" class="has-arrow">
                    <div class="parent-icon"><i class='bx bx-package'></i></div>
                    <div class="menu-title">Medicines &amp; catalog</div>
                </a>
                <ul>
                    <li><a href="{{ route('products.index') }}"><i class="bx bx-right-arrow-alt"></i>Medicine list</a></li>
                    <li><a href="{{ url('addproduct') }}"><i class="bx bx-right-arrow-alt"></i>Create medicine</a></li>
                    <li><a href="{{ route('inventory.low-stock') }}"><i class="bx bx-right-arrow-alt"></i>Low stock</a></li>
                    <li><a href="{{ route('inventory.batches') }}"><i class="bx bx-right-arrow-alt"></i>Batch management</a></li>
                    <li><a href="{{ route('inventory.expiry-tracking') }}"><i class="bx bx-right-arrow-alt"></i>Expiry tracking</a></li>
                    <li><a href="{{ route('inventory.receipts.index') }}"><i class="bx bx-right-arrow-alt"></i>Inventory logs (receipts)</a></li>
                    <li><a href="{{ route('inventory.catalog.categories') }}"><i class="bx bx-right-arrow-alt"></i>Category</a></li>
                    <li><a href="{{ route('manufacturers.index') }}"><i class="bx bx-right-arrow-alt"></i>Manufacturers</a></li>
                    <li><a href="{{ route('suppliers.index') }}"><i class="bx bx-right-arrow-alt"></i>Suppliers</a></li>
                    <li><a href="{{ route('inventory.catalog.units') }}"><i class="bx bx-right-arrow-alt"></i>Units</a></li>
                    <li><a href="{{ url('grid') }}"><i class="bx bx-right-arrow-alt"></i>Grid view</a></li>
                </ul>
            </li>

            <li class="menu-label">Prescriptions</li>
            <li>
                <a href="{{ route('pharmacy.prescriptions') }}">
                    <div class="parent-icon"><i class='bx bx-file'></i></div>
                    <div class="menu-title">Prescriptions</div>
                </a>
            </li>

            <li class="menu-label">Stock</li>
            <li>
                <a href="{{ route('inventory.manage-stock') }}">
                    <div class="parent-icon"><i class='bx bx-cube'></i></div>
                    <div class="menu-title">Manage stock</div>
                </a>
            </li>
            <li>
                <a href="{{ route('inventory.stock-adjustment.create') }}">
                    <div class="parent-icon"><i class='bx bx-slider-alt'></i></div>
                    <div class="menu-title">Stock adjustment</div>
                </a>
            </li>
            <li>
                <a href="{{ route('inventory.stock-transfer') }}">
                    <div class="parent-icon"><i class='bx bx-transfer'></i></div>
                    <div class="menu-title">Stock transfer</div>
                </a>
            </li>
            <li>
                <a href="{{ route('sites.index') }}">
                    <div class="parent-icon"><i class='bx bx-buildings'></i></div>
                    <div class="menu-title">Sites / branches</div>
                </a>
            </li>

            <li class="menu-label">Purchases</li>
            <li>
                <a href="{{ route('inventory.receive.create') }}">
                    <div class="parent-icon"><i class='bx bx-down-arrow-circle'></i></div>
                    <div class="menu-title">Receive stock</div>
                </a>
            </li>
            <li>
                <a href="{{ route('inventory.receipts.index') }}">
                    <div class="parent-icon"><i class='bx bx-list-ul'></i></div>
                    <div class="menu-title">Receipt history</div>
                </a>
            </li>

            <li class="menu-label">Sales &amp; reports</li>
            <li>
                <a href="{{ route('reports.periodic') }}">
                    <div class="parent-icon"><i class='bx bx-chart'></i></div>
                    <div class="menu-title">Today's report</div>
                </a>
            </li>
            <li>
                <a href="{{ route('settings.index') }}">
                    <div class="parent-icon"><i class='bx bx-cog'></i></div>
                    <div class="menu-title">Settings</div>
                </a>
            </li>

            {{-- <li>
                <a href="{{ route('transactions.index') }}">
                    <div class="parent-icon"><i class='bx bx-money'></i>
                    </div>
                    <div class="menu-title">Transactions</div>
                </a>
            </li>
            <li>
                <a href="#">
                    <div class="parent-icon"><i class='bx bx-file'></i>
                    </div>
                    <div class="menu-title">Reports</div>
                </a>
            </li>


            <li>
                <a href="#">
                    <div class="parent-icon"><i class='bx bx-user-check'></i>
                    </div>
                    <div class="menu-title">Customers</div>
                </a>
            </li>
            
            <li>
                <a class="has-arrow" href="javascript:;">
                    <div class="parent-icon"> <i class="bx bx-donate-blood"></i>
                    </div>
                    <div class="menu-title">Icons</div>
                </a>
                <ul>
                    <li> <a href="icons-line-icons.html"><i class="bx bx-right-arrow-alt"></i>Line Icons</a>
                    </li>
                    <li> <a href="icons-boxicons.html"><i class="bx bx-right-arrow-alt"></i>Boxicons</a>
                    </li>
                    <li> <a href="icons-feather-icons.html"><i class="bx bx-right-arrow-alt"></i>Feather Icons</a>
                    </li>
                </ul>
            </li> --}}
            <li class="menu-label">Users</li>
            <li>
                <a href="javascript:;" class="has-arrow">
                    <div class="parent-icon"><i class='bx bx-user'></i></div>
                    <div class="menu-title">Employee</div>
                </a>
            <ul>
                <li>
                    <a href="{{ route('users.index') }}">
                        <i class="bx bx-right-arrow-alt"></i> Add user
                    </a>
                </li>
                <li>
                    <a href="{{ route('users.employees.grid') }}">
                        <i class="bx bx-right-arrow-alt"></i> Employees (grid)
                    </a>
                </li>
                <li>
                    <a href="{{ route('pharmacy.showuser') }}">
                        <i class="bx bx-right-arrow-alt"></i> Manage users (list)
                    </a>
                </li>
            </ul>
            </li>
            <li>
                <a href="javascript:;" class="has-arrow">
                    <div class="parent-icon"><i class='bx bx-group'></i></div>
                    <div class="menu-title">Customers</div>
                </a>
                <ul>
                    <li>
                        <a href="{{ route('customers.index', ['view' => 'grid']) }}">
                            <i class="bx bx-right-arrow-alt"></i> Directory (grid)
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('customers.index', ['view' => 'list']) }}">
                            <i class="bx bx-right-arrow-alt"></i> Directory (list)
                        </a>
                    </li>
                </ul>
            </li>
           
        </ul>
        <!--end navigation-->
    </div>
    <!--end sidebar wrapper -->
    <!--start header -->
    <header>
        <div class="topbar d-flex align-items-center">
            <nav class="navbar navbar-expand">
                <div class="mobile-toggle-menu"><i class='bx bx-menu'></i>
                </div>
                <div class="search-bar flex-grow-1">
                    <div class="position-relative search-bar-box">
                        <input type="text" class="form-control search-control" placeholder="Type to search..."> <span class="position-absolute top-50 search-show translate-middle-y"><i class='bx bx-search'></i></span>
                        <span class="position-absolute top-50 search-close translate-middle-y"><i class='bx bx-x'></i></span>
                    </div>
                </div>
                <div class="d-none d-md-flex align-items-center gap-2 ms-3 flex-shrink-0">
                    <a href="{{ route('orders.index') }}" class="btn btn-primary btn-sm px-3">POS</a>
                    <a href="{{ route('inventory.receive.create') }}" class="btn btn-success btn-sm px-3">Receive</a>
                    @if (isset($sitesForSwitcher) && $sitesForSwitcher->count() > 0)
                        <form method="post" action="{{ route('sites.switch') }}" class="d-flex align-items-center gap-1 mb-0">
                            @csrf
                            <label class="small text-muted mb-0 d-none d-lg-inline">Site</label>
                            <select name="site_id" class="form-select form-select-sm" style="min-width: 9rem; max-width: 16rem;" title="Active branch for POS &amp; stock; super admins can choose All sites for dashboard totals" onchange="this.form.submit()">
                                @if (!empty($showDashboardAllSitesOption))
                                    <option value="all" {{ !empty($dashboardAllSites) ? 'selected' : '' }}>
                                        All sites (dashboard)
                                    </option>
                                @endif
                                @foreach ($sitesForSwitcher as $s)
                                    <option value="{{ $s->id }}" {{ empty($dashboardAllSites) && (int) ($currentSiteId ?? 0) === (int) $s->id ? 'selected' : '' }}>
                                        {{ $s->name }}@if($s->code) · {{ $s->code }}@endif
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    @endif
                </div>
                <div class="top-menu ms-auto">
                    <ul class="navbar-nav align-items-center">
                        <li class="nav-item mobile-search-icon">
                            <a class="nav-link" href="#">	<i class='bx bx-search'></i>
                            </a>
                        </li>
                        <li class="nav-item dropdown dropdown-large">
                            <a class="nav-link dropdown-toggle dropdown-toggle-nocaret" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">	<i class='bx bx-category'></i>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end">
                                <div class="row row-cols-3 g-3 p-3">
                                    <div class="col text-center">
                                        <div class="app-box mx-auto bg-gradient-cosmic text-white"><i class='bx bx-group'></i>
                                        </div>
                                        <div class="app-title">Teams</div>
                                    </div>
                                    <div class="col text-center">
                                        <div class="app-box mx-auto bg-gradient-burning text-white"><i class='bx bx-atom'></i>
                                        </div>
                                        <div class="app-title">Projects</div>
                                    </div>
                                    <div class="col text-center">
                                        <div class="app-box mx-auto bg-gradient-lush text-white"><i class='bx bx-shield'></i>
                                        </div>
                                        <div class="app-title">Tasks</div>
                                    </div>
                                    <div class="col text-center">
                                        <div class="app-box mx-auto bg-gradient-kyoto text-dark"><i class='bx bx-notification'></i>
                                        </div>
                                        <div class="app-title">Feeds</div>
                                    </div>
                                    <div class="col text-center">
                                        <div class="app-box mx-auto bg-gradient-blues text-dark"><i class='bx bx-file'></i>
                                        </div>
                                        <div class="app-title">Files</div>
                                    </div>
                                    <div class="col text-center">
                                        <div class="app-box mx-auto bg-gradient-moonlit text-white"><i class='bx bx-filter-alt'></i>
                                        </div>
                                        <div class="app-title">Alerts</div>
                                    </div>
                                </div>
                            </div>
                        </li>
                        <li class="nav-item dropdown dropdown-large">
                            <a class="nav-link dropdown-toggle dropdown-toggle-nocaret position-relative" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"> <span class="alert-count">7</span>
                                <i class='bx bx-bell'></i>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a href="javascript:;">
                                    <div class="msg-header">
                                        <p class="msg-header-title">Notifications</p>
                                        <p class="msg-header-clear ms-auto">Marks all as read</p>
                                    </div>
                                </a>
                                <div class="header-notifications-list">
                                    <a class="dropdown-item" href="javascript:;">
                                        <div class="d-flex align-items-center">
                                            <div class="notify bg-light-primary text-primary"><i class="bx bx-group"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="msg-name">New Customers<span class="msg-time float-end">14 Sec
                                            ago</span></h6>
                                                <p class="msg-info">5 new user registered</p>
                                            </div>
                                        </div>
                                    </a>
                                    
                                </div>
                                <a href="javascript:;">
                                    <div class="text-center msg-footer">View All Notifications</div>
                                </a>
                            </div>
                        </li>
                        <li class="nav-item dropdown dropdown-large">
                            <a class="nav-link dropdown-toggle dropdown-toggle-nocaret position-relative" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"> <span class="alert-count">8</span>
                                <i class='bx bx-comment'></i>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a href="javascript:;">
                                    <div class="msg-header">
                                        <p class="msg-header-title">Messages</p>
                                        <p class="msg-header-clear ms-auto">Marks all as read</p>
                                    </div>
                                </a>
                                <div class="header-message-list">
                                    <a class="dropdown-item" href="javascript:;">
                                        <div class="d-flex align-items-center">
                                            <div class="user-online">
                                                <img src="assets/images/avatars/avatar-1.png" class="msg-avatar" alt="user avatar">
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="msg-name">Daisy Anderson <span class="msg-time float-end">5 sec
                                            ago</span></h6>
                                                <p class="msg-info">The standard chunk of lorem</p>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                <a href="javascript:;">
                                    <div class="text-center msg-footer">View All Messages</div>
                                </a>
                            </div>
                        </li>
                    </ul>
                </div>
                @guest
                <div class="user-box dropdown">
                    @if (Route::has('login'))
                    
                        <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
                   
                @endif

                @if (Route::has('register'))
                   
                        <a class="nav-link" href="{{ route('register') }}">{{ __('Register') }}</a>
                    
                @endif
            @else
                    <a class="d-flex align-items-center nav-link dropdown-toggle dropdown-toggle-nocaret" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="{{asset('/storage/users/' .Auth::user()->user_img)}}" class="user-img" alt="user">
                        <div class="user-info ps-3">
                            <p class="user-name mb-0">{{ Auth::user()->name }}</p>
                            <p class="designattion mb-0"> 
                                
                                @if (Auth::user()->is_admin == 1) Admin
                                @elseif (Auth::user()->is_admin == 2) Cashier
                                @else Manager
                                @endif
                            </p>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="users/profile"><i class="bx bx-user"></i><span>Profile</span></a>
                        </li>
                        <li><a class="dropdown-item" href="{{ route('settings.index') }}"><i class="bx bx-cog"></i><span>Settings</span></a>
                        </li>
                        <li><a class="dropdown-item" href="javascript:;"><i class='bx bx-home-circle'></i><span>Dashboard</span></a>
                        </li>
                        <li><a class="dropdown-item" href="javascript:;"><i class='bx bx-dollar-circle'></i><span>Earnings</span></a>
                        </li>
                        <li><a class="dropdown-item" href="javascript:;"><i class='bx bx-download'></i><span>Downloads</span></a>
                        </li>
                        <li>
                            <div class="dropdown-divider mb-0"></div>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('logout') }}" 
                            onclick="event.preventDefault(); document.getElementById('logout-form').submit();"><i class='bx bx-log-out-circle'></i><span>{{ __('Logout') }}</span></a>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                @csrf
                                
                            </form>
                        </li>
                    </ul>
                </div>
                @endguest
            </nav>
        </div>
    </header>
    <!--end header -->
<!--start switcher-->
<div class="switcher-wrapper">
    <div class="switcher-btn"> <i class='bx bx-cog bx-spin'></i>
    </div>
    <div class="switcher-body">
        <div class="d-flex align-items-center">
            <h5 class="mb-0 text-uppercase">Theme Customizer</h5>
            <button type="button" class="btn-close ms-auto close-switcher" aria-label="Close"></button>
        </div>
        <hr/>
        <h6 class="mb-0">Theme Styles</h6>
        <hr/>
        <div class="d-flex align-items-center justify-content-between">
            <div class="form-check">
                <input class="form-check-input" type="radio" name="flexRadioDefault" id="lightmode" checked>
                <label class="form-check-label" for="lightmode">Light</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="flexRadioDefault" id="darkmode">
                <label class="form-check-label" for="darkmode">Dark</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="flexRadioDefault" id="semidark">
                <label class="form-check-label" for="semidark">Semi Dark</label>
            </div>
        </div>
        <hr/>
        <div class="form-check">
            <input class="form-check-input" type="radio" id="minimaltheme" name="flexRadioDefault">
            <label class="form-check-label" for="minimaltheme">Minimal Theme</label>
        </div>
        <hr/>
        <h6 class="mb-0">Header Colors</h6>
        <hr/>
        <div class="header-colors-indigators">
            <div class="row row-cols-auto g-3">
                <div class="col">
                    <div class="indigator headercolor1" id="headercolor1"></div>
                </div>
                <div class="col">
                    <div class="indigator headercolor2" id="headercolor2"></div>
                </div>
                <div class="col">
                    <div class="indigator headercolor3" id="headercolor3"></div>
                </div>
                <div class="col">
                    <div class="indigator headercolor4" id="headercolor4"></div>
                </div>
                <div class="col">
                    <div class="indigator headercolor5" id="headercolor5"></div>
                </div>
                <div class="col">
                    <div class="indigator headercolor6" id="headercolor6"></div>
                </div>
                <div class="col">
                    <div class="indigator headercolor7" id="headercolor7"></div>
                </div>
                <div class="col">
                    <div class="indigator headercolor8" id="headercolor8"></div>
                </div>
            </div>
        </div>
        <hr/>
        <h6 class="mb-0">Sidebar Colors</h6>
        <hr/>
        <div class="header-colors-indigators">
            <div class="row row-cols-auto g-3">
                <div class="col">
                    <div class="indigator sidebarcolor1" id="sidebarcolor1"></div>
                </div>
                <div class="col">
                    <div class="indigator sidebarcolor2" id="sidebarcolor2"></div>
                </div>
                <div class="col">
                    <div class="indigator sidebarcolor3" id="sidebarcolor3"></div>
                </div>
                <div class="col">
                    <div class="indigator sidebarcolor4" id="sidebarcolor4"></div>
                </div>
                <div class="col">
                    <div class="indigator sidebarcolor5" id="sidebarcolor5"></div>
                </div>
                <div class="col">
                    <div class="indigator sidebarcolor6" id="sidebarcolor6"></div>
                </div>
                <div class="col">
                    <div class="indigator sidebarcolor7" id="sidebarcolor7"></div>
                </div>
                <div class="col">
                    <div class="indigator sidebarcolor8" id="sidebarcolor8"></div>
                </div>
            </div>
        </div>
    </div>
</div>
<footer class="page-footer">
    <p class="mb-0">Copyright © {{date('Y')}}. All right reserved.</p>
</footer>
<!--end switcher-->
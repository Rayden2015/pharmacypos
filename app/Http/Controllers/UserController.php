<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Site;
use App\Models\User;
use App\Support\CurrentSite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;


class UserController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }



    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $users = User::query()
            ->forCurrentSiteContext()
            ->orderBy('name')
            ->paginate(5);

        $sites = Site::query()->forUserTenant(auth()->user())->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']);

        return view('users.index', compact('users', 'sites'));
    }

    /**
     * Employee list (manage users). Available at /showuser and /pharmacy/showuser.
     */
    public function manageUsers()
    {
        $users = User::query()
            ->forCurrentSiteContext()
            ->with('site:id,name,code')
            ->orderBy('name')
            ->paginate(15);

        $sites = Site::query()->forUserTenant(auth()->user())->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']);

        return view('users.showuser', compact('users', 'sites'));
    }

    /**
     * Dreams-style card grid for employees (/pharmacy/employees/grid).
     */
    public function employeesGrid(Request $request)
    {
        $query = User::query()
            ->forCurrentSiteContext()
            ->with('site:id,name,code');

        if ($request->filled('search')) {
            $term = '%'.$request->input('search').'%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('mobile', 'like', $term);
            });
        }

        if ($request->query('status') === 'active') {
            $query->where('status', '1');
        } elseif ($request->query('status') === 'inactive') {
            $query->where('status', '2');
        }

        if ($request->filled('role')) {
            $query->where('is_admin', $request->input('role'));
        }

        $statsBase = User::query()->forCurrentSiteContext();
        $stats = [
            'total' => (clone $statsBase)->count(),
            'active' => (clone $statsBase)->where('status', '1')->count(),
            'inactive' => (clone $statsBase)->where('status', '2')->count(),
            'new_this_month' => (clone $statsBase)->where('created_at', '>=', now()->startOfMonth())->count(),
        ];

        $users = $query->orderBy('name')->paginate(12)->withQueryString();

        $sites = Site::query()->forUserTenant(auth()->user())->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']);

        return view('users.employees-grid', compact('users', 'sites', 'stats'));
    }

    public function profile()
    {
        return view('users.profile');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $viewer = $request->user();

        $rules = [
            'name' => 'required',
            'email' => 'required',
            'mobile' => ['required', 'string', 'min:10', 'max:10'],
            'address' => 'required',
            'is_admin' => 'required',
            'status' => 'required',
            'password' => 'required|min:6|max:20',
            'confirm_password' => 'required|same:password',
            'is_super_admin' => 'nullable|boolean',
        ];

        if ($viewer->isSuperAdmin() && ! $request->boolean('is_super_admin')) {
            $rules['site_id'] = 'required|exists:sites,id';
        } else {
            $rules['site_id'] = 'nullable|exists:sites,id';
        }

        $this->validate($request, $rules);

        if (User::query()->where('email', strtolower($request->input('email')))->exists()) {
            return redirect()->back()->with('error', 'User Registration Failed, Email Already Exists!');
        }

        if ($request->hasFile('user_img')) {
            $fileNameWithExt = $request->file('user_img')->getClientOriginalName();
            $filename = pathinfo($fileNameWithExt, PATHINFO_FILENAME);
            $extension = $request->file('user_img')->getClientOriginalExtension();
            $fileNameToStore = $filename.'_'.time().'.'.$extension;
            $request->file('user_img')->storeAs('public/users', $fileNameToStore);
        } else {
            $fileNameToStore = 'user.png';
        }

        $makeSuper = $viewer->isSuperAdmin() && $request->boolean('is_super_admin');

        $data = $request->input();
        $user = new User;
        $user->name = ucwords($data['name']);
        $user->email = strtolower($data['email']);
        $user->password = Hash::make($request['password']);
        $user->confirm_password = Hash::make($request['confirm_password']);
        $user->mobile = $request->mobile;
        $user->address = ucwords($data['address']);
        $user->status = $request->status;
        $user->is_admin = $request->is_admin;
        $user->user_img = $fileNameToStore;

        if ($makeSuper) {
            $user->is_super_admin = true;
            $user->site_id = $request->filled('site_id') ? (int) $request->site_id : null;
            $user->company_id = null;
        } else {
            $user->is_super_admin = false;
            if ($viewer->isSuperAdmin()) {
                $user->site_id = (int) $request->input('site_id');
            } else {
                $user->site_id = (int) CurrentSite::id();
            }
            $site = Site::query()->find($user->site_id);
            $user->company_id = $site?->company_id ?? Company::defaultId();
        }

        $user->save();

        return redirect()->route('pharmacy.showuser')->with('success', 'User Created Successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        User::query()->forCurrentSiteContext()->findOrFail($id);

        $users = User::query()
            ->forCurrentSiteContext()
            ->orderBy('name')
            ->paginate(5);

        $sites = Site::query()->forUserTenant(auth()->user())->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']);

        return view('users.index', compact('users', 'sites'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $this->authorizeUserAccess($user);

        $this->validate($request, [
            'name' => 'required',
            'email' => 'required',
            'mobile' => ['required', 'string', 'min:10', 'max:10'],
            'address' => 'required',
            'is_admin' => 'required',
            'status' => 'required',
            'user_img' => 'image|nullable|max:2042',
            'is_super_admin' => 'nullable|boolean',
            'site_id' => 'nullable|exists:sites,id',
        ]);

        if ($request->hasFile('user_img')) {
            //Get file name
            $fileNameWithExt = $request->file('user_img')->getClientOriginalName();
            //File name
            $filename = pathinfo($fileNameWithExt, PATHINFO_FILENAME);

            $extension = $request->file('user_img')->getClientOriginalExtension();

            $fileNameToStore = $filename. '_' .time(). '.' .$extension;

            $request->file('user_img')->storeAs('public/users', $fileNameToStore);
        } else {
            $fileNameToStore = $user->user_img;
        }

        $data = $request->input();
        $user->name = ucwords($data['name']);
        $user->email = strtolower($data['email']);
        $user->mobile = $data['mobile'];
        $user->address = ucwords($data['address']);
        $user->is_admin = $data['is_admin'];
        $user->status = $data['status'];
        if (! empty($data['password'])) {
            if ($data['password'] === ($data['confirm_password'] ?? '')) {
                $user->password = Hash::make($data['password']);
                $user->confirm_password = Hash::make($data['confirm_password']);
            }
        }
        $user->user_img = $fileNameToStore;

        $viewer = $request->user();
        if ($viewer->isSuperAdmin()) {
            $user->is_super_admin = $request->boolean('is_super_admin');
            if ($user->is_super_admin) {
                $user->site_id = $request->filled('site_id') ? (int) $request->site_id : null;
                $user->company_id = null;
            } else {
                $user->site_id = $request->filled('site_id') ? (int) $request->site_id : CurrentSite::id();
                $site = Site::query()->find($user->site_id);
                $user->company_id = $site?->company_id ?? Company::defaultId();
            }
        }

        $user->save();

        return redirect()->back()->with('success', 'User Updated Successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $this->authorizeUserAccess($user);

        if (! auth()->user()->isSuperAdmin() && $user->isSuperAdmin()) {
            abort(403);
        }
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account here.');
        }

        if ($user->user_img != 'user.png') {
            Storage::delete('public/users/'.$user->user_img);
        }
        $user->delete();

        return back()->with('success', 'User Deleted Successfully');
    }

    private function authorizeUserAccess(User $target): void
    {
        $viewer = auth()->user();
        if ($viewer->isSuperAdmin()) {
            return;
        }
        if ($viewer->company_id && (int) $target->company_id !== (int) $viewer->company_id) {
            abort(403);
        }
        if ((int) $target->site_id !== (int) CurrentSite::id()) {
            abort(403);
        }
    }
}

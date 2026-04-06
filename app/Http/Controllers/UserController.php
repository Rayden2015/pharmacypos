<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Site;
use App\Models\User;
use App\Support\CurrentSite;
use App\Support\TenantUserRoles;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;


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
        $assignableRoles = $this->assignableRolesForForms();

        return view('users.index', compact('users', 'sites', 'assignableRoles'));
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
        $assignableRoles = $this->assignableRolesForForms();

        return view('users.showuser', compact('users', 'sites', 'assignableRoles'));
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
            $role = $request->input('role');
            if (is_string($role) && array_key_exists($role, User::HIERARCHY_ROLE_LABELS)) {
                $query->where('tenant_role', $role);
            } elseif (in_array((string) $role, ['1', '2', '3'], true)) {
                $query->where('is_admin', (int) $role);
            }
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
        $assignableRoles = $this->assignableRolesForForms();

        return view('users.employees-grid', compact('users', 'sites', 'stats', 'assignableRoles'));
    }

    public function profile()
    {
        $user = auth()->user()->load(['site:id,name,code', 'company:id,company_name']);

        $parts = preg_split('/\s+/', trim((string) $user->name), 2);
        $firstName = $user->first_name ?? ($parts[0] ?? '');
        $lastName = $user->last_name ?? ($parts[1] ?? '');

        return view('users.profile', compact('user', 'firstName', 'lastName'));
    }

    /**
     * Self-service profile: basic info, password, or 2FA preference toggles (DreamsPOS-style sections).
     * Admin user CRUD remains on {@see update()}.
     */
    public function updateProfile(Request $request)
    {
        return match ($request->input('_section', 'profile')) {
            'password' => $this->updateProfilePasswordOnly($request),
            'security' => $this->updateProfileSecurityPrefs($request),
            default => $this->updateProfileBasicInfo($request),
        };
    }

    private function updateProfileBasicInfo(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'mobile' => ['required', 'string', 'min:10', 'max:10'],
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:120',
            'city' => 'nullable|string|max:120',
            'state_region' => 'nullable|string|max:120',
            'postal_code' => 'nullable|string|max:32',
            'user_img' => 'nullable|image|max:2048',
        ]);

        $user->first_name = ucwords(strtolower($validated['first_name']));
        $lastRaw = $validated['last_name'] ?? null;
        $user->last_name = $lastRaw !== null && trim((string) $lastRaw) !== ''
            ? ucwords(strtolower(trim((string) $lastRaw)))
            : null;
        $user->name = trim($user->first_name.' '.($user->last_name ?? ''));
        $user->email = strtolower($validated['email']);
        $user->mobile = $validated['mobile'];
        $user->address_line1 = $this->nullableTrimmedString($validated['address_line1'] ?? null);
        $user->address_line2 = $this->nullableTrimmedString($validated['address_line2'] ?? null);
        $user->country = $this->nullableTrimmedString($validated['country'] ?? null);
        $user->city = $this->nullableTrimmedString($validated['city'] ?? null);
        $user->state_region = $this->nullableTrimmedString($validated['state_region'] ?? null);
        $user->postal_code = $this->nullableTrimmedString($validated['postal_code'] ?? null);
        $user->address = $user->address_line1;

        if ($request->hasFile('user_img')) {
            if ($user->user_img && $user->user_img !== 'user.png') {
                Storage::delete('public/users/'.$user->user_img);
            }
            $user->user_img = $this->storePublicUserAvatar($request->file('user_img'));
        }

        $user->save();

        Log::channel('audit')->info('profile.updated', [
            'user_id' => $user->id,
            'site_id' => $user->site_id,
            'company_id' => $user->company_id,
            'section' => 'basic',
            'email_changed' => $user->wasChanged('email'),
            'avatar_changed' => $request->hasFile('user_img'),
        ]);

        return redirect()->route('profile')->with('success', __('Profile updated successfully.'));
    }

    private function updateProfilePasswordOnly(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:6|max:255|confirmed',
        ]);

        if (! Hash::check($validated['current_password'], $user->getAuthPassword())) {
            return redirect()->route('profile')
                ->withErrors(['current_password' => __('The current password is incorrect.')])
                ->withInput();
        }

        $user->password = Hash::make($validated['password']);
        $user->confirm_password = Hash::make($validated['password']);
        $user->save();

        Log::channel('audit')->info('profile.updated', [
            'user_id' => $user->id,
            'site_id' => $user->site_id,
            'company_id' => $user->company_id,
            'section' => 'password',
        ]);

        return redirect()->route('profile')->with('success', __('Password updated successfully.'));
    }

    private function updateProfileSecurityPrefs(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'two_factor_sms' => 'nullable|boolean',
            'two_factor_email' => 'nullable|boolean',
        ]);

        $prefs = $user->notification_preferences ?? [];
        $prefs['two_factor_sms'] = $request->boolean('two_factor_sms');
        $prefs['two_factor_email'] = $request->boolean('two_factor_email');
        $user->notification_preferences = $prefs;
        $user->save();

        Log::channel('audit')->info('profile.updated', [
            'user_id' => $user->id,
            'site_id' => $user->site_id,
            'company_id' => $user->company_id,
            'section' => 'security',
        ]);

        return redirect()->route('profile')->with('success', __('Security preferences saved.'));
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

        $makeSuper = $viewer->isSuperAdmin() && $request->boolean('is_super_admin');

        $rules = [
            'name' => 'required',
            'email' => 'required',
            'mobile' => ['required', 'string', 'min:10', 'max:10'],
            'address' => 'required',
            'status' => 'required',
            'password' => 'required|min:6|max:20',
            'confirm_password' => 'required|same:password',
            'is_super_admin' => 'nullable|boolean',
        ];

        $targetCompanyId = null;
        if (! $makeSuper) {
            if ($viewer->isSuperAdmin()) {
                $rules['site_id'] = 'required|exists:sites,id';
            } else {
                $rules['site_id'] = 'nullable|exists:sites,id';
            }
            if ($viewer->isSuperAdmin()) {
                $targetCompanyId = (int) Site::query()->whereKey((int) $request->input('site_id'))->value('company_id');
            } else {
                $targetCompanyId = (int) (Site::query()->whereKey((int) CurrentSite::id())->value('company_id') ?: $viewer->company_id);
            }
            $rules['role_id'] = ['required', 'integer', Rule::exists('roles', 'id')->where(function ($q) use ($targetCompanyId) {
                $q->where('guard_name', 'web')->where('company_id', $targetCompanyId);
            })];
        } else {
            $rules['role_id'] = 'nullable';
            if ($viewer->isSuperAdmin() && ! $request->boolean('is_super_admin')) {
                $rules['site_id'] = 'required|exists:sites,id';
            } else {
                $rules['site_id'] = 'nullable|exists:sites,id';
            }
        }

        $this->validate($request, $rules);

        if (User::query()->where('email', strtolower($request->input('email')))->exists()) {
            return redirect()->back()->with('error', 'User Registration Failed, Email Already Exists!');
        }

        if ($request->hasFile('user_img')) {
            $fileNameToStore = $this->storePublicUserAvatar($request->file('user_img'));
        } else {
            $fileNameToStore = 'user.png';
        }

        $data = $request->input();
        $user = new User;
        $user->name = ucwords($data['name']);
        $user->email = strtolower($data['email']);
        $user->password = Hash::make($request['password']);
        $user->confirm_password = Hash::make($request['confirm_password']);
        $user->mobile = $request->mobile;
        $user->address = ucwords($data['address']);
        $user->status = $request->status;
        $user->user_img = $fileNameToStore;

        if ($makeSuper) {
            $user->is_super_admin = true;
            $user->site_id = $request->filled('site_id') ? (int) $request->site_id : null;
            $user->company_id = null;
            $user->tenant_role = null;
            $user->is_admin = 0;
        } else {
            $user->is_super_admin = false;
            if ($viewer->isSuperAdmin()) {
                $user->site_id = (int) $request->input('site_id');
            } else {
                $user->site_id = (int) CurrentSite::id();
            }
            $site = Site::query()->find($user->site_id);
            $user->company_id = $site?->company_id ?? Company::defaultId();
            $user->tenant_role = null;
            $user->is_admin = 0;
        }

        $user->save();

        if (! $user->is_super_admin && $user->company_id) {
            TenantUserRoles::syncSpatieRoleAssignment($user, (int) $request->input('role_id'));
        } elseif ($user->is_super_admin) {
            $registrar = app(\Spatie\Permission\PermissionRegistrar::class);
            if ($user->company_id) {
                $registrar->setPermissionsTeamId($user->company_id);
            }
            $user->syncRoles([]);
            $registrar->setPermissionsTeamId(null);
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        }

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
        $assignableRoles = $this->assignableRolesForForms();

        return view('users.index', compact('users', 'sites', 'assignableRoles'));
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

        $viewer = $request->user();
        $willBeSuperAdmin = $viewer->isSuperAdmin()
            ? $request->boolean('is_super_admin')
            : (bool) $user->is_super_admin;

        $targetCompanyId = (int) $user->company_id;
        if ($viewer->isSuperAdmin() && $request->filled('site_id')) {
            $targetCompanyId = (int) Site::query()->whereKey((int) $request->input('site_id'))->value('company_id');
        }

        $rules = [
            'name' => 'required',
            'email' => 'required',
            'mobile' => ['required', 'string', 'min:10', 'max:10'],
            'address' => 'required',
            'status' => 'required',
            'user_img' => 'image|nullable|max:2042',
            'is_super_admin' => 'nullable|boolean',
            'site_id' => 'nullable|exists:sites,id',
        ];
        $rules['role_id'] = $willBeSuperAdmin
            ? 'nullable'
            : ['required', 'integer', Rule::exists('roles', 'id')->where(function ($q) use ($targetCompanyId) {
                $q->where('guard_name', 'web')->where('company_id', $targetCompanyId);
            })];

        $this->validate($request, $rules);

        if ($request->hasFile('user_img')) {
            if ($user->user_img && $user->user_img !== 'user.png') {
                Storage::delete('public/users/'.$user->user_img);
            }
            $fileNameToStore = $this->storePublicUserAvatar($request->file('user_img'));
        } else {
            $fileNameToStore = $user->user_img;
        }

        $data = $request->input();
        $user->name = ucwords($data['name']);
        $user->email = strtolower($data['email']);
        $user->mobile = $data['mobile'];
        $user->address = ucwords($data['address']);
        $user->status = $data['status'];
        if (! empty($data['password'])) {
            if ($data['password'] === ($data['confirm_password'] ?? '')) {
                $user->password = Hash::make($data['password']);
                $user->confirm_password = Hash::make($data['confirm_password']);
            }
        }
        $user->user_img = $fileNameToStore;

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

        if ($user->is_super_admin) {
            $user->tenant_role = null;
            $user->is_admin = 0;
        }

        $user->save();

        if (! $user->is_super_admin && $user->company_id) {
            TenantUserRoles::syncSpatieRoleAssignment($user, (int) $request->input('role_id'));
        } else {
            $registrar = app(\Spatie\Permission\PermissionRegistrar::class);
            if ($user->company_id) {
                $registrar->setPermissionsTeamId($user->company_id);
            }
            $user->syncRoles([]);
            $registrar->setPermissionsTeamId(null);
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        }

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

    private function storePublicUserAvatar(UploadedFile $file): string
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        if (! in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            $ext = 'jpg';
        }
        $name = 'u_'.bin2hex(random_bytes(12)).'.'.$ext;
        $file->storeAs('public/users', $name);

        return $name;
    }

    /**
     * Spatie roles defined for the active company context (tenant users: their org; super admin: current branch’s company).
     *
     * @return \Illuminate\Support\Collection<int, \Spatie\Permission\Models\Role>
     */
    private function assignableRolesForForms()
    {
        $viewer = auth()->user();
        $companyId = $viewer->isSuperAdmin()
            ? (int) (Site::query()->whereKey((int) CurrentSite::id())->value('company_id') ?? 0)
            : (int) ($viewer->company_id ?? 0);
        if ($companyId < 1) {
            return collect();
        }

        return Role::query()
            ->where('company_id', $companyId)
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get();
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

    private function nullableTrimmedString(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return trim($value);
    }
}

<?php

namespace App\Http\Controllers;

use App\DataTables\UserManagerDataTable;
use App\Events\UserRoleChangedEvent;
use App\Http\Requests\CreateUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\ModelHasRole;
use App\Repositories\CustomFieldRepository;
use App\Repositories\RoleRepository;
use App\Repositories\RoleFolioRepository;
use App\Repositories\UploadRepository;
use App\Repositories\UserRepository;
use App\Repositories\VehicleRepository;
use Flash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Prettus\Validator\Exceptions\ValidatorException;

class UserManagerController extends Controller
{
    /** @var  UserRepository */
    private $userRepository;
    /**
     * @var RoleRepository
     */
    private $roleRepository;

    /**
     * @var RoleFolioRepository
     */
    private $roleFolioRepository;

    /**
     * @var ModelHasRoleRepository
     */
    private $modelHasRoleRepository;

    private $uploadRepository;

    /**
     * @var CustomFieldRepository
     */
    private $customFieldRepository;

    public function __construct(
        UserRepository $userRepo,
        RoleRepository $roleRepo,
        UploadRepository $uploadRepo,
        CustomFieldRepository $customFieldRepo,
        VehicleRepository $vehicleRepo,
        RoleFolioRepository $roleFolioRepo,
        ModelHasRole $modelHasRoleRepo
    ) {
        parent::__construct();
        $this->userRepository = $userRepo;
        $this->roleRepository = $roleRepo;
        $this->uploadRepository = $uploadRepo;
        $this->customFieldRepository = $customFieldRepo;
        $this->vehicleRepository = $vehicleRepo;
        $this->roleFolioRepository = $roleFolioRepo;
        $this->modelHasRoleRepository = $modelHasRoleRepo;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(UserManagerDataTable $userDataTable)
    {
        return $userDataTable->render('settings.users.manager.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $role = $this->roleRepository->where('name', 'manager')->pluck('name', 'name');
        $vehicle = $this->vehicleRepository->pluck('brand', 'id');
        $rolesSelected = [];
        $hasCustomField = in_array($this->userRepository->model(), setting('custom_field_models', []));
        if ($hasCustomField) {
            $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->userRepository->model());
            $html = generateCustomField($customFields);
        }

        return view('settings.users.manager.create')
            ->with("role", $role)
            ->with("customFields", isset($html) ? $html : false)
            ->with("rolesSelected", $rolesSelected)
            ->with('vehicle', $vehicle);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateUserRequest $request)
    {
        //Si es demo entonces no es modificable
        if (env('APP_DEMO', false)) {
            Flash::warning('This is only demo app you can\'t change this section ');
            return redirect(route('users.manager.index'));
        }

        $input = $request->all();

        //Get role_id by role name
        $role = $this->roleRepository->where('name', $request->roles)->first();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->userRepository->model());

        $input['roles'] = isset($input['roles']) ? $input['roles'] : [];
        $input['password'] = Hash::make($input['password']);
        $input['api_token'] = str_random(60);

        try {
            if ($role->name != 'admin') {
                $nextFolio = $this->roleFolioRepository->where('role_id', $role->id)->first();
                $input['key_id'] = $nextFolio->prefix . str_pad($nextFolio->next + 1, 4, "0", STR_PAD_LEFT);
            }
            $user = $this->userRepository->create($input);
            $user->syncRoles($input['roles']);
            $user->customFieldsValues()->createMany(getCustomFieldsValues($customFields, $request));

            //Next role folio
            if ($role) {
                $nextFolio->next = $nextFolio->next + 1;
                $nextFolio->save();
            }

            if (isset($input['avatar']) && $input['avatar']) {
                $cacheUpload = $this->uploadRepository->getByUuid($input['avatar']);
                $mediaItem = $cacheUpload->getMedia('avatar')->first();
                $mediaItem->copy($user, 'avatar');
            }
            event(new UserRoleChangedEvent($user));
        } catch (ValidatorException $e) {
            Flash::error($e->getMessage());
        }

        Flash::success('saved successfully.');

        return redirect(route('users.manager.index'));
    }

    public function loginAsUser(Request $request, $id)
    {
        $user = $this->userRepository->findWithoutFail($id);
        if (empty($user)) {
            Flash::error('User not found');
            return redirect(route('users.manager.index'));
        }
        auth()->login($user, true);
        if (auth()->id() !== $user->id) {
            Flash::error('User not found');
        }
        return redirect(route('users.profile'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $user = $this->userRepository->findWithoutFail($id);

        if (empty($user)) {
            Flash::error('User not found');

            return redirect(route('users.manager.index'));
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (!auth()->user()->hasRole('admin') && $id != auth()->id()) {
            Flash::error('Permission denied');
            return redirect(route('users.manager.index'));
        }
        $user = $this->userRepository->findWithoutFail($id);
        unset($user->password);
        $html = false;
        $role = $this->roleRepository->where('name', 'manager')->pluck('name', 'name');
        $rolesSelected = $user->getRoleNames()->toArray();
        $vehicle = $this->vehicleRepository->pluck('type', 'id');
        $customFieldsValues = $user->customFieldsValues()->with('customField')->get();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->userRepository->model());
        $hasCustomField = in_array($this->userRepository->model(), setting('custom_field_models', []));
        if ($hasCustomField) {
            $html = generateCustomField($customFields, $customFieldsValues);
        }

        if (empty($user)) {
            Flash::error('User not found');

            return redirect(route('users.manager.index'));
        }
        return view('settings.users.manager.edit')
            ->with('user', $user)->with("role", $role)
            ->with("rolesSelected", $rolesSelected)
            ->with("customFields", $html)
            ->with('vehicle', $vehicle);
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
        if (env('APP_DEMO', false)) {
            Flash::warning('This is only demo app you can\'t change this section ');
            return redirect(route('users.profile'));
        }
        if (!auth()->user()->hasRole('admin') && $id != auth()->id()) {
            Flash::error('Permission denied');
            return redirect(route('users.profile'));
        }

        $user = $this->userRepository->findWithoutFail($id);

        //Get role_id by role name
        $role = $this->roleRepository->where('name', $request->roles)->first();

        if (empty($user)) {
            Flash::error('User not found');
            return redirect(route('users.profile'));
        }
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->userRepository->model());

        $input = $request->all();
        if (!auth()->user()->can('permissions.index')) {
            unset($input['roles']);
        } else {
            $input['roles'] = isset($input['roles']) ? $input['roles'] : [];
        }
        if (empty($input['password'])) {
            unset($input['password']);
        } else {
            $input['password'] = Hash::make($input['password']);
        }
        try {

            if ($role->name != 'admin') {
                $nextFolio = $this->roleFolioRepository->where('role_id', $role->id)->first();
                $input['key_id'] = $nextFolio->prefix . str_pad($nextFolio->next + 1, 4, "0", STR_PAD_LEFT);
            }

            $user = $this->userRepository->update($input, $id);


            if (empty($user)) {
                Flash::error('User not found');
                return redirect(route('users.profile'));
            }
            if (isset($input['avatar']) && $input['avatar']) {
                $cacheUpload = $this->uploadRepository->getByUuid($input['avatar']);
                $mediaItem = $cacheUpload->getMedia('avatar')->first();
                $mediaItem->copy($user, 'avatar');
            }
            if (auth()->user()->can('permissions.index')) {
                $user->syncRoles($input['roles']);
            }
            foreach (getCustomFieldsValues($customFields, $request) as $value) {
                $user->customFieldsValues()
                    ->updateOrCreate(['custom_field_id' => $value['custom_field_id']], $value);
            }

            //Next role folio
            if ($role && $user) {
                $nextFolio->next = $nextFolio->next + 1;
                $nextFolio->save();
            }

            event(new UserRoleChangedEvent($user));
        } catch (ValidatorException $e) {
            Flash::error($e->getMessage());
        }


        Flash::success('User updated successfully.');

        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (env('APP_DEMO', false)) {
            Flash::warning('This is only demo app you can\'t change this section ');
            return redirect(route('users.manager.index'));
        }
        $user = $this->userRepository->findWithoutFail($id);

        if (empty($user)) {
            Flash::error('User not found');

            return redirect(route('users.manager.index'));
        }

        $this->userRepository->delete($id);

        Flash::success('User deleted successfully.');

        return redirect(route('users.manager.index'));
    }

    /**
     * Remove Media of User
     * @param Request $request
     */
    public function removeMedia(Request $request)
    {
        if (env('APP_DEMO', false)) {
            Flash::warning('This is only demo app you can\'t change this section ');
        } else {
            if (auth()->user()->can('medias.delete')) {
                $input = $request->all();
                $user = $this->userRepository->findWithoutFail($input['id']);
                try {
                    if ($user->hasMedia($input['collection'])) {
                        $user->getFirstMedia($input['collection'])->delete();
                    }
                } catch (\Exception $e) {
                    Log::error($e->getMessage());
                }
            }
        }
    }

    public function nextFolio($role)
    {
    }
}

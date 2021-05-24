<?php

namespace App\Http\Controllers;
use App\DataTables\VehicleDataTable;
use App\Http\Requests\CreateVehicleRequest;
use App\Http\Requests\UpdateVehicleRequest;
use App\Repositories\VehicleRepository;
use App\Repositories\CustomFieldRepository;
use App\Repositories\UploadRepository;
use Flash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Prettus\Validator\Exceptions\ValidatorException;

class VehicleController extends Controller
{
    /** @var  VehicleRepository */
     private $vehicleRepository;
    
    /**
     * @var CustomFieldRepository
     */
    private $customFieldRepository;

    /**
     * @var UploadRepository
     */
    private $uploadRepository;

    public function __construct(VehicleRepository $vehicleRepo, CustomFieldRepository $customFieldRepo, UploadRepository $uploadRepo)
    {
        parent::__construct();
        $this->vehicleRepository = $vehicleRepo;
        $this->customFieldRepository = $customFieldRepo;
        $this->uploadRepository = $uploadRepo;
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(VehicleDataTable $vehicleDataTable)
    {
        return $vehicleDataTable->render('vehicles.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $hasCustomField = in_array($this->vehicleRepository->model(), setting('custom_field_models', []));
        if ($hasCustomField) {
            $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->vehicleRepository->model());
            $html = generateCustomField($customFields);
        }
        return view('vehicles.create')->with("customFields", isset($html) ? $html : false);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateVehicleRequest $request)
    {
        $input = $request->all();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->vehicleRepository->model());
        try {
            $vehicle = $this->vehicleRepository->create($input);
            $vehicle->customFieldsValues()->createMany(getCustomFieldsValues($customFields, $request));
            if (isset($input['image']) && $input['image']) {
                $cacheUpload = $this->uploadRepository->getByUuid($input['image']);
                $mediaItem = $cacheUpload->getMedia('image')->first();
                $mediaItem->copy($vehicle, 'image');
            }
        } catch (ValidatorException $e) {
            Flash::error($e->getMessage());
        }

        Flash::success(__('lang.saved_successfully', ['operator' => __('lang.vehicle')]));
 
        return redirect(route('vehicles.index'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $vehicle = $this->vehicleRepository->findWithoutFail($id);

        if (empty($vehicle)) {
            Flash::error('Vehicle not found');

            return redirect(route('vehicles.index'));
        }

        return view('vehicles.show')->with('vehicle', $vehicle);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $vehicle = $this->vehicleRepository->findWithoutFail($id);


        if (empty($vehicle)) {
            Flash::error(__('lang.not_found', ['operator' => __('lang.vehicle')]));

            return redirect(route('vehicles.index'));
        }
        $customFieldsValues = $vehicle->customFieldsValues()->with('customField')->get();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->vehicleRepository->model());
        $hasCustomField = in_array($this->vehicleRepository->model(), setting('custom_field_models', []));
        if ($hasCustomField) {
            $html = generateCustomField($customFields, $customFieldsValues);
        }

        return view('vehicles.edit')->with('vehicle', $vehicle)->with("customFields", isset($html) ? $html : false);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update($id, UpdateVehicleRequest $request)
    {
        $vehicle = $this->vehicleRepository->findWithoutFail($id);

        if (empty($vehicle)) {
            Flash::error('Vehicle not found');
            return redirect(route('vehicles.index'));
        }
        $input = $request->all();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->vehicleRepository->model());
        try {
            $vehicle = $this->vehicleRepository->update($input, $id);

            if (isset($input['image']) && $input['image']) {
                $cacheUpload = $this->uploadRepository->getByUuid($input['image']);
                $mediaItem = $cacheUpload->getMedia('image')->first();
                $mediaItem->copy($vehicle, 'image');
            }
            foreach (getCustomFieldsValues($customFields, $request) as $value) {
                $vehicle->customFieldsValues()
                    ->updateOrCreate(['custom_field_id' => $value['custom_field_id']], $value);
            }
        } catch (ValidatorException $e) {
            Flash::error($e->getMessage());
        }

        Flash::success(__('lang.updated_successfully', ['operator' => __('lang.vehicle')]));

        return redirect(route('vehicles.index'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $vehicle = $this->vehicleRepository->findWithoutFail($id);

        if (empty($vehicle)) {
            Flash::error('Vehicle not found');

            return redirect(route('vehicles.index'));
        }

        $this->vehicleRepository->delete($id);

        Flash::success(__('lang.deleted_successfully', ['operator' => __('lang.vehicle')]));

        return redirect(route('vehicles.index'));
    }

     /**
     * Remove Media of Vehicle
     * @param Request $request
     */
    public function removeMedia(Request $request)
    {
        $input = $request->all();
        $vehicle = $this->vehicleRepository->findWithoutFail($input['id']);
        try {
            if ($vehicle->hasMedia($input['collection'])) {
                $vehicle->getFirstMedia($input['collection'])->delete();
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
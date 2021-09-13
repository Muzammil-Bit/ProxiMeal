<?php

namespace App\Http\Controllers\API;


use App\Models\ExtraGroup;
use App\Repositories\ExtraGroupRepository;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use InfyOm\Generator\Criteria\LimitOffsetCriteria;
use Prettus\Repository\Criteria\RequestCriteria;
use Illuminate\Support\Facades\Response;
use Prettus\Repository\Exceptions\RepositoryException;
use Flash;
use Prettus\Validator\Exceptions\ValidatorException;
use App\Http\Requests\CreateExtraGroupRequest;
use App\Repositories\CustomFieldRepository;
use App\Http\Requests\UpdateExtraGroupRequest;

/**
 * Class ExtraGroupController
 * @package App\Http\Controllers\API
 */

class ExtraGroupAPIController extends Controller
{
     /** @var  ExtraGroupRepository */
    private $extraGroupRepository;

    /**
     * @var CustomFieldRepository
     */
    private $customFieldRepository;



    public function __construct(ExtraGroupRepository $extraGroupRepo, CustomFieldRepository $customFieldRepo)
    {
        parent::__construct();
        $this->extraGroupRepository = $extraGroupRepo;
        $this->customFieldRepository = $customFieldRepo;
    }

    /**
     * Display a listing of the ExtraGroup.
     * GET|HEAD /extraGroups
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try{
            $this->extraGroupRepository->pushCriteria(new RequestCriteria($request));
            $this->extraGroupRepository->pushCriteria(new LimitOffsetCriteria($request));
        } catch (RepositoryException $e) {
            return $this->sendError($e->getMessage());
        }
        $extraGroups = $this->extraGroupRepository->all();

        return $this->sendResponse($extraGroups->toArray(), 'Extra Groups retrieved successfully');
    }
    
    
    /**
     * Store a newly created extraGroup in storage.
     *
     * @param CreateextraGroupRequest $request
     *
     * @return Response
     */
    public function store(CreateExtraGroupRequest $request)
    {
        $input = $request->all();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->extraGroupRepository->model());
        try {
            $extraGroup = $this->extraGroupRepository->create($input);
            $extraGroup->customFieldsValues()->createMany(getCustomFieldsValues($customFields, $request));
        } catch (ValidatorException $e) {
            return Response(['success' => false, 'data' => $e->getMessage()]);
        }


        return Response(['success' => true, 'data' => $extraGroup]);
    }



    /**
     * Update the specified ExtraGroup in storage.
     *
     * @param  int              $id
     * @param UpdateExtraGroupRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateExtraGroupRequest $request)
    {
        $extraGroup = $this->extraGroupRepository->findWithoutFail($id);

        if (empty($extraGroup)) {
            return Response(['success' => false, 'data' => "Not found."]);
        }
        $input = $request->all();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->extraGroupRepository->model());
        try {
            $extraGroup = $this->extraGroupRepository->update($input, $id);


            foreach (getCustomFieldsValues($customFields, $request) as $value) {
                $extraGroup->customFieldsValues()
                    ->updateOrCreate(['custom_field_id' => $value['custom_field_id']], $value);
            }
        } catch (ValidatorException $e) {
            return Response(['success' => false, 'data' => $e->getMessage()]);
        }


        return Response(['success' => true, 'data' => $extraGroup]);
    }
    

    /**
     * Display the specified ExtraGroup.
     * GET|HEAD /extraGroups/{id}
     *
     * @param  int $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        /** @var ExtraGroup $extraGroup */
        if (!empty($this->extraGroupRepository)) {
            $extraGroup = $this->extraGroupRepository->findWithoutFail($id);
        }

        if (empty($extraGroup)) {
            return $this->sendError('Extra Group not found');
        }

        return $this->sendResponse($extraGroup->toArray(), 'Extra Group retrieved successfully');
    }
    
    /**
     * Remove the specified ExtraGroup from storage.
     *
     * @param  int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $extraGroup = $this->extraGroupRepository->findWithoutFail($id);

        if (empty($extraGroup)) {
            return $this->sendError('Extra Group not found');
        }

        $this->extraGroupRepository->delete($id);

        return Response(['success' => true, 'message' => "DELETED SUCCESSFULLY."]);
    }
}

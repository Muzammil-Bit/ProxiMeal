<?php

namespace App\DataTables;

use App\Models\Vehicle;
use App\Models\CustomField;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Barryvdh\DomPDF\Facade as PDF;

class VehicleDataTable extends DataTable
{
    /**
     * custom fields columns
     * @var array
     */
    public static $customFields = [];

    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return \Yajra\DataTables\DataTableAbstract
     */
    public function dataTable($query)
    {
        $dataTable = new EloquentDataTable($query);
        $columns = array_column($this->getColumns(), 'data');
        $dataTable = $dataTable
            ->editColumn('updated_at', function ($vehicle) {
                return getDateColumn($vehicle, 'updated_at');
            })
            ->editColumn('price', function ($vehicle) {
                return $vehicle->price . " Ghc";
            })
            ->editColumn('free_range', function ($vehicle) {
                return $vehicle->free_range . " Km";
            })
            ->editColumn('maximum_range', function ($vehicle) {
                return $vehicle->maximum_range . ' km';
            })
            ->editColumn('maximum_carrying_capacity', function ($vehicle) {
                return $vehicle->maximum_carrying_capacity . ' Kg';
            })
            ->addColumn('action', 'vehicles.datatables_actions')
            ->editColumn('additional_cost_per_km_after_distance_limit', function ($vehicle) {
                return $vehicle->additional_cost_per_km_after_distance_limit . " Ghc";
            })
            ->rawColumns(array_merge($columns, ['action']));

        return $dataTable;
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\Post $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Vehicle $model)
    {
        return $model->newQuery();
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        return $this->builder()
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->addAction(['width' => '80px', 'printable' => false, 'responsivePriority' => '100'])
            ->parameters(array_merge(
                config('datatables-buttons.parameters'),
                [
                    'language' => json_decode(
                        file_get_contents(
                            base_path('resources/lang/' . app()->getLocale() . '/datatable.json')
                        ),
                        true
                    )
                ]
            ));
    }

    /**
     * Get columns.
     *
     * @return array
     */
    protected function getColumns()
    {
        $columns = [
            [
                'data' => 'type',
                'title' => trans('lang.vehicle_type')
            ],
            [
                'data' => 'price',
                'title' => trans('lang.vehicle_price')
            ],
            // !!! Muzammil Hussain
            [
                'data' => 'free_range',
                'title' => trans('Free Range')
            ],
            [
                'data' => 'maximum_range',
                'title' => trans('Maximum Range')
            ],
            [
                'data' => 'maximum_carrying_capacity',
                'title' => trans('Maximum Capacity')
            ],
            [
                'data' => 'additional_cost_per_km_after_distance_limit',
                'title' => trans('Additional Distance Cost After Limit')
            ],
            // !!!***********************************!!!
            [
                'data' => 'updated_at',
                'title' => trans('lang.vehicle_updated_at'),
                'searchable' => false,
            ]
        ];

        $hasCustomField = in_array(Category::class, setting('custom_field_models', []));
        if ($hasCustomField) {
            $customFieldsCollection = CustomField::where('custom_field_model', Category::class)->where('in_table', '=', true)->get();
            foreach ($customFieldsCollection as $key => $field) {
                array_splice($columns, $field->order - 1, 0, [[
                    'data' => 'custom_fields.' . $field->name . '.view',
                    'title' => trans('lang.category_' . $field->name),
                    'orderable' => false,
                    'searchable' => false,
                ]]);
            }
        }

        return $columns;
    }

    /**
     * Export PDF using DOMPDF
     * @return mixed
     */
    public function pdf()
    {
        $data = $this->getDataForPrint();
        $pdf = PDF::loadView($this->printPreview, compact('data'));
        return $pdf->download($this->filename() . '.pdf');
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'rolesdatatable_' . time();
    }
}

<?php

namespace App\DataTables;

use App\Models\User;
use App\Transaction;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Barryvdh\DomPDF\Facade as PDF;

class WalletDataTable extends DataTable
{
    /**
     * custom fields columns
     * @var array
     */
    // public static $customFields = [];

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
            ->editColumn('user', function ($query) {
                return User::find($query->user_id)->name;
            })
            ->editColumn('amount', function ($query) {
                return setting('default_currency') . "  " . Transaction::where('user_id', $query->user_id)->sum('value');
            })->editColumn('action', function ($query) {
                return $this->actions(User::find($query->user_id)->id);
            });
        // ->rawColumns(array_merge($columns, ['action']));

        return $dataTable;
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\Post $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Transaction $model)
    {
        return $model->newQuery()->select('transactions.*')->groupBy('user_id');
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
            ->addAction(['title' => trans('lang.actions'), 'width' => '80px', 'printable' => false, 'responsivePriority' => '100'])
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
                'data' => 'id',
                'title' => trans('ID'),

            ],
            [
                'data' => 'user',
                'title' => trans('User Name'),

            ],
            [
                'data' => 'amount',
                'title' => trans('Current Wallet Amount'),

            ],
        ];
        return $columns;
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'walletTable_' . time();
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
    public function actions($id)
    {
        return
            "<a data-toggle='tooltip' data-placement='bottom' href='/wallets/$id/transaction' class='btn btn-link'><i class='fa fa-eye'></i>
            View All Transactions
            </a>";
    }
}

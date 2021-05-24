<div class='btn-group btn-group-sm'>
    @can('vehicles.show')
    <a data-toggle="tooltip" data-placement="bottom" title="{{trans('lang.view_details')}}" href="{{ route('vehicles.show', $id) }}" class='btn btn-link'>
        <i class="fa fa-eye"></i>
    </a>
    @endcan

    @can('vehicles.edit')
    <a data-toggle="tooltip" data-placement="bottom" title="{{trans('lang.vehicle_edit')}}" href="{{ route('vehicles.edit', $id) }}" class='btn btn-link'>
        <i class="fa fa-edit"></i>
    </a>
    @endcan

    @can('vehicles.destroy')
    {!! Form::open(['route' => ['vehicles.destroy', $id], 'method' => 'delete']) !!}
    {!! Form::button('<i class="fa fa-trash"></i>', [
    'type' => 'submit',
    'class' => 'btn btn-link text-danger',
    'onclick' => "return confirm('Are you sure?')"
    ]) !!}
    {!! Form::close() !!}
    @endcan
</div>

@extends('layouts.app')


@section('content')
<!-- Content Header (Page header) -->
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark">{{trans('Wallets')}}<small class="ml-3 mr-3">|</small><small>{{trans('Wallet Management')}}</small></h1>
      </div><!-- /.col -->
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="{{url('/dashboard')}}"><i class="fa fa-dashboard"></i> {{trans('lang.dashboard')}}</a></li>
          <li class="breadcrumb-item"><a href="{!! route('wallets.index') !!}">{{trans('Wallets')}}</a>
          </li>
          <li class="breadcrumb-item active">{{trans('User\'s Wallets')}}</li>
        </ol>
      </div><!-- /.col -->
    </div><!-- /.row -->
  </div><!-- /.container-fluid -->
</div>



<!-- /.content-header -->
<div class="content">
  <div class="clearfix"></div>
  @include('flash::message')
  @include('adminlte-templates::common.errors')
  <div class="clearfix"></div>
  <div class="card">
    <div class="card-header">
      <ul class="nav nav-tabs align-items-end card-header-tabs w-100">
        @can('wallets.index')
        <li class="nav-item">
          <a class="nav-link" href="{!! route('wallets.index') !!}"><i class="fa fa-list mr-2"></i>{{trans('Wallet List')}}</a>
        </li>
        @endcan
        <li class="nav-item">
          <a class="nav-link" href="{!! url()->current() !!}"><i class="fa fa-plus mr-2"></i>{{trans('Create Transaction')}}</a>
        </li>
        <li class="nav-item">
          <a class="nav-link active" href="{!! url()->current() !!}"><i class="fa fa-plus mr-2"></i>{{trans(App\Models\User::find($transactions[0]->user_id . "'s Transactions")->name)}}</a>
        </li>
      </ul>
    </div>
    <div class="card-body">


      
      <div class="table ">
        <table class="w-100">
          <thead>
            <th>User Name</th>
            <th>Value</th>
            <th>Description</th>
            <th>Created On</th>
          </thead>
          <tbody>
            @foreach ($transactions as $trans)
              <tr>
                <td>{{  $trans->user->name  }}</td>
                <td>{{  $trans->value }}</td>
                <td class="text-center">{{  $trans->description == null || $trans->description == "" ? "-" : $trans->description }}</td>
                <td>{{  $trans->created_at }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>


@endsection


@push('scripts_lib')

@endpush
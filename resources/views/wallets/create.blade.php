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
          <li class="breadcrumb-item active">{{trans('Wallets list')}}</li>
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
            <a class="nav-link active" href="{!! url()->current() !!}"><i class="fa fa-plus mr-2"></i>{{trans('Create Transaction')}}</a>
          </li>
        </ul>
      </div>
      <div class="card-body">


        <form action="{{ route('wallets.store') }}" method="POST">
          @csrf

         <!-- user id field -->
        <div class="form-group">
          <label for="user_id" class="control-label">User :</label>
          <select name="user_id" id="user_id" class="form-control">
            @foreach ($users as $user)
              <option value="{{ $user->id }}" class="d-flex justify-content-between">{{ $user->name }}      ---       {{ App\Transaction::where('user_id', $user->id)->sum('value') }}</option>
            @endforeach
          </select>
        </div>

        
        <div class="form-group">
          <label for="type" class="control-label">Type : </label>
          <select name="type" id="type" class="form-control">
            <option value="plus">CREDIT  -  Add to wallet (+)</option>
            <option value="minus">DEBIT  -  Remove from wallet (-)</option>
          </select>
        </div>

        <div class="form-group ">
          <label for="value" class="control-label text-right">Value : </label>
          <input type="number" min="0" value="0" name="value" id="value" class="form-control" placeholder="Amount to be added / deducted">
        </div>


        <div class="form-group ">
          <label for="description" class="control-label text-right">Description</label>
          <div>
            <textarea name="description" class="form-control" id="description" cols="30" rows="10"></textarea>
          </div>
        </div>


        <div class="row justify-content-end">
          <button class="btn btn-success">Save Transaction <i class="fa fa-save"></i></button>
        </div>

        </form>
      </div>
    </div>
  </div>



@endsection


@push('scripts_lib')

@endpush
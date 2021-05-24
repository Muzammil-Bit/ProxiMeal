<!-- Id Field -->
<div class="form-group row col-6">
    {!! Form::label('id', 'Id:', ['class' => 'col-3 control-label text-right']) !!}
    <div class="col-9">
      <p>{!! $vehicle->id !!}</p>
    </div>
  </div>
  
  <!-- Brand Field -->
  <div class="form-group row col-6">
    {!! Form::label('brand', 'Brand:', ['class' => 'col-3 control-label text-right']) !!}
    <div class="col-9">
      <p>{!! $vehicle->brand !!}</p>
    </div>
  </div>
  
  <!-- Model Field -->
  <div class="form-group row col-6">
    {!! Form::label('model', 'Model:', ['class' => 'col-3 control-label text-right']) !!}
    <div class="col-9">
      <p>{!! $vehicle->model !!}</p>
    </div>
  </div>

   <!-- Plates Field -->
   <div class="form-group row col-6">
    {!! Form::label('plates', 'Plates:', ['class' => 'col-3 control-label text-right']) !!}
    <div class="col-9">
      <p>{!! $vehicle->plates !!}</p>
    </div>
  </div>
  
  <!-- Image Field -->
  <div class="form-group row col-6">
    {!! Form::label('image', 'Image:', ['class' => 'col-3 control-label text-right']) !!}
    <div class="col-9">
      <p>{!! $vehicle->image !!}</p>
    </div>
  </div>
  
  <!-- Created At Field -->
  <div class="form-group row col-6">
    {!! Form::label('created_at', 'Created At:', ['class' => 'col-3 control-label text-right']) !!}
    <div class="col-9">
      <p>{!! $vehicle->created_at !!}</p>
    </div>
  </div>
  
  <!-- Updated At Field -->
  <div class="form-group row col-6">
    {!! Form::label('updated_at', 'Updated At:', ['class' => 'col-3 control-label text-right']) !!}
    <div class="col-9">
      <p>{!! $vehicle->updated_at !!}</p>
    </div>
  </div>
  
  
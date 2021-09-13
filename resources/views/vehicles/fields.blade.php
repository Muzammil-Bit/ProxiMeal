@if($customFields)
<h5 class="col-12 pb-4">{!! trans('lang.main_fields') !!}</h5>
@endif
<div style="flex: 50%;max-width: 50%;padding: 0 4px;" class="column">
<!-- Brand Field -->
<div class="form-group row ">
  {!! Form::label('type', trans("lang.vehicle_type"), ['class' => 'col-3 control-label text-right']) !!}
  <div class="col-9">
    {!! Form::text('type', null,  ['class' => 'form-control','placeholder'=>  trans("lang.vehicle_type_placeholder")]) !!}
    <div class="form-text text-muted">
      {{ trans("lang.vehicle_brand_help") }}
    </div>
  </div>
</div>

<!-- Model Field -->
<div class="form-group row ">
    {!! Form::label('price', trans("lang.vehicle_price"), ['class' => 'col-3 control-label text-right']) !!}
    <div class="col-9">
      {!! Form::text('price', null,  ['class' => 'form-control','placeholder'=>  trans("lang.vehicle_price_placeholder")]) !!}
      <div class="form-text text-muted">
        {{ trans("lang.vehicle_model_help") }}
      </div>
    </div>
  </div>

    <!-- Free Range Field -->
    <div class="form-group row ">
      {!! Form::label('free_range', trans("Free Range(Km)"), ['class' => 'col-3 control-label text-right']) !!}
      <div class="col-9">
        {!! Form::number('free_range', null,  ['class' => 'form-control','placeholder'=>  trans("Insert Free Range in Km")]) !!}
        <div class="form-text text-muted">
          {{ trans("Maximum Range Km") }}
        </div>
      </div>
    </div>
  
  <!-- Max Range Field -->
  <div class="form-group row ">
      {!! Form::label('maximum_range', trans("Maximum Range(Km)"), ['class' => 'col-3 control-label text-right']) !!}
      <div class="col-9">
        {!! Form::number('maximum_range', null,  ['class' => 'form-control','placeholder'=>  trans("Insert Maximum Range in Km")]) !!}
        <div class="form-text text-muted">
          {{ trans("Maximum Range (Km)") }}
        </div>
      </div>
    </div>
    
    <!-- Max Order Weight Limit Field -->
    <div class="form-group row ">
        {!! Form::label('maximum_carrying_capacity', trans("Maximum Carrying Capacity"), ['class' => 'col-3 control-label text-right']) !!}
        <div class="col-9">
          {!! Form::number('maximum_carrying_capacity', null,  ['class' => 'form-control','placeholder'=>  trans("Maximum Carrying Capacity")]) !!}
          <div class="form-text text-muted">
            {{ trans("Maximum Carrying Capacity") }}
          </div>
        </div>
      </div>


    <!-- Additional Cost -->
    <div class="form-group row ">
        {!! Form::label('additional_cost_per_km_after_distance_limit', trans("Additional Distance Cost after limit per KM"), ['class' => 'col-3 control-label text-right']) !!}
        <div class="col-9">
          {!! Form::number('additional_cost_per_km_after_distance_limit', null,  ['class' => 'form-control','placeholder'=>  trans("Additional Distance Cost after limit per KM")]) !!}
          <div class="form-text text-muted">
            {{ trans("Additional Distance Cost after limit per KM") }}
          </div>
        </div>
      </div>


</div>
<div style="flex: 50%;max-width: 50%;padding: 0 4px;" class="column">

</div>
@if($customFields)
<div class="clearfix"></div>
<div class="col-12 custom-field-container">
  <h5 class="col-12 pb-4">{!! trans('lang.custom_field_plural') !!}</h5>
  {!! $customFields !!}
</div>
@endif
<!-- Submit Field -->
<div class="form-group col-12 text-right">
  <button type="submit" class="btn btn-{{setting('theme_color')}}" ><i class="fa fa-save"></i> {{trans('lang.save')}} {{trans('lang.vehicle')}}</button>
  <a href="{!! route('vehicles.index') !!}" class="btn btn-default"><i class="fa fa-undo"></i> {{trans('lang.cancel')}}</a>
</div>

@extends('themes.default1.layouts.master')
@section('title')
Api Key
@stop
@section('content-header')
<h1>
API Keys
</h1>
  <ol class="breadcrumb">
        <li><a href="{{url('/')}}"><i class="fa fa-dashboard"></i> Home</a></li>
        <li><a href="{{url('settings')}}">Settings</a></li>
         <li class="active">Api Key</li>
      </ol>
@stop
@section('content')
<div class="box box-primary">

    <div class="box-header">
        @if (count($errors) > 0)
        <div class="alert alert-danger">
            <strong>Whoops!</strong> There were some problems with your input.<br><br>
            <ul>
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        @if(Session::has('success'))
        <div class="alert alert-success alert-dismissable">
            <i class="fa fa-check"></i>
            <b>{{Lang::get('message.success')}}!</b>
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            {{Session::get('success')}}
        </div>
        @endif
        <!-- fail message -->
        @if(Session::has('fails'))
        <div class="alert alert-danger alert-dismissable">
            <i class="fa fa-ban"></i>
            <b>{{Lang::get('message.alert')}}!</b> {{Lang::get('message.failed')}}.
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            {{Session::get('fails')}}
        </div>
        @endif
    </div>
     <div  class="box-body">
 
        {!! Form::model($model,['url'=>'apikeys','method'=>'patch']) !!}
          <tr>
         <h3 class="box-title" style="margin-top:0px;margin-left: 10px;">{{Lang::get('message.system-api')}}</h3>
       <button type="submit" class="btn btn-primary pull-right" id="submit" style="margin-top:-40px;
                        margin-right:15px;"><i class="fa fa-floppy-o">&nbsp;&nbsp;</i>{!!Lang::get('message.save')!!}</button>
         </tr>

   

       

            <div class="col-md-12">



                <div class="row">

                    <div class="col-md-6 form-group {{ $errors->has('username') ? 'has-error' : '' }}">
                        <!-- first name -->
                        {!! Form::label('rzp_key',Lang::get('message.rzp_key')) !!}
                        {!! Form::text('rzp_key',null,['class' => 'form-control']) !!}

                    </div>

                    <div class="col-md-6 form-group {{ $errors->has('password') ? 'has-error' : '' }}">
                        <!-- last name -->
                        {!! Form::label('rzp_secret',Lang::get('message.rzp_secret')) !!}
                        {!! Form::text('rzp_secret',null,['class' => 'form-control']) !!}

                    </div>



                </div>



                <div class="row">

                    <div class="col-md-6 form-group {{ $errors->has('client_id') ? 'has-error' : '' }}">
                        <!-- first name -->
                        {!! Form::label('apilayer_key',Lang::get('message.apilayer')) !!}
                        {!! Form::text('apilayer_key',null,['class' => 'form-control']) !!}

                    </div>

                    <div class="col-md-6 form-group {{ $errors->has('client_secret') ? 'has-error' : '' }}">
                        <!-- last name -->
                        {!! Form::label('zoho_api_key',Lang::get('message.zoho_key')) !!}
                        {!! Form::text('zoho_api_key',null,['class' => 'form-control']) !!}

                    </div>

                </div>

                 <div class="row">

                    <div class="col-md-6 form-group {{ $errors->has('client_id') ? 'has-error' : '' }}">
                        <!-- first name -->
                        {!! Form::label('msg91_auth_key',Lang::get('message.msg91key')) !!}
                        {!! Form::text('msg91_auth_key',null,['class' => 'form-control']) !!}

                    </div>

                    

                

                    <div class="col-md-6 form-group {{ $errors->has('twitter_consumer_key') ? 'has-error' : '' }}">
                        <!-- first name -->
                        {!! Form::label('twitter_consumer_key',Lang::get('message.twitter_consumer_key')) !!}
                        {!! Form::text('twitter_consumer_key',null,['class' => 'form-control']) !!}

                    </div>

                    

               
                    <div class="col-md-6 form-group {{ $errors->has('twitter_consumer_secret') ? 'has-error' : '' }}">
                        <!-- first name -->
                        {!! Form::label('twitter_consumer_secret',Lang::get('message.twitter_consumer_secret')) !!}
                        {!! Form::text('twitter_consumer_secret',null,['class' => 'form-control']) !!}

                    </div>

                    

               

                    <div class="col-md-6 form-group {{ $errors->has('twitter_access_token') ? 'has-error' : '' }}">
                        <!-- first name -->
                        {!! Form::label('twitter_access_token',Lang::get('message.twitter_access_token')) !!}
                        {!! Form::text('twitter_access_token',null,['class' => 'form-control']) !!}

                    </div>

                    

               
                    <div class="col-md-6 form-group {{ $errors->has('twitter_access_token_secret') ? 'has-error' : '' }}">
                        <!-- first name -->
                        {!! Form::label('access_tooken_secret',Lang::get('message.twitter_access_tooken_secret')) !!}
                        {!! Form::text('access_tooken_secret',null,['class' => 'form-control']) !!}

                    </div>

                    

                </div>




            </div>

       

    </div>

</div>


{!! Form::close() !!}
@stop
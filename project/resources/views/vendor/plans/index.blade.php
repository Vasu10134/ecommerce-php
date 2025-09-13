@extends('layouts.vendor')
@section('content')


<div class="content-area">

    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                    <h4 class="heading">{{ $langg->lang237 }}</h4>
                    <ul class="links">
                        <li>
                            <a href="{{ route('vendor-dashboard') }}">{{ $langg->lang441 }} </a>
                        </li>
                        <li>
                            <a href="{{ route('vendor-package') }}">{{ $langg->lang237 }}</a>
                        </li>
                    </ul>
            </div>
        </div>
    </div>
    @php
        
    @endphp

<section class="user-dashbord">

      <div class="row">
        <div class="col-lg-12">
            <div class="user-profile-details">
                @include('includes.form-success')
                    <div class="row">
                        @foreach($subs as $sub)
                            <div class="col-lg-4">
                                <div class="elegant-pricing-tables style-2 text-center">
                                    <div class="pricing-head">
                                        <h3>{{ $sub->title }}</h3>
                                        @if($sub->price  == 0)
                                            <span class="price">
                                            <span class="price-digit">{{ $langg->lang402 }}</span>
                                            </span>
                                        @else
                                            <span class="price">
                                                <sup>{{ $curr->sign }}</sup>
                                                <span class="price-digit"> {{$sub->convertedUserPrice($sub->price)}}</span><br>
                                                <span class="price-month">{{ $sub->days }} {{ $langg->lang403 }}</span>
                                            </span>
                                        @endif
                                    </div>
                                    <div class="pricing-detail">
                                        {!! $sub->details !!}
                                    </div>
                                    @if(!empty($package))
                                        @if($package->subscription_id == $sub->id)
                                            <a href="javascript:;" class="btn btn-default">{{ $langg->lang404 }}</a>
                                            <br>
                                            @if(Carbon\Carbon::now()->format('Y-m-d') > $user->date)
                                            <small class="hover-white">{{ $langg->lang405 }} {{ date('d/m/Y',strtotime($user->date)) }}</small>
                                            @else
                                            <small class="hover-white">{{ $langg->lang406 }} {{ date('d/m/Y',strtotime($user->date)) }}</small>
                                            @endif
                                             <a href="{{route('vendor-vendor-request',$sub->id)}}" class="hover-white"><u>{{ $langg->lang407 }}</u></a>
                                        @else
                                            <a href="{{route('vendor-vendor-request',$sub->id)}}" class="btn btn-default">{{ $langg->lang408 }}</a>
                                            <br><small>&nbsp;</small>
                                        @endif
                                    @else
                                        <a href="{{route('vendor-vendor-request',$sub->id)}}" class="btn btn-default">{{ $langg->lang408 }}</a>
                                        <br><small>&nbsp;</small>
                                    @endif
             
                                    </div>
                            </div>
                                @endforeach
                        </div>
                    </div>
                </div>
      </div>

  </section>

</div>

@endsection
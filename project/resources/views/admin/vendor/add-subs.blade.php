@extends('layouts.load')
@section('content')

						<div class="content-area">
							<div class="add-product-content">
								<div class="row">
									<div class="col-lg-12">
										<div class="product-description">
											<div class="body-area">

                                            @include('includes.admin.form-error') 
                                            
                                            <form id="geniusformdata" action="{{route('admin-vendor-subs-store',$data->id)}}" method="POST" enctype="multipart/form-data">
                                                
                                                {{csrf_field()}}
                                                
												<div class="row">
													<div class="col-lg-4">
														<div class="left-area">
																<h4 class="heading">{{ __('Current Plan') }} :</h4>
														</div>
													</div>
                                                    <div class="col-lg-7">
                                                            <h5 class="heading title">{{ $data->subscribes()->orderBy('id','desc')->first() ? $data->subscribes()->orderBy('id','desc')->first()->title : 'No Plan' }} </h5>
                                                    </div>
												</div>

												<div class="row">
													<div class="col-lg-4">
														<div class="left-area">
																<h4 class="heading">{{ __('Choose Plan') }} :</h4>
														</div>
													</div>
                                                    <div class="col-lg-7">
                                                        <select name="subs_id" required="">
                                                            @foreach(DB::table('subscriptions')->get() as $subdata)
                                                                <option value="{{ $subdata->id }}">{{ $subdata->title }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
												</div>

												<div class="row">
													<div class="col-lg-4">
														<div class="left-area">
															
														</div>
													</div>
													<div class="col-lg-7">
														<button class="addProductSubmit-btn" type="submit">{{ __('Submit') }}</button>
													</div>
												</div>
											</form>

											</div>
										</div>
									</div>
								</div>
							</div>
						</div>

@endsection
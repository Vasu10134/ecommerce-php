<ul>

    @foreach($categories as $category)

    <li class="{{count($category->subs) > 0 ? 'dropdown_list':''}} {{ $loop->index >= 14 ? 'rx-child' : '' }}">
    @if(count($category->subs) > 0)
        <div class="img">
            <img loading="lazy" src="{{ asset('assets/images/categories/'.$category->photo) }}" alt="">
        </div>
        <div class="link-area">
            <span><a href="{{ route('front.category',$category->slug) }}">{{ $category->name }}</a></span>
            <a href="javascript:;">
                <i class="fa fa-angle-right" aria-hidden="true"></i>
            </a>
        </div>

    @else
        <a href="{{ route('front.category',$category->slug) }}"><img loading="lazy" src="{{ asset('assets/images/categories/'.$category->photo) }}"> {{ $category->name }}</a>

    @endif
        @if(count($category->subs) > 0)


        <ul class="{{ $category->subs()->withCount('childs')->get()->sum('childs_count') > 0 ? 'categories_mega_menu' : 'categories_mega_menu column_1' }}">
            @foreach($category->subs()->whereStatus(1)->get() as $subcat)
                <li>
                    <a href="{{ route('front.subcat',['slug1' => $category->slug, 'slug2' => $subcat->slug]) }}">{{$subcat->name}}</a>
                    @if(count($subcat->childs) > 0)
                        <div class="categorie_sub_menu">
                            <ul>
                                @foreach($subcat->childs()->whereStatus(1)->get() as $childcat)
                                <li><a href="{{ route('front.childcat',['slug1' => $category->slug, 'slug2' => $subcat->slug, 'slug3' => $childcat->slug]) }}">{{$childcat->name}}</a></li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </li>
            @endforeach
        </ul>

        @endif

        </li>

        @if($loop->index == 14)
            <li>
            <a href="{{ route('front.categories') }}"><i class="fas fa-plus"></i> {{ __('See All Categories') }} </a>
            </li>
            @break
        @endif


        @endforeach

</ul>

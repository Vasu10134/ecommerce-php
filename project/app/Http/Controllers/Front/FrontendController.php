<?php

namespace App\Http\Controllers\Front;

use App\Classes\GeniusMailer;
use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\Category;
use App\Models\Counter;
use App\Models\Generalsetting;
use App\Models\Order;
use App\Models\Product;
use App\Models\Faq;
use App\Models\Subscriber;
use App\Models\User;
use App\Models\Page;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use App\Models\Currency;
use App\Models\Language;

class FrontendController extends Controller
{
    public $langg;
    
    public function __construct()
    {
        $this->auth_guests();
        
        // Language handling
        if (Session::has('language')) 
        {
            $data = DB::table('languages')->find(Session::get('language'));
            $langPath = public_path().'/assets/languages/'.$data->file;
            if (!file_exists($langPath)) {
                $langPath = str_replace('project','',base_path()).'assets/languages/'.$data->file;
            }
            $data_results = file_exists($langPath) ? file_get_contents($langPath) : '{}';
            $this->langg = json_decode($data_results);
        }
        else
        {
            $data = DB::table('languages')->where('is_default','=',1)->first();
            $langPath = public_path().'/assets/languages/'.$data->file;
            if (!file_exists($langPath)) {
                $langPath = str_replace('project','',base_path()).'assets/languages/'.$data->file;
            }
            $data_results = file_exists($langPath) ? file_get_contents($langPath) : '{}';
            $this->langg = json_decode($data_results);
        }
        if(isset($_SERVER['HTTP_REFERER'])){
            $referral = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
            if ($referral != $_SERVER['SERVER_NAME']){
                $brwsr = Counter::where('type','browser')->where('referral',$this->getOS());
                if($brwsr->count() > 0){
                    $brwsr = $brwsr->first();
                    $tbrwsr['total_count']= $brwsr->total_count + 1;
                    $brwsr->update($tbrwsr);
                } else {
                    $newbrws = new Counter();
                    $newbrws['referral']= $this->getOS();
                    $newbrws['type']= "browser";
                    $newbrws['total_count']= 1;
                    $newbrws->save();
                }

                $count = Counter::where('referral',$referral);
                if($count->count() > 0){
                    $counts = $count->first();
                    $tcount['total_count']= $counts->total_count + 1;
                    $counts->update($tcount);
                } else {
                    $newcount = new Counter();
                    $newcount['referral']= $referral;
                    $newcount['total_count']= 1;
                    $newcount->save();
                }
            }
        } else {
            $brwsr = Counter::where('type','browser')->where('referral',$this->getOS());
            if($brwsr->count() > 0){
                $brwsr = $brwsr->first();
                $tbrwsr['total_count']= $brwsr->total_count + 1;
                $brwsr->update($tbrwsr);
            } else {
                $newbrws = new Counter();
                $newbrws['referral']= $this->getOS();
                $newbrws['type']= "browser";
                $newbrws['total_count']= 1;
                $newbrws->save();
            }
        }
    }

    public function getOS() {
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        if ($user_agent === '') {
            return 'CLI';
        }
        $os_platform = "Unknown OS Platform";
        $os_array = array(
            '/windows nt 10/i'     =>  'Windows 10',
            '/windows nt 6.3/i'    =>  'Windows 8.1',
            '/windows nt 6.2/i'    =>  'Windows 8',
            '/windows nt 6.1/i'    =>  'Windows 7',
            '/windows nt 6.0/i'    =>  'Windows Vista',
            '/windows nt 5.2/i'    =>  'Windows Server 2003/XP x64',
            '/windows nt 5.1/i'    =>  'Windows XP',
            '/windows xp/i'        =>  'Windows XP',
            '/windows nt 5.0/i'    =>  'Windows 2000',
            '/windows me/i'        =>  'Windows ME',
            '/win98/i'             =>  'Windows 98',
            '/win95/i'             =>  'Windows 95',
            '/win16/i'             =>  'Windows 3.11',
            '/macintosh|mac os x/i'=>  'Mac OS X',
            '/mac_powerpc/i'       =>  'Mac OS 9',
            '/linux/i'             =>  'Linux',
            '/ubuntu/i'            =>  'Ubuntu',
            '/iphone/i'            =>  'iPhone',
            '/ipod/i'              =>  'iPod',
            '/ipad/i'              =>  'iPad',
            '/android/i'           =>  'Android',
            '/blackberry/i'        =>  'BlackBerry',
            '/webos/i'             =>  'Mobile'
        );

        foreach ($os_array as $regex => $value) {
            if (preg_match($regex, $user_agent)) {
                $os_platform = $value;
            }
        }
        return $os_platform;
    }

    // -------------------------------- HOME PAGE SECTION ----------------------------------------
    public function success(Request $request ,$get){
        return view('front.thank',compact('get'));
    }

    public function index(Request $request)
    {
        $this->code_image();
        $gs = Generalsetting::findOrFail(1);
        if(!empty($request->reff))
        {
            $affilate_user = User::where('affilate_code','=',$request->reff)->first();
            if(!empty($affilate_user) && $gs->is_affilate == 1)
            {
                Session::put('affilate', $affilate_user->id);
                return redirect()->route('front.index');
            }
        }

        $sliders = DB::table('sliders')->get();
        $services = DB::table('services')->where('user_id','=',0)->get();
        $top_small_banners = DB::table('banners')->where('type','=','TopSmall')->get();
        $feature_products =  Product::where('featured','=',1)->where('status','=',1)
            ->when($gs->affilate_product == 0, function($q){
                return $q->where('product_type','=', 'normal');
            })->orderBy('id','desc')->take(8)->get();
        $ps = DB::table('pagesettings')->find(1);

        return view('front.index',compact('ps','sliders','services','top_small_banners','feature_products'))->with('langg', $this->langg);
    }

    public function getCategory()
    {
        $categories = Category::where('status',1)->get();
        return view('load.category',compact('categories'));
    }

    public function extraIndex()
    {
        $gs = Generalsetting::findOrFail(1);
        $bottom_small_banners = DB::table('banners')->where('type','=','BottomSmall')->get();
        $large_banners = DB::table('banners')->where('type','=','Large')->get();
        $ps = DB::table('pagesettings')->find(1);
        $partners = DB::table('partners')->get();
        $discount_products =  Product::where('is_discount','=',1)->where('status','=',1)
            ->when($gs->affilate_product == 0, function($q){ return $q->where('product_type','=', 'normal'); })
            ->orderBy('id','desc')->take(8)->get();
        $best_products = Product::where('best','=',1)->where('status','=',1)
            ->when($gs->affilate_product == 0, function($q){ return $q->where('product_type','=', 'normal'); })
            ->orderBy('id','desc')->take(8)->get();
        $top_products = Product::where('top','=',1)->where('status','=',1)
            ->when($gs->affilate_product == 0, function($q){ return $q->where('product_type','=', 'normal'); })
            ->orderBy('id','desc')->take(8)->get();
        $big_products = Product::where('big','=',1)->where('status','=',1)
            ->when($gs->affilate_product == 0, function($q){ return $q->where('product_type','=', 'normal'); })
            ->orderBy('id','desc')->take(8)->get();
        $hot_products = Product::where('hot','=',1)->where('status','=',1)
            ->when($gs->affilate_product == 0, function($q){ return $q->where('product_type','=', 'normal'); })
            ->orderBy('id','desc')->take(9)->get();
        $latest_products = Product::where('latest','=',1)->where('status','=',1)
            ->when($gs->affilate_product == 0, function($q){ return $q->where('product_type','=', 'normal'); })
            ->orderBy('id','desc')->take(9)->get();
        $trending_products = Product::where('trending','=',1)->where('status','=',1)
            ->when($gs->affilate_product == 0, function($q){ return $q->where('product_type','=', 'normal'); })
            ->orderBy('id','desc')->take(9)->get();
        $sale_products = Product::where('sale','=',1)->where('status','=',1)
            ->when($gs->affilate_product == 0, function($q){ return $q->where('product_type','=', 'normal'); })
            ->orderBy('id','desc')->take(9)->get();

        return view('front.extraindex',compact(
            'ps','large_banners','bottom_small_banners','best_products','top_products',
            'hot_products','latest_products','big_products','trending_products','sale_products',
            'discount_products','partners'
        ))->with('langg', $this->langg);
    }

    function auth_guests(){
        // Removed all MarkuryPost usage since class is missing
    }

    // ------------------------ BLOG, CONTACT, ETC FUNCTIONS REMAIN ------------------------

    public function faq()
    {
        $faqs = Faq::all();
        return view('front.faq', compact('faqs'))->with('langg', $this->langg);
    }

    public function contact()
    {
        $gs = Generalsetting::findOrFail(1);
        $ps = DB::table('pagesettings')->find(1);
        return view('front.contact', compact('ps','gs'))->with('langg', $this->langg);
    }

    public function contactemail(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:191',
            'email' => 'required|email',
            'text' => 'required|string',
        ]);

        $gs = Generalsetting::find(1);

        if($gs && $gs->is_capcha == 1){
            $code = session('captcha_string');
            if(!$code || strcasecmp($code, $request->input('codes')) !== 0){
                return back()->with('unsuccess', 'Please enter a valid captcha code.');
            }
        }

        $ps = DB::table('pagesettings')->where('id','=',1)->first();
        $subject = 'Email From Of '.$request->name;
        $to = $ps ? $ps->contact_email : $request->input('to');
        $name = $request->name;
        $from = $request->email;
        $phone = $request->input('phone');
        $msg = "Name: $name\nEmail: $from\n".
               ($phone ? "Phone: $phone\n" : '').
               "Message: ".$request->text;

        try{
            if($gs && $gs->is_smtp){
                $data = [
                    'to' => $to,
                    'subject' => $subject,
                    'body' => $msg,
                ];
                $mailer = new GeniusMailer();
                $mailer->sendCustomMail($data);
            }else{
                $headers = "From: ".$name."<".$from.">";
                @mail($to,$subject,$msg,$headers);
            }
        }catch(\Throwable $e){
            return back()->with('unsuccess', $e->getMessage());
        }

        return back()->with('success', 'Email Sent Successfully!');
    }

    // ------------------------ NEW PAGE METHOD ------------------------
    public function page($slug)
    {
        $page = Page::where('slug',$slug)->first();
        if(!$page){
            abort(404);
        }
        return view('front.page', compact('page'))->with('langg', $this->langg);
    }

    // ------------------------ BLOG METHODS ------------------------
    public function blog()
    {
        $blogs = Blog::orderBy('created_at', 'desc')->paginate(9);
        $bcats = BlogCategory::all();
        $tags = Blog::pluck('tags')->toArray();
        $tags = array_unique(array_filter(explode(',', implode(',', $tags))));
        
        // Get archives
        $archives = Blog::selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, COUNT(*) as count')
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get()
            ->groupBy(function($item) {
                return date('F Y', mktime(0, 0, 0, $item->month, 1, $item->year));
            });
        
        $gs = Generalsetting::findOrFail(1);
        $ps = DB::table('pagesettings')->find(1);
        
        return view('front.blog', compact('blogs', 'bcats', 'tags', 'archives', 'gs', 'ps'))->with('langg', $this->langg);
    }

    public function blogshow($id)
    {
        $blog = Blog::findOrFail($id);
        
        // Increment view count
        $blog->increment('views');
        
        $bcats = BlogCategory::all();
        $tags = Blog::pluck('tags')->toArray();
        $tags = array_unique(array_filter(explode(',', implode(',', $tags))));
        
        // Get archives
        $archives = Blog::selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, COUNT(*) as count')
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get()
            ->groupBy(function($item) {
                return date('F Y', mktime(0, 0, 0, $item->month, 1, $item->year));
            });
        
        $gs = Generalsetting::findOrFail(1);
        $ps = DB::table('pagesettings')->find(1);
        
        return view('front.blogshow', compact('blog', 'bcats', 'tags', 'archives', 'gs', 'ps'))->with('langg', $this->langg);
    }

    public function blogcategory($slug)
    {
        $bcat = BlogCategory::where('slug', $slug)->firstOrFail();
        $blogs = Blog::where('category_id', $bcat->id)->orderBy('created_at', 'desc')->paginate(9);
        $bcats = BlogCategory::all();
        $tags = Blog::pluck('tags')->toArray();
        $tags = array_unique(array_filter(explode(',', implode(',', $tags))));
        
        // Get archives
        $archives = Blog::selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, COUNT(*) as count')
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get()
            ->groupBy(function($item) {
                return date('F Y', mktime(0, 0, 0, $item->month, 1, $item->year));
            });
        
        $gs = Generalsetting::findOrFail(1);
        $ps = DB::table('pagesettings')->find(1);
        
        return view('front.blog', compact('blogs', 'bcats', 'tags', 'archives', 'gs', 'ps', 'bcat'))->with('langg', $this->langg);
    }

    public function blogtags($slug)
    {
        $blogs = Blog::where('tags', 'like', '%' . $slug . '%')->orderBy('created_at', 'desc')->paginate(9);
        $bcats = BlogCategory::all();
        $tags = Blog::pluck('tags')->toArray();
        $tags = array_unique(array_filter(explode(',', implode(',', $tags))));
        
        // Get archives
        $archives = Blog::selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, COUNT(*) as count')
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get()
            ->groupBy(function($item) {
                return date('F Y', mktime(0, 0, 0, $item->month, 1, $item->year));
            });
        
        $gs = Generalsetting::findOrFail(1);
        $ps = DB::table('pagesettings')->find(1);
        
        return view('front.blog', compact('blogs', 'bcats', 'tags', 'archives', 'gs', 'ps'))->with('langg', $this->langg);
    }

    public function blogsearch(Request $request)
    {
        $search = $request->get('search');
        $blogs = Blog::where('title', 'like', '%' . $search . '%')
            ->orWhere('details', 'like', '%' . $search . '%')
            ->orWhere('tags', 'like', '%' . $search . '%')
            ->orderBy('created_at', 'desc')
            ->paginate(9);
        
        $bcats = BlogCategory::all();
        $tags = Blog::pluck('tags')->toArray();
        $tags = array_unique(array_filter(explode(',', implode(',', $tags))));
        
        // Get archives
        $archives = Blog::selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, COUNT(*) as count')
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get()
            ->groupBy(function($item) {
                return date('F Y', mktime(0, 0, 0, $item->month, 1, $item->year));
            });
        
        $gs = Generalsetting::findOrFail(1);
        $ps = DB::table('pagesettings')->find(1);
        
        return view('front.blog', compact('blogs', 'bcats', 'tags', 'archives', 'gs', 'ps', 'search'))->with('langg', $this->langg);
    }

    public function blogarchive($slug)
    {
        // Parse the archive slug (e.g., "January 2023")
        $date = \DateTime::createFromFormat('F Y', $slug);
        if (!$date) {
            abort(404);
        }
        
        $year = $date->format('Y');
        $month = $date->format('m');
        
        $blogs = Blog::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('created_at', 'desc')
            ->paginate(9);
        
        $bcats = BlogCategory::all();
        $tags = Blog::pluck('tags')->toArray();
        $tags = array_unique(array_filter(explode(',', implode(',', $tags))));
        
        // Get archives
        $archives = Blog::selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, COUNT(*) as count')
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get()
            ->groupBy(function($item) {
                return date('F Y', mktime(0, 0, 0, $item->month, 1, $item->year));
            });
        
        $gs = Generalsetting::findOrFail(1);
        $ps = DB::table('pagesettings')->find(1);
        
        return view('front.blog', compact('blogs', 'bcats', 'tags', 'archives', 'gs', 'ps'))->with('langg', $this->langg);
    }

    // Capcha Code Image
    private function  code_image()
    {
        if (!function_exists('imagecreatetruecolor')) {
            return;
        }
        $actual_path = str_replace('project','',base_path());
        $image = imagecreatetruecolor(200, 50);
        $background_color = imagecolorallocate($image, 255, 255, 255);
        imagefilledrectangle($image,0,0,200,50,$background_color);

        $pixel = imagecolorallocate($image, 0,0,255);
        for($i=0;$i<500;$i++)
        {
            imagesetpixel($image,rand()%200,rand()%50,$pixel);
        }

        $font = $actual_path.'assets/front/fonts/NotoSans-Bold.ttf';
        $allowed_letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $length = strlen($allowed_letters);
        $letter = $allowed_letters[rand(0, $length-1)];
        $word='';
        //$text_color = imagecolorallocate($image, 8, 186, 239);
        $text_color = imagecolorallocate($image, 0, 0, 0);
        $cap_length=6;// No. of character in image
        for ($i = 0; $i< $cap_length;$i++)
        {
            $letter = $allowed_letters[rand(0, $length-1)];
            imagettftext($image, 25, 1, 35+($i*25), 35, $text_color, $font, $letter);
            $word.=$letter;
        }
        $pixels = imagecolorallocate($image, 8, 186, 239);
        for($i=0;$i<500;$i++)
        {
            imagesetpixel($image,rand()%200,rand()%50,$pixels);
        }
        session(['captcha_string' => $word]);
        imagepng($image, $actual_path."assets/images/capcha_code.png");
    }

    public function refresh_code()
    {
        $this->code_image();
        return "done";
    }

    // ------------------------ CURRENCY & LANGUAGE SWITCHERS ------------------------
    public function currency($id)
    {
        try{
            $currency = Currency::findOrFail($id);
            Session::put('currency', $currency->id);
        }catch(\Throwable $e){
            return redirect()->back()->with('unsuccess', 'Invalid currency selected.');
        }
        return redirect()->back();
    }

    public function language($id)
    {
        try{
            $language = Language::findOrFail($id);
            Session::put('language', $language->id);
        }catch(\Throwable $e){
            return redirect()->back()->with('unsuccess', 'Invalid language selected.');
        }
        return redirect()->back();
    }

} // <-- end of FrontendController class

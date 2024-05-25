<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\Tiket\TiketWisataAPIController;
use App\Models\BravoReview;
use App\Models\Product;
use App\Models\Category;
use App\Models\Hotel;
use App\Models\Media;
use App\Models\Location;
use App\Models\User;
use App\Models\ProductVariant;
use App\Models\PriceCalendar;
use App\Models\OrderItem;
use App\Models\ShopSetting;
use App\Models\TiketWisata;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    protected $prod = false;
    public $token;

    public function index()
    {
        return Product::all();
    }

    public function store(Request $request)
    {
        $type = Category::find($request->category_id);
        $user = Auth::user();
        $slug = $this->generateSlug($request->name);
        $sku = $this->generateSKU($user->id, $type->slug);

        $variant = json_decode($request->variant) ?? null;
        $variant_count = 0;
        if ($variant) {
            $variant_count = sizeof($variant);
        }

        $this->token = $request->bearerToken();
        $cdn = $this->prod ? "https://cdn.mykomodo.kabtour.com/v2/media_files" : "https://cdn.mykomodo.kabtour.com/v2/media_files";
        $post = Http::withToken($this->token)->withoutVerifying()->withOptions(["verify" => false])->acceptJson();
        $names = [];
        foreach ($request->allFiles() as $k => $v) {
            if (is_array($v)) {
                foreach ($v as $vk) {
                    $name = $vk->getClientOriginalName();
                    $post->attach($k . '[]', file_get_contents($vk), $name);
                    $names[] = $name;
                }
            } else {
                $name = $v->getClientOriginalName();
                $post->attach($k, file_get_contents($v), $name);
                $names[] = $name;
            }
        }
        // dd($names);

        $response = $post->post($cdn, ["prefix" => $sku]);
        // dd($response->json());
        // dd($response->json());
        $result = json_decode(json_encode($response->json()));
        $banner = 0;
        $gallery = [];

        $data = '';

        $banner = $result->banner->id;
        if (isset($result->gallery)) {
            for ($i = 0; $i < sizeof($result->gallery); $i++) {
                $gallery[] = $result->gallery[$i]->id;
            }
        }

        // variant image not used
        // if($variant_count>0){
        //     for($i = 0; $i<$variant_count;$i++){
        //         $variant[$i]->image_id = $result->variant_image[$i]->id ?? null;
        //     }
        // }
        if (isset($result->variant_image)) {
            foreach ($result->variant_image as $k => $v) {
                $name_exp = explode('-', explode('.', $v->file_name)[0]);
                $idx = $name_exp[sizeof($name_exp) - 1];
                $variant[$idx]->image_id = $v->id;
            }
        }
        // end of variant image


        // if (sizeOf($gallery) == ($variant_count)) {
        //     for ($i = 0; $i < sizeof($gallery); $i++) {
        //         // return response()->json($variant[$i]);
        //         $variant[$i]->image_id = $gallery[$i];
        //     }
        // }

        // if (sizeOf($gallery) == ($variant_count + 1)) {
        //     for ($i = 1; $i < sizeof($gallery); $i++) {
        //         $variant[$i - 1]->image_id = $gallery[$i];
        //         unset($gallery[$i]);
        //     }
        // }

        // if (sizeOf($gallery) == ($variant_count + 2)) {
        //     for ($i = 2; $i < sizeof($gallery); $i++) {
        //         $variant[$i - 2]->image_id = $gallery[$i];
        //         unset($gallery[$i]);
        //     }
        // }

        $product = new Product();
        $product->name = $request->name;
        $product->slug = $slug;
        $product->description = $request->description;
        $product->length = $request->length ?? 0;
        $product->width = $request->width ?? 0;
        $product->height = $request->height ?? 0;
        $product->weight = $request->weight ?? 0;
        $product->price = $request->price;
        $product->price_weekend = $request->price_weekend ?? 0;
        $product->price_holiday = $request->price_holiday ?? 0;
        $product->discount = $request->discount ?? 0;
        $product->discount_weekend = $request->discount_weekend ?? 0;
        $product->discount_holiday = $request->discount_holiday ?? 0;
        $product->price_calendars = $request->price_calendars ?? null;
        $product->sku = $sku;
        $product->stock_status = $request->stock_quantity > 0 ? 'tersedia' : 'kosong';
        $product->stock_quantity = $request->stock_quantity;
        $product->is_featured = $request->is_featured ?? 0;
        $product->image_id = $banner;
        $product->gallery = implode(',', $gallery);
        $product->category_id = $request->category_id;
        $product->subcategory_id = $request->subcategory_id ?? null;
        $product->location_id = Location::where('slug', $user->location)->first()->id;
        $product->status = $request->status;
        $product->admin_fee = getAdminFee($product->price);
        $product->create_user = $user->id;
        $product->shipping = $request->shipping ?? null;
        $product->save();
        $data = $product;
        // $data->variant = [];
        $tempvar = [];
        for ($i = 0; $i < $variant_count; $i++) {
            $var = $variant[$i];
            $variantModel = new ProductVariant();
            $variantModel->product_id = $product->id;
            $variantModel->name = $var->name;
            $variantModel->price = $var->price;
            $variantModel->price_weekend = $var->price_weekend ?? 0;
            $variantModel->price_holiday = $var->price_holiday ?? 0;
            $variantModel->discount = $var->discount ?? 0;
            $variantModel->discount_weekend = $var->discount_weekend ??  0;
            $variantModel->discount_holiday = $var->discount_holiday ?? 0;
            $variantModel->image_id = $var->image_id ?? null;
            $variantModel->price_calendars = $product->price_calendars ?? null;
            $variantModel->stock_quantity = $var->stock_quantity ?? 0;
            $variantModel->stock_status = $var->stock_quantity > 0 ? 'tersedia' : 'kosong';
            $variantModel->status = $var->status ?? 'publish';
            $variantModel->create_user = $user->id;
            $variantModel->save();
            // array_push($data->variant, $variantModel);
            $tempvar[] = $variantModel;
        }
        $data->variant = $tempvar;

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil ditambahkan',
            'data' => $data
        ]);
    }

    public function getCalendar(Request $request)
    {
        $result = [
            'success' => true,
            'message' => 'Tanggal berhasil diambil',
        ];
        $getDates = PriceCalendar::where(['create_user' => Auth::id(), 'object_model' => $request->category_slug])->get();
        $result['data'] = $getDates;
        return response()->json($result);
    }

    public function saveCalendar(Request $request)
    {
        $user = Auth::user();
        $dates = $request->dates;
        $deletes = [];
        $saved = [];
        // $arrDates = implode(',',$dates);
        $result = [
            'success' => true,
            'message' => 'Tanggal berhasil disimpan',
        ];
        $getSaved = PriceCalendar::where(['create_user' => $user->id, 'object_model' => $request->category_slug])->get();
        foreach ($getSaved as $datecal) {
            array_push($saved, $datecal->price_date);
        }
        foreach ($dates as $k => $v) {
            $cal = PriceCalendar::updateOrCreate([
                'create_user' => $user->id,
                'object_model' => $request->category_slug,
                'price_date' => $v,
            ], [
                'create_user' => $user->id,
                'object_model' => $request->category_slug,
                'object_id' => null,
                'price' => 0,
                'price_child' => 0,
                'price_date' => $v,
                'status' => $request->status ? 1 : 0,
                'deleted_at' => null,
            ]);
            if (!$cal->id) {
                $result['success'] = false;
                $result['message'] = "Error at key: " . $k . " , with value: " . $v;
            }
        }

        foreach ($saved as $k => $v) {
            if (!in_array($v, $dates)) {
                array_push($deletes, $v);
            }
        }

        foreach ($deletes as $k => $v) {
            $getCal = PriceCalendar::where(['create_user' => $user->id, 'object_model' => $request->category_slug, 'price_date' => $v])->first();
            $cal = PriceCalendar::find($getCal->id);
            $cal->delete();
        }

        return response()->json($result);
    }
    public function checkDate()
    {
        return response()->json(date('Y-m-d H:i:s', strtotime("+7 Hours")));
    }

    public function checkSlug($name)
    {
        $slug = str_replace(' ', '-', $name);
        $check = Product::where('slug', 'LIKE', '%' . $slug . '%')->count();
        if ($check > 0) {
            $slug .= '-' . ($check + 1);
        }
        dd([$check, $check + 1, $slug]);
    }

    private function generateSlug($name)
    {
        $slug = str_replace(' ', '-', $name) . '-' . Auth::id();
        $check = Product::where('slug', 'LIKE', '%' . $slug . '%')->count();
        if ($check > 0) {
            $slug .= '-' . $check + 1;
        }
        return $slug;
    }

    private function generateSKU($uid, $type)
    {
        $sku = $type . '-' . $uid . '-';
        $code = substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 6);
        $sku .= $code;
        if (Product::where('sku', $sku)->doesntExist())
            return $sku;
        $this->generateSKU($uid, $type);
    }

    public function myProducts($category_id)
    {
        $result = [
            'success' => true,
            'message' => 'Data Fetched',
        ];
        $get_holiday = Http::withOptions([
            'verify' => false,
        ])->get('https://raw.githubusercontent.com/guangrei/Json-Indonesia-holidays/master/calendar.json')->json();
        $check_date = date("Y-m-d", strtotime("+7 Hours"));
        $products = Product::where(['create_user' => Auth::id(), 'category_id' => $category_id])->with('variants')->orderBy('id', 'desc')->get();
        foreach ($products as &$k) {
            $k->check_date = $check_date;
            $preurl = $this->prod ?  "https://cdn.mykomodo.kabtour.com/uploads/" : "https://cdn.mykomodo.kabtour.com/uploads/";
            $banner = Media::find($k->image_id);
            if (explode('/', $banner->file_path)[0] == '0000') {
                $preurl = $this->prod ? "https://cdn.mykomodo.kabtour.com/uploads/" : "https://cdn.mykomodo.kabtour.com/uploads/";
            }
            $k->banner = [
                "original"  => $preurl . $banner->file_path,
                "200x150"   => $preurl . $banner->file_resize_200,
                "250x200"   => $preurl . $banner->file_resize_250,
                "400x350"   => $preurl . $banner->file_resize_400,
            ];
            $gall = explode(',', $k->gallery);
            $gallimg = [];
            foreach ($gall as $key => $v) {
                $img = Media::find($v);
                if ($img) {
                    $arrimg = [
                        "original"  => $preurl . $img->file_path,
                        "200x150"   => $preurl . $img->file_resize_200,
                        "250x200"   => $preurl . $img->file_resize_250,
                        "400x350"   => $preurl . $img->file_resize_400,
                    ];
                    array_push($gallimg, $arrimg);
                }
            }
            $k->gallery = $gallimg;
            $cat = Category::find($k->category_id);
            $slug = $cat->slug;
            $k->location_name = Location::find($k->location_id)->name;
            $k->category_name = $cat->name;
            if ($k->subcategory_id && $k->subcategory_id > 0) {
                $k->category_name = Category::find($k->subcategory_id)->name;
            }
            $k->price_normal = $k->price;
            $k->price = $k->price_normal - $k->discount;
            if ($k->price_weekend && $k->price_weekend > 0) {
                if (date("D", strtotime($check_date)) === "Sat" || date("D", strtotime($check_date)) === "Sun") {
                    $k->price = $k->price_weekend - $k->discount_weekend;
                }
                if (array_key_exists($check_date, $get_holiday)) {
                    $k->price = $k->price_weekend - $k->discount_weekend;
                }
            }
            // $getCalendars = Product::find($k->id)->price_calendars;
            $getCalendars = PriceCalendar::where(['create_user' => $k->create_user, 'object_model' => $slug, 'price_date' => $check_date, 'status' => 1])->first();
            // dd($getCalendars);
            if ($getCalendars) {
                if ($k->price_holiday && $k->price_holiday > 0) {
                    // $arrDates = explode(',', $getCalendars);
                    // if (in_array($check_date, $arrDates)) {
                    //     $k->price = $k->price_holiday - $k->discount_holiday;
                    // }
                    $k->price = $k->price_holiday - $k->discount_holiday;
                }
            }
            $k->price = (string)$k->price;
            foreach ($k->variants as &$variant) {
                $variant->price_normal = $variant->price;
                $variant->price = $variant->price_normal - $variant->discount;
                if ($variant->image_id) {
                    $varimage = Media::find($variant->image_id);
                    $variant->image = [
                        "original"  => $preurl . $varimage->file_path,
                        "200x150"   => $preurl . $varimage->file_resize_200,
                        "250x200"   => $preurl . $varimage->file_resize_250,
                        "400x350"   => $preurl . $varimage->file_resize_400,
                    ];
                }

                if ($variant->price_weekend && $variant->price_weekend > 0) {
                    if (date("D", strtotime($check_date)) === "Sat" || date("D", strtotime($check_date)) === "Sun") {
                        $variant->price = $variant->price_weekend - $variant->discount_weekend;
                    }
                    if (array_key_exists($check_date, $get_holiday)) {
                        $variant->price = $variant->price_weekend - $variant->discount_weekend;
                    }
                }
                if ($getCalendars) {
                    if ($variant->price_holiday && $variant->price_holiday > 0) {
                        // $arrDates = explode(',', $getCalendars);
                        // if (in_array($check_date, $arrDates)) {
                        //     $variant->price = $variant->price_holiday - $variant->discount_holiday;
                        // }
                        $variant->price = $variant->price_holiday - $variant->discount_holiday;
                    }
                }
                $variant->price = (string)$variant->price;
            }
            $k->total_sales = OrderItem::where(['product_id' => $k->id, 'status' => 'Payment Sukses'])->whereNotNull('payment_id')->sum('quantity');
        }
        $result['data'] = $products;
        return response()->json($result);
    }

    public function show($id)
    {
        $detail = Product::where('id', $id)->with('variants')->get()[0];
        if ($detail) {
            $preurl = $this->prod ? "https://cdn.mykomodo.kabtour.com/uploads/" : "https://cdn.mykomodo.kabtour.com/uploads/";
            $banner = Media::find($detail->image_id);
            $detail->banner = [
                "original"  => $preurl . $banner->file_path,
                "200x150"   => $preurl . $banner->file_resize_200,
                "250x200"   => $preurl . $banner->file_resize_250,
                "400x350"   => $preurl . $banner->file_resize_400,
            ];
            $gall = explode(',', $detail->gallery);
            $gallimg = [];
            foreach ($gall as $k => $v) {
                $img = Media::find($v);
                if ($img) {
                    $arrimg = [
                        "original"  => $preurl . $img->file_path,
                        "200x150"   => $preurl . $img->file_resize_200,
                        "250x200"   => $preurl . $img->file_resize_250,
                        "400x350"   => $preurl . $img->file_resize_400,
                    ];
                    array_push($gallimg, $arrimg);
                }
            }
            $detail->gallery = $gallimg;
            foreach ($detail->variants as &$k) {
                $img = Media::find($k->image_id);
                $arrimg = [
                    "original"  => $preurl . $img->file_path,
                    "200x150"   => $preurl . $img->file_resize_200,
                    "250x200"   => $preurl . $img->file_resize_250,
                    "400x350"   => $preurl . $img->file_resize_400,
                ];
                $k->image = $arrimg;
            }
            return response()->json($detail);
        }
    }

    // Masih prototype
    public function update(Request $request, $id)
    {
        // return(response()->json($request->all()));
        $apiresult = ['success' => false, 'message' => 'Terjadi Kesalahan'];
        // $result['reqdata']=$request->all();
        // return response()->json($result);
        $product = Product::find($id);
        if ($product) {
            $sku = $product->sku;
            $variant = json_decode($request->variant) ?? null;
            $variant_count = 0;
            if ($variant) {
                $variant_count = sizeof($variant);
            }
            $this->token = $request->bearerToken();
            $cdn = $this->prod ? "https://cdn.mykomodo.kabtour.com/v2/media_files" : "https://cdn.mykomodo.kabtour.com/v2/media_files";
            $post = Http::withToken($this->token)->withoutVerifying()->withOptions(["verify" => false])->acceptJson();
            $names = [];
            foreach ($request->allFiles() as $k => $v) {
                if (is_array($v)) {
                    foreach ($v as $vk) {
                        $name = $vk->getClientOriginalName();
                        $post->attach($k . '[]', file_get_contents($vk), $name);
                        $names[] = $name;
                    }
                } else {
                    $name = $v->getClientOriginalName();
                    $post->attach($k, file_get_contents($v), $name);
                    $names[] = $name;
                }
            }

            $response = $post->post($cdn, ["prefix" => $sku]);

            $result = json_decode(json_encode($response->json()));
            // return(response()->json($result));
            if (isset($result->banner)) {
                $product->image_id = $result->banner->id;
            }
            if (isset($result->gallery)) {
                $newgal = [];
                foreach ($result->gallery as $k => $v) {
                    $newgal[] = $v->id;
                }
                $oldgal = explode(',', $product->gallery);
                if (sizeof($result->gallery) >= sizeof($oldgal)) {
                    $product->gallery = implode(',', $newgal);
                } else {
                    $product->gallery = implode(',', $oldgal);
                }
            }
            if (isset($result->variant_image)) {
                foreach ($result->variant_image as $k => $v) {
                    $name_exp = explode('-', explode('.', $v->file_name)[0]);
                    $idx = $name_exp[sizeof($name_exp) - 1];
                    $variant[$idx]->image_id = $v->id;
                }
            }
            // return(response()->json($result->variant_image));
            // if(isset($result->variant_image)){
            //     for($i = 0; $i<sizeof($result->variant_image);$i++){
            //         $variant[$i]->image_id = $result->variant_image[$i]->id;
            //     }
            // }

            // $result['data']=$product;
            // $result['variant']=$variant;
            $product->name = $request->name ?? $product->name;
            $product->description = $request->description ?? $product->description;
            $product->length = $request->length ?? $product->length;
            $product->width = $request->width ?? $product->width;
            $product->height = $request->height ?? $product->height;
            $product->weight = $request->weight ?? $product->weight;
            $product->price = $request->price ?? $product->price;
            $product->price_weekend = $request->price_weekend ?? $product->price_weekend;
            $product->price_holiday = $request->price_holiday ?? $product->price_holiday;
            $product->discount = $request->discount ?? $product->discount;
            $product->discount_weekend = $request->discount_weekend ?? $product->discount_weekend;
            $product->discount_holiday = $request->discount_holiday ?? $product->discount_holiday;
            $product->price_calendars = $request->price_calendars ?? $product->price_calendars;
            $product->stock_status = $request->stock_quantity > 0 ? 'tersedia' : 'kosong';
            $product->stock_quantity = $request->stock_quantity ?? $product->stock_quantity;
            $product->is_featured = $request->is_featured ?? $product->is_featured;
            $product->category_id = $request->category_id ?? $product->category_id;
            $product->subcategory_id = $request->subcategory_id ?? $product->subcategory_id;
            $product->status = $request->status ?? $product->status;
            $product->admin_fee = getAdminFee($product->price);
            $product->shipping = $request->shipping ?? $product->shipping;
            $product->save();

            $data = $product;
            // $data->variant = [];
            $tempvar = [];
            for ($i = 0; $i < $variant_count; $i++) {
                $var = $variant[$i];
                $variantModel = null;
                if ($var->id && $var->id != 'null') {
                    $variantModel = ProductVariant::find($var->id);
                    if (!isset($var->image_id)) {
                        $var->image_id = $variantModel->image_id;
                    }
                } else {
                    $variantModel = new ProductVariant();
                    $variantModel->product_id = $product->id;
                    $variantModel->name = $var->name;
                    $variantModel->price = $var->price;
                    $variantModel->price_weekend = $var->price_weekend ?? 0;
                    $variantModel->price_holiday = $var->price_holiday ?? 0;
                    $variantModel->discount = $var->discount ?? 0;
                    $variantModel->discount_weekend = $var->discount_weekend ??  0;
                    $variantModel->discount_holiday = $var->discount_holiday ?? 0;
                    $variantModel->image_id = $var->image_id ?? null;
                    $variantModel->price_calendars = $product->price_calendars ?? null;
                    $variantModel->stock_quantity = $var->stock_quantity ?? 0;
                    $variantModel->stock_status = $var->stock_quantity > 0 ? 'tersedia' : 'kosong';
                    $variantModel->status = $var->status ?? 'publish';
                    $variantModel->create_user = Auth::id();
                    $variantModel->save();
                }
                $variantModel->product_id = $product->id ?? $variantModel->product_id;
                $variantModel->name = $var->name ?? $variantModel->name;
                $variantModel->price = $var->price ?? $variantModel->price;
                $variantModel->price_weekend = $var->price_weekend ?? $variantModel->price_weekend;
                $variantModel->price_holiday = $var->price_holiday ?? $variantModel->price_holiday;
                $variantModel->discount = $var->discount ?? $variantModel->discount;
                $variantModel->discount_weekend = $var->discount_weekend ??  $variantModel->discount_weekend;
                $variantModel->discount_holiday = $var->discount_holiday ?? $variantModel->discount_holiday;
                $variantModel->image_id = $var->image_id ?? $variantModel->image_id;
                $variantModel->price_calendars = $product->price_calendars ?? $variantModel->price_calendars;
                $variantModel->stock_quantity = $var->stock_quantity ?? $variantModel->stock_quantity;
                $variantModel->stock_status = $var->stock_quantity > 0 ? 'tersedia' : 'kosong';
                $variantModel->status = $var->status ?? $variantModel->status;
                $variantModel->create_user = Auth::id();
                $variantModel->save();
                // array_push($data->variant, $variantModel);
                $tempvar[] = $variantModel;
            }
            $data->variant = $tempvar;
            $apiresult['success'] = true;
            $apiresult['message'] = 'Data berhasil di update';
            $apiresult['data'] = $data->toArray();
            // $result['reqdata']=$request->all();
            return response()->json($apiresult);
        }
        return response()->json($apiresult);
    }

    public function updateStock(Request $request)
    {
        $result = ['success' => false, 'message' => 'Update gagal'];
        if ($request->products) {
            $result['message'] = [];
            foreach ($request->products as $k) {
                $product = Product::find($k['product_id']);
                if ($product) {
                    $product->stock_quantity = $k['stock_quantity'];
                    $product->stock_status = $k['stock_quantity'] > 0 ? 'tersedia' : 'kosong';
                    $product->save();
                    $result['success'] = true;
                    $result['message'][] = 'Stok Produk ID ' . $k['product_id'] . ' berhasil diubah';
                } else {
                    $result['success'] = false;
                    $result['message'][] = 'Produk ID ' . $k['product_id'] . ' tidak ditemukan';
                }
            }
            if ($request->variants) {
                $result['message'] = [];
                foreach ($request->variants as $k) {
                    $product = ProductVariant::find($k['variant_id']);
                    if ($product) {
                        $product->stock_quantity = $k['stock_quantity'];
                        $product->stock_status = $k['stock_quantity'] > 0 ? 'tersedia' : 'kosong';
                        $product->save();
                        $result['success'] = true;
                        $result['message'][] = 'Stok Produk ID ' . $k['variant_id'] . ' berhasil diubah';
                    } else {
                        $result['success'] = false;
                        $result['message'][] = 'Variant ID ' . $k['variant_id'] . ' tidak ditemukan';
                    }
                }
            }
        } else {
            $result['message'] = 'Tidak ada request';
        }
        return response()->json($result);
    }

    public function updatePrice(Request $request)
    {
        $result = ['success' => false, 'message' => 'Update gagal'];
        if ($request->products) {
            $result['message'] = [];
            foreach ($request->products as $k) {
                $product = Product::find($k['product_id']);
                if ($product) {
                    $product->price = $k['price'];
                    $product->save();
                    $result['success'] = true;
                    $result['message'][] = 'Harga Produk ID ' . $k['product_id'] . ' berhasil diubah';
                } else {
                    $result['success'] = false;
                    $result['message'][] = 'Produk ID ' . $k['product_id'] . ' tidak ditemukan';
                }
            }
            if ($request->variants) {
                $result['message'] = [];
                foreach ($request->variants as $k) {
                    $product = ProductVariant::find($k['variant_id']);
                    if ($product) {
                        $product->price = $k['price'];
                        $product->save();
                        $result['success'] = true;
                        $result['message'][] = 'Harga Produk ID ' . $k['variant_id'] . ' berhasil diubah';
                    } else {
                        $result['success'] = false;
                        $result['message'][] = 'Variant ID ' . $k['variant_id'] . ' tidak ditemukan';
                    }
                }
            }
        } else {
            $result['message'] = 'Tidak ada request';
        }
        return response()->json($result);
    }

    public function updateStatus(Request $request)
    {
        $result = ['success' => false, 'message' => 'Update gagal'];
        if ($request->products) {
            $result['message'] = [];
            foreach ($request->products as $k) {
                $product = Product::find($k['product_id']);
                if ($product) {
                    $product->status = $k['status'];
                    $product->save();
                    $result['success'] = true;
                    $result['message'][] = 'Status Produk ID ' . $k['product_id'] . ' berhasil diubah';
                } else {
                    $result['success'] = false;
                    $result['message'][] = 'Produk ID ' . $k['product_id'] . ' tidak ditemukan';
                }
            }
        } else {
            $result['message'] = 'Tidak ada request';
        }
        return response()->json($result);
    }

    public function deleteProduct($id)
    {
        $variant = ProductVariant::where('product_id', $id)->get();
        foreach ($variant as $k) {
            ProductVariant::destroy($k->id);
        }
        Product::destroy($id);
        return response()->json(['success' => true, 'message' => 'Produk berhasil dihapus']);
    }

    public function deleteVariant($id)
    {
        ProductVariant::destroy($id);
        return response()->json(['success' => true, 'message' => 'Varian berhasil dihapus']);
    }

    public function deleteAllVariant($id)
    {
        ProductVariant::where('product_id', $id)->delete();
        return response()->json(['success' => true, 'message' => 'Semua Varian berhasil dihapus']);
    }

    public function destroy($id)
    {
        Product::destroy($id);
    }

    public function search(Request $request)
    {
        // return Product::where('name','like','%'.$name.'%')->get();
        // dd($request->all());
        $get_holiday = Http::withOptions([
            'verify' => false,
        ])->get('https://raw.githubusercontent.com/guangrei/Json-Indonesia-holidays/master/calendar.json')->json();
        $check_date = date("Y-m-d", strtotime("+7 Hours"));

        $product = Product::query();
        // $product->join('users', function (JoinClause $join) {
        //     $join->on('bravo_market_products.create_user', '=', 'users.id')
        //          ->where('users.status', '!=', 'suspend');
        // });
        $getSuspended = User::where('status','suspend')->get(['id']);
        // dd($suspended);
        $suspended = [];
        foreach($getSuspended as $k){
            $suspended[] = $k->id;
        }
        // dd($suspended);
        $product->whereNotIn('create_user',$suspended);
        if (!empty($location_id = $request->query('location_id'))) {
            $product->where('location_id', $location_id);
        }
        if (!empty($location = $request->query('location'))) {
            $location_id = Location::where('slug', $location)->first()->id;
            if ($location_id) {
                $product->where('location_id', $location_id);
            }
        }
        if (!empty($category_id = $request->query('category_id'))) {
            $product->where('category_id', $category_id);
        }
        if (!empty($category = $request->query('category'))) {
            $category_id = Category::where('slug', $category)->first()->id;
            $product->where('category_id', $category_id);
        }
        if (!empty($subcategory_id = $request->query('subcategory_id'))) {
            $product->where('subcategory_id', $subcategory_id);
        }
        if (!empty($subcategory = $request->query('subcategory'))) {
            $subcategory_id = Category::where('slug', $subcategory)->first()->id;
            $product->where('subcategory_id', $subcategory_id);
        }
        if (!empty($name = $request->query('name'))) {
            $product->where('name', 'like', '%' . $name . '%');
        }
        if (!empty($create_user = $request->query('create_user'))) {
            $product->where('create_user', $create_user);
        }
        if (!empty($keyword = $request->query('keyword'))) {
            $product->where('name', 'LIKE', '%' . $keyword . '%');
        }
        $list = $product->where('bravo_market_products.status', 'publish')->with(['variants' => function ($q) {
            $q->where('bravo_market_product_variants.status', 'publish')
                ->where('bravo_market_product_variants.stock_status', 'tersedia')
                ->where('bravo_market_product_variants.stock_quantity', '>', 0);
        }])->orderBy('bravo_market_products.id', 'desc')->get();
        $c = 0;
        foreach ($list as &$k) {
            $userStatus = User::find($k->create_user)->status;
            if ($userStatus != 'suspend') {

                $preurl = $this->prod ? "https://cdn.mykomodo.kabtour.com/uploads/" : "https://cdn.mykomodo.kabtour.com/uploads/";
                $banner = Media::find($k->image_id);
                if (explode('/', $banner->file_path)[0] == '0000') {
                    $preurl = $this->prod ? "https://cdn.mykomodo.kabtour.com/uploads/" : "https://cdn.mykomodo.kabtour.com/uploads/";
                }
                $k->banner = [
                    "original"  => $preurl . $banner->file_path,
                    "200x150"   => $preurl . $banner->file_resize_200,
                    "250x200"   => $preurl . $banner->file_resize_250,
                    "400x350"   => $preurl . $banner->file_resize_400,
                ];
                $gall = explode(',', $k->gallery);
                $gallimg = [];
                foreach ($gall as $key => $v) {
                    $img = Media::find($v);
                    if ($img) {
                        $arrimg = [
                            "original"  => $preurl . $img->file_path,
                            "200x150"   => $preurl . $img->file_resize_200,
                            "250x200"   => $preurl . $img->file_resize_250,
                            "400x350"   => $preurl . $img->file_resize_400,
                        ];
                        array_push($gallimg, $arrimg);
                    }
                }
                $k->gallery = $gallimg;
                $cat = Category::find($k->category_id);
                $slug = $cat->slug;
                $k->location_name = Location::find($k->location_id)->name;
                $k->category_name = $cat->name;
                if ($k->subcategory_id && $k->subcategory_id > 0) {
                    $k->category_name = Category::find($k->subcategory_id)->name;
                }
                $k->price_normal = $k->price;
                $k->price = $k->price_normal - $k->discount;
                if ($k->price_weekend && $k->price_weekend > 0) {
                    if (date("D", strtotime($check_date)) === "Sat" || date("D", strtotime($check_date)) === "Sun") {
                        $k->price = $k->price_weekend - $k->discount_weekend;
                    }
                    if (array_key_exists($check_date, $get_holiday)) {
                        $k->price = $k->price_weekend - $k->discount_weekend;
                    }
                }
                // $getCalendars = Product::find($k->id)->price_calendars;
                $getCalendars = PriceCalendar::where(['create_user' => $k->create_user, 'object_model' => $slug, 'price_date' => $check_date, 'status' => 1])->first();
                if ($getCalendars) {
                    if ($k->price_holiday && $k->price_holiday > 0) {
                        // $arrDates = explode(',', $getCalendars);
                        // if (in_array($check_date, $arrDates)) {
                        //     $k->price = $k->price_holiday - $k->discount_holiday;
                        // }
                        $k->price = $k->price_holiday - $k->discount_holiday;
                    }
                }
                $k->price = (string)$k->price;
                foreach ($k->variants as &$variant) {
                    $variant->price_normal = $variant->price;
                    $variant->price = $variant->price_normal - $variant->discount;
                    if ($variant->image_id) {
                        $varimage = Media::find($variant->image_id);
                        $variant->image = [
                            "original"  => $preurl . $varimage->file_path,
                            "200x150"   => $preurl . $varimage->file_resize_200,
                            "250x200"   => $preurl . $varimage->file_resize_250,
                            "400x350"   => $preurl . $varimage->file_resize_400,
                        ];
                    }

                    if ($variant->price_weekend && $variant->price_weekend > 0) {
                        if (date("D", strtotime($check_date)) === "Sat" || date("D", strtotime($check_date)) === "Sun") {
                            $variant->price = $variant->price_weekend - $variant->discount_weekend;
                        }
                        if (array_key_exists($check_date, $get_holiday)) {
                            $variant->price = $variant->price_weekend - $variant->discount_weekend;
                        }
                    }
                    if ($getCalendars) {
                        if ($variant->price_holiday && $variant->price_holiday > 0) {
                            // $arrDates = explode(',', $getCalendars);
                            // if (in_array($check_date, $arrDates)) {
                            //     $variant->price = $variant->price_holiday - $variant->discount_holiday;
                            // }
                            $variant->price = $variant->price_holiday - $variant->discount_holiday;
                        }
                    }
                    $variant->price = (string)$variant->price;
                }
                $k->total_sales = OrderItem::where(['product_id' => $k->id, 'status' => 'Payment Sukses'])->whereNotNull('payment_id')->sum('quantity');
                $k->seller = User::where('id', $k->create_user)->first(['business_name as name', 'location', 'address', 'first_name', 'last_name', 'name as pic_name', 'email']);
                // $getLoc = User::find($k->create_user)->location;
                $object_model = 'umkm';
                if ($k->category_id == 7) {
                    $object_model = 'food-beverage';
                }
                // $shop_setting = DB::table("shop_settings")->where(["user_id"=>$k->create_user,"object_model"=>$object_model])->first();
                // if($shop_setting){
                //     $k->seller->name = $shop_setting->name;
                // }
                $setting = ShopSetting::where(["user_id" => $k->create_user, "object_model" => $slug])->first();
                if ($setting) {
                    if ($setting->name && !empty($setting->name) && $setting->name != '') {
                        $k->seller->name = $setting->name;
                    }
                }
                $k->seller->shipping_type = $setting->shipping_type ?? null;
                $k->seller->shipping_cost = $setting->shipping_cost ?? 0;
                $getLoc = User::find($k->create_user)->location;
                if (!$getLoc) {
                    $product = Product::where(['create_user' => $k->create_user, 'status' => 'publish'])->first();
                    $loc = Location::find($product->location_id);
                    $user = User::find($k->create_user);
                    $user->location = $loc->slug;
                    $user->save();
                    $getLoc = $user->location;
                }
                if ($getLoc) {
                    $loc = Location::where('slug', $getLoc)->first();
                    if ($loc) {
                        $k->seller->location_id = $loc->id;
                        $k->seller->location_name = $loc->name;
                        if ($setting && $setting->image_id) {
                            $banner = Media::find($setting->image_id)->file_resize_200;
                            $k->seller->banner = $this->prod ? "https://cdn.mykomodo.kabtour.com/uploads/" . $banner : "https://cdn.mykomodo.kabtour.com/uploads/" . $banner;
                        } else {
                            $getBannerId = Product::where('create_user', $k->create_user)->first()->image_id;
                            $banner = Media::find($getBannerId)->file_resize_200;
                            $k->seller->banner = $this->prod ? "https://cdn.mykomodo.kabtour.com/uploads/" . $banner : "https://cdn.mykomodo.kabtour.com/uploads/" . $banner;
                        }
                        // $getMinPrice =  Product::where('create_user', $k->create_user)->min('price');
                        // $k->price = $getMinPrice;
                        $k->seller->review = 0;
                        $rating = 0;
                        $review_count = 0;
                        $reviews = BravoReview::where(['object_model' => 'food-beverages', 'vendor_id' => $k->create_user])->get();
                        if ($reviews && count($reviews) > 0) {
                            foreach ($reviews as $rk) {
                                $rating = $rating + $rk->rate_number;
                                $review_count++;
                            }
                            $k->seller->review = intval(ceil($rating / $review_count));
                            // if($k->create_user==10){
                            //     dd([$reviews,$k->review,$review_count,$rating]);
                            // }

                        }
                        $k->seller->description = $setting->description ?? null;
                        $k->seller->open_hour = $setting->open_hour ?? '07:00';
                        $k->seller->close_hour = $setting->close_hour ?? '23:00';
                        $k->seller->is_open = !$setting ? true : ($setting->is_open == 1 ? true : false);
                        $k->seller->address = $setting->address ?? ($k->address ?? $k->location_name);
                        $k->seller->latitude = $setting->latitude ?? NULL;
                        $k->seller->longitude = $setting->longitude ?? NULL;
                        // $setting = ShopSetting::where(['user_id'=>$k->create_user,'object_model'=>'food-beverage'])->first();
                        // if($setting){
                        //     $k->business_name = $setting->name;
                        //     $k->description = $setting->description;
                        //     $k->open_hour = $setting->open_hour;
                        //     $k->close_hour = $setting->close_hour;
                        //     $k->is_open = $setting->is_open == 1 ? true:false;
                        // }
                        // array_push($result, $k);
                        // if($k->seller->is_open){
                        //     array_push($result, $k);
                        // }
                    }
                }
            }
            else{
                // dd($k);
                // unset($list[$c]);
                // $list->pull($c);
            }
            $c++;
        }
        // dd($list);
        // dd(sizeof($list));
        return response()->json($list);
    }

    public function restoList(Request $request)
    {
        $result = [];
        $qList = User::select('users.id', 'users.business_name')
            ->join('user_meta', 'user_meta.user_id', '=', 'users.id')
            ->where('user_meta.name', 'verify_data_category')
            ->where('user_meta.val', 'like', '%food-beverage%')
            ->where('users.is_verified', 1)
            ->where('users.verify_submit_status', 'completed')
            ->where('users.status','<>','suspend');
        if ($request->has('location_id')) {
            $qList->where('users.location', Location::find($request->get('location_id'))->slug);
        }
        $getList = $qList->get();
        foreach ($getList as &$k) {
            $setting = DB::table("shop_settings")->where(["user_id" => $k->id, "object_model" => "food-beverage"])->first();
            if ($setting) {
                if ($setting->name && !empty($setting->name) && $setting->name != '') {
                    $k->business_name = $setting->name;
                }
                array_push($result, $k);
            }
            // dd([$k,$setting]);
            // array_push($result,$k);
        }
        return response()->json($result);
    }

    // public function fnbList(Request $request)
    // {
    //     $query = "SELECT a.create_user, b.business_name, concat(b.address,', ',b.city,', ',b.state,' ',b.zip_code) as address, b.status FROM bravo_market_products a, users b where a.category_id=7 and b.id=a.create_user ";
    //     if ($request->location_id) {
    //         $query .= "and a.location_id=" . $request->location_id . " ";
    //     }
    //     $query .= "GROUP by a.create_user";
    //     $getList = DB::select($query);
    //     for($i=0;$i<sizeof($getList);$i++){
    //         if($getList[$i]->status=='suspend'){
    //             unset($getList[$i]);
    //         }
    //     }
    //     // dd($getList);
    //     $result = [];
    //     foreach ($getList as &$k) {
    //         // dd($k);
    //         $setting = DB::table("shop_settings")->where(["user_id" => $k->create_user, "object_model" => "food-beverage"])->first();
    //         // dd($setting);
    //         if ($setting) {
    //             if ($setting->name && !empty($setting->name) && $setting->name != '') {
    //                 $k->business_name = $setting->name;
    //             }
    //         }
    //         $k->shipping_type = $setting->shipping_type ?? null;
    //         $k->shipping_cost = $setting->shipping_cost ?? 0;
    //         $getLoc = User::find($k->create_user)->location;
    //         // dd($getLoc);
    //         if (!$getLoc) {
    //             $product = Product::where(['create_user' => $k->create_user, 'status' => 'publish'])->first();
    //             $loc = Location::find($product->location_id);
    //             $user = User::find($k->create_user);
    //             $user->location = $loc->slug;
    //             $user->save();
    //             $getLoc = $user->location;
    //         }
    //         // dd($k);
    //         if ($getLoc) {
    //             $loc = Location::where('slug', $getLoc)->first();
    //             // dd(Location::where('slug', $getLoc)->get());
    //             if ($loc) {
    //                 $k->location_id = $loc->id;
    //                 $k->location_name = $loc->name;
    //                 if ($setting && $setting->image_id) {
    //                     $banner = Media::find($setting->image_id)->file_resize_200;
    //                     $k->banner = $this->prod ? "https://cdn.mykomodo.kabtour.com/uploads/" . $banner : "https://cdn.mykomodo.kabtour.com/uploads/" . $banner;
    //                 } else {
    //                     $getBannerId = Product::where('create_user', $k->create_user)->first()->image_id;
    //                     $banner = Media::find($getBannerId)->file_resize_200;
    //                     $k->banner = $this->prod ? "https://cdn.mykomodo.kabtour.com/uploads/" . $banner : "https://cdn.mykomodo.kabtour.com/uploads/" . $banner;
    //                 }
    //                 // $getMinPrice =  Product::where('create_user', $k->create_user)->min('price');
    //                 // $k->price = $getMinPrice;
    //                 $k->review = 0;
    //                 // dd($k);
    //                 $rating = 0;
    //                 $review_count = 0;
    //                 $reviews = BravoReview::where(['object_model' => 'food-beverages', 'vendor_id' => $k->create_user])->get();
    //                 if ($reviews && count($reviews) > 0) {
    //                     foreach ($reviews as $rk) {
    //                         $rating = $rating + $rk->rate_number;
    //                         $review_count++;
    //                     }
    //                     $k->review = intval(ceil($rating / $review_count));
    //                     // if($k->create_user==10){
    //                     //     dd([$reviews,$k->review,$review_count,$rating]);
    //                     // }

    //                 }
    //                 $k->description = $setting->description ?? null;
    //                 $k->open_hour = $setting->open_hour ?? '07:00';
    //                 $k->close_hour = $setting->close_hour ?? '23:00';
    //                 $k->is_open = !$setting ? true : ($setting->is_open == 1 ? true : false);
    //                 $k->address = $setting->address ?? ($k->address ?? $k->location_name);
    //                 $k->latitude = $setting->latitude ?? NULL;
    //                 $k->longitude = $setting->longitude ?? NULL;
    //                 // $setting = ShopSetting::where(['user_id'=>$k->create_user,'object_model'=>'food-beverage'])->first();
    //                 // if($setting){
    //                 //     $k->business_name = $setting->name;
    //                 //     $k->description = $setting->description;
    //                 //     $k->open_hour = $setting->open_hour;
    //                 //     $k->close_hour = $setting->close_hour;
    //                 //     $k->is_open = $setting->is_open == 1 ? true:false;
    //                 // }
    //                 // array_push($result, $k);
    //                 // dd($k);
    //                 if ($k->is_open) {
    //                     array_push($result, $k);
    //                 }
    //             }
    //         }
    //     }
    //     return response()->json($result);
    // }
    
    public function fnbList(Request $request)
    {
        $query = Product::where('category_id',7);
        if($request->has('location_id')){
            $query->where('location_id',$request->location_id);
        }
        $getList = $query->groupBy('create_user')->get('create_user');
        // dd($uidList);
        foreach($getList as &$k){
            $userData = User::find($k->create_user);
            $k->business_name = $userData->business_name;
            $k->address = $userData->address.', '.$userData->city.', '.$userData->state.', '.$userData->zip_code;
            $k->status = $userData->status;
        }
        // $getList = DB::select($query);
        for($i=0;$i<sizeof($getList);$i++){
            if($getList[$i]->status=='suspend'){
                unset($getList[$i]);
            }
        }
        // dd($getList);
        $result = [];
        foreach ($getList as &$k) {
            // dd($k);
            // $setting = DB::table("shop_settings")->where(["user_id" => $k->create_user, "object_model" => "food-beverage"])->first();
            $setting = ShopSetting::where(["user_id" => $k->create_user, "object_model" => "food-beverage"])->first();
            if ($setting) {
                if ($setting->name && !empty($setting->name) && $setting->name != '') {
                    $k->business_name = $setting->name;
                }
            }
            $k->shipping_type = $setting->shipping_type ?? null;
            $k->shipping_cost = $setting->shipping_cost ?? 0;
            $getLoc = User::find($k->create_user)->location;
            if (!$getLoc) {
                $product = Product::where(['create_user' => $k->create_user, 'status' => 'publish'])->first();
                $loc = Location::find($product->location_id);
                $user = User::find($k->create_user);
                $user->location = $loc->slug;
                $user->save();
                $getLoc = $user->location;
            }
            if ($getLoc) {
                $loc = Location::where('slug', $getLoc)->first();
                if ($loc) {
                    $k->location_id = $loc->id;
                    $k->location_name = $loc->name;
                    if ($setting && $setting->image_id) {
                        $banner = Media::find($setting->image_id)->file_resize_200;
                        $k->banner = $this->prod ? "https://cdn.mykomodo.kabtour.com/uploads/" . $banner : "https://cdn.mykomodo.kabtour.com/uploads/" . $banner;
                    } else {
                        $getBannerId = Product::where('create_user', $k->create_user)->first()->image_id;
                        $banner = Media::find($getBannerId)->file_resize_200;
                        // $k->banner = $this->prod ? "https://cdn.mykomodo.kabtour.com/uploads/" . $banner : "https://cdn.mykomodo.kabtour.com/uploads/" . $banner;
                        $k->banner = $this->prod ? "https://cdn.mykomodo.kabtour.com/uploads/" . $banner : "https://cdn.mykomodo.kabtour.com/uploads/" . $banner;
                    }
                    // $getMinPrice =  Product::where('create_user', $k->create_user)->min('price');
                    // $k->price = $getMinPrice;
                    $k->review = 0;
                    $rating = 0;
                    $review_count = 0;
                    $reviews = BravoReview::where(['object_model' => 'food-beverages', 'vendor_id' => $k->create_user])->get();
                    if ($reviews && count($reviews) > 0) {
                        foreach ($reviews as $rk) {
                            $rating = $rating + $rk->rate_number;
                            $review_count++;
                        }
                        $k->review = intval(ceil($rating / $review_count));
                        // if($k->create_user==10){
                        //     dd([$reviews,$k->review,$review_count,$rating]);
                        // }

                    }
                    $k->description = $setting->description ?? null;
                    $k->open_hour = $setting->open_hour ?? '07:00';
                    $k->close_hour = $setting->close_hour ?? '23:00';
                    $k->is_open = !$setting ? true : ($setting->is_open == 1 ? true : false);
                    $k->address = $setting->address ?? ($k->address ?? $k->location_name);
                    $k->latitude = $setting->latitude ?? NULL;
                    $k->longitude = $setting->longitude ?? NULL;
                    // $setting = ShopSetting::where(['user_id'=>$k->create_user,'object_model'=>'food-beverage'])->first();
                    // if($setting){
                    //     $k->business_name = $setting->name;
                    //     $k->description = $setting->description;
                    //     $k->open_hour = $setting->open_hour;
                    //     $k->close_hour = $setting->close_hour;
                    //     $k->is_open = $setting->is_open == 1 ? true:false;
                    // }
                    // array_push($result, $k);
                    if ($k->is_open) {
                        array_push($result, $k);
                    }
                }
            }
        }
        return response()->json($result);
    }

    public function umkmList(Request $request)
    {
        $query = "SELECT a.create_user, b.business_name, concat(b.address,', ',b.city,', ',b.state,' ',b.zip_code) as address, b.status FROM bravo_market_products a, users b where a.category_id=5 and b.id=a.create_user ";
        if ($request->location_id) {
            $query .= "and a.location_id=" . $request->location_id . " ";
        }
        $query .= "GROUP by a.create_user";
        // $getList = DB::select($query);
        // dd($getList);
        $getList = DB::select($query);
        for($i=0;$i<sizeof($getList);$i++){
            if($getList[$i]->status=='suspend'){
                unset($getList[$i]);
            }
        }
        $result = [];
        foreach ($getList as &$k) {
            // dd($k);
            $setting = DB::table("shop_settings")->where(["user_id" => $k->create_user, "object_model" => "umkm"])->first();
            if ($setting) {
                if ($setting->name && !empty($setting->name) && $setting->name != '') {
                    $k->business_name = $setting->name;
                }
            }
            $k->shipping_type = $setting->shipping_type ?? null;
            $k->shipping_cost = $setting->shipping_cost ?? 0;
            $getLoc = User::find($k->create_user)->location;
            if (!$getLoc) {
                $product = Product::where(['create_user' => $k->create_user, 'status' => 'publish'])->first();
                $loc = Location::find($product->location_id);
                $user = User::find($k->create_user);
                $user->location = $loc->slug;
                $user->save();
                $getLoc = $user->location;
            }
            if ($getLoc) {
                $loc = Location::where('slug', $getLoc)->first();
                if ($loc) {
                    $k->location_id = $loc->id;
                    $k->location_name = $loc->name;
                    if ($setting && $setting->image_id) {
                        $banner = Media::find($setting->image_id)->file_resize_200;
                        $k->banner = $this->prod ? "https://cdn.mykomodo.kabtour.com/uploads/" . $banner : "https://cdn.mykomodo.kabtour.com/uploads/" . $banner;
                    } else {
                        $getBannerId = Product::where('create_user', $k->create_user)->first()->image_id;
                        $banner = Media::find($getBannerId)->file_resize_200;
                        $k->banner = $this->prod ? "https://cdn.mykomodo.kabtour.com/uploads/" . $banner : "https://cdn.mykomodo.kabtour.com/uploads/" . $banner;
                    }
                    // $getMinPrice =  Product::where('create_user', $k->create_user)->min('price');
                    // $k->price = $getMinPrice;
                    $k->review = 0;
                    $rating = 0;
                    $review_count = 0;
                    $reviews = BravoReview::where(['object_model' => 'umkm', 'vendor_id' => $k->create_user])->get();
                    if ($reviews && count($reviews) > 0) {
                        foreach ($reviews as $rk) {
                            $rating = $rating + $rk->rate_number;
                            $review_count++;
                        }
                        $k->review = intval(ceil($rating / $review_count));
                        // if($k->create_user==10){
                        //     dd([$reviews,$k->review,$review_count,$rating]);
                        // }

                    }
                    $k->description = $setting->description ?? null;
                    $k->open_hour = $setting->open_hour ?? '07:00';
                    $k->close_hour = $setting->close_hour ?? '23:00';
                    $k->is_open = !$setting ? true : ($setting->is_open == 1 ? true : false);
                    $k->address = $setting->address ?? ($k->address ?? $k->location_name);
                    $k->latitude = $setting->latitude ?? NULL;
                    $k->longitude = $setting->longitude ?? NULL;
                    // $setting = ShopSetting::where(['user_id'=>$k->create_user,'object_model'=>'food-beverage'])->first();
                    // if($setting){
                    //     $k->business_name = $setting->name;
                    //     $k->description = $setting->description;
                    //     $k->open_hour = $setting->open_hour;
                    //     $k->close_hour = $setting->close_hour;
                    //     $k->is_open = $setting->is_open == 1 ? true:false;
                    // }
                    if ($k->is_open) {
                        array_push($result, $k);
                    }
                }
            }
        }
        return response()->json($result);
    }

    // public function umkmList(Request $request)
    // {
    //     // $query = "SELECT a.create_user, b.business_name FROM bravo_market_products a, users b where a.category_id=5 and b.id=a.create_user GROUP by a.create_user";
    //     // $getList = DB::select($query);
    //     $get_holiday = Http::withOptions([
    //         'verify' => false,
    //     ])->get('https://raw.githubusercontent.com/guangrei/Json-Indonesia-holidays/master/calendar.json')->json();
    //     $check_date = date("Y-m-d", strtotime("+7 Hours"));
    //     $getList = Product::where(['category_id' => '5', 'status' => 'publish', 'stock_status' => 'tersedia'])->orderBy('id','desc')->get(['id', 'name', 'price as price_normal', 'create_user', 'price_weekend', 'price_holiday', 'discount', 'discount_weekend', 'discount_holiday']);
    //     $result = [];
    //     // dd($getList->toArray());
    //     foreach ($getList as &$k) {
    //         $owner = User::find($k->create_user);
    //         $getLoc = $owner->location;
    //         if ($getLoc) {
    //             $loc = Location::where('slug', $getLoc)->first();
    //             if ($loc) {
    //                 $k->business_name = $owner->business_name;
    //                 $k->location_id = $loc->id;
    //                 $k->location_name = $loc->name;
    //                 $getBannerId = Product::where('create_user', $k->create_user)->first()->image_id;
    //                 $banner = Media::find($getBannerId)->file_resize_200;

    //                 $k->banner = $this->prod ? "https://cdn.mykomodo.kabtour.com/uploads/" . $banner : "https://cdn.mykomodo.kabtour.com/uploads/" . $banner;
    //                 // $getMinPrice =  Product::where('create_user',$k->create_user)->min('price');
    //                 // $k->price = $getMinPrice;
    //                 $k->price = $k->price_normal - $k->discount;
    //                 if ($k->price_weekend && $k->price_weekend > 0) {
    //                     if (date("D", strtotime($check_date)) === "Sat" || date("D", strtotime($check_date)) === "Sun") {
    //                         $k->price = $k->price_weekend - $k->discount_weekend;
    //                     }
    //                     if (array_key_exists($check_date, $get_holiday)) {
    //                         $k->price = $k->price_weekend - $k->discount_weekend;
    //                     }
    //                 }
    //                 $getCalendars = Product::find($k->id)->price_calendars;
    //                 if ($getCalendars) {
    //                     if ($k->price_holiday && $k->price_holiday > 0) {
    //                         $arrDates = explode(',', $getCalendars);
    //                         if (in_array($check_date, $arrDates)) {
    //                             $k->price = $k->price_holiday - $k->discount_holiday;
    //                         }
    //                     }
    //                 }
    //                 array_push($result, $k);
    //             }
    //         }
    //     }
    //     return response()->json($result);
    // }

    public function layananJasaList(Request $request)
    {
        $query = "SELECT a.create_user, b.business_name FROM bravo_market_products a, users b where a.category_id=1 and b.id=a.create_user GROUP by a.create_user";
        $getList = DB::select($query);
        $result = [];
        foreach ($getList as &$k) {
            $getLoc = User::find($k->create_user)->location;
            if ($getLoc) {
                $loc = Location::where('slug', $getLoc)->first();
                if ($loc) {
                    $k->location_id = $loc->id;
                    $k->location_name = $loc->name;
                    $getBannerId = Product::where('create_user', $k->create_user)->first()->image_id;
                    $banner = Media::find($getBannerId)->file_resize_200;
                    $k->banner = $this->prod ? "https://cdn.mykomodo.kabtour.com/uploads/" . $banner : "https://cdn.mykomodo.kabtour.com/uploads/" . $banner;
                    $getMinPrice =  Product::where('create_user', $k->create_user)->min('price');
                    $k->price = $getMinPrice;
                    array_push($result, $k);
                }
            }
        }
        return response()->json($result);
    }

    public function keywordSearch(Request $request)
    {
        $keyword = $request->keyword;
        $results = [
            "success" => true,
            "message" => "Data found",
            "data" => null
        ];
        $reqUmkm = $request->merge(['category_id' => 5]);
        // dd($reqUmkm);
        $umkm = json_decode($this->search($reqUmkm)->getContent());
        $reqFnb = $request->merge(['category_id' => 7]);
        $reqFnb = $request;
        $fnb = json_decode($this->search($reqFnb)->getContent());
        $homestay = Http::withoutVerifying()->withOptions(["verify" => false])->acceptJson()->get('https://hotel-api.mykomodo.kabtour.com/v2/search?keyword=' . $request->keyword)->json()['data']['data'];
        $tranport = Http::withoutVerifying()->withOptions(["verify" => false])->acceptJson()->get('https://rent-api.mykomodo.kabtour.com/v2/search?keyword=' . $request->keyword)->json()['data'];
        $twc = new TiketWisataAPIController();
        $tiketwista = json_decode($twc->index($request)->getContent())->data;

        $results['data'] = [
            'homestay' => $homestay,
            'umkm' => $umkm,
            'food-beverages' => $fnb,
            'transportasi' => $tranport,
            'tiket-wisata' => $tiketwista
        ];
        return response()->json($results, 200);
    }
}

<?php

namespace App\Http\Controllers\Api\Tiket;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Models\TiketWisata;
use App\Models\TiketWisataDate;
use App\Models\Media;
use App\Models\Booking;
use App\Models\Location;
use App\Models\User;
use App\Models\BravoTiketWisataCheckin;
use App\Models\BravoTiketWisataManifest;
use App\Models\BravoTiketWisataCategory;
use App\Models\Payment;
use Illuminate\Support\Str;
use App\Models\Media as MediaFile;


class TiketWisataAPIController extends Controller{
    protected $prod = false;
    public $token;
    public $app_id = 2;

    public function index(Request $request){
        $check_date = $request->date ?? date("Y-m-d", strtotime("+8 Hours"));
        $getSuspended = User::where('status','suspend')->get('id');
        $suspended = [];
        foreach($getSuspended as $k){
            $suspended[] = $k->id;
        }
        $query = TiketWisata::where('status','publish');
        $query->whereNotIn('create_user',$suspended);
        if($request->has('create_user')){
            $query->where('create_user',$request->create_user);
        }
        if($request->has('location_id')){
            $query->where('location_id',$request->location_id);
        }
        if($request->has('keyword')){
            $query->where('title','LIKE','%'.$request->keyword.'%');
        }
        $tiket = $query->with(['category','specialDates'])->get();
        foreach ($tiket as &$k) {
            $preurl = $this->prod ? "https://cdn.mykomodo.kabtour.com/uploads/" : "https://cdn.mykomodo.kabtour.com/uploads/";
			$banner = Media::find($k->banner);
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
            $k->price_normal = $k->price;
            $getCalendars = TiketWisataDate::where(['target_id'=>$k->id,'start_date'=>$check_date,'status'=>1])->first();
            if ($getCalendars) {
                if ($k->price_holiday && $k->price_holiday > 0) {
                    $k->price = $k->price_holiday;
                }
            }
            $bookingCount = Booking::where('object_model', 'tiket-wisata')
                            ->where('object_id', $k->id)
                            ->whereRaw('substr(start_date,1,10) = ?', [$check_date])
                            ->whereNotIn('status', ['draft', 'waiting', 'Dibatalkan', 'Waiting'])
                            ->count();
            $k->stock = $k->stock - $bookingCount;
            $k->location = Location::find($k->location_id,['id','name','slug']);
        }
        return response()->json(['data'=>$tiket]);
    }

    public function show($id,Request $request){}

    public function store(Request $request){
        $slug = $this->generateSlug($request->title);
        $request->merge(['slug'=>$slug]);
        $request->merge(['create_user' => Auth::id()]);
        if(!$request->has('location_id')){
            // $loc_id = Location::where('slug',Auth::user()->location)->first()->id;
            $loc_id = $user->location_id ?? $user->kabupaten_id;
            $request->merge(['location_id'=>$loc_id]);
        }
        $tiket = TiketWisata::create($request->except(['banner','gallery']));

        if (!$tiket->id) {
            return response()->json(['message' => 'Failed to create Tiket Wisata'], 500);
        }

        $tiket->slug = $tiket->slug.'-'.$tiket->id;
        $tiket->save();

        $this->token = $request->bearerToken();
		
        $cdn = $this->prod ? "https://cdn.mykomodo.kabtour.com/v2/media_files" : "https://cdn.mykomodo.kabtour.com/v2/media_files";
        $post = Http::withToken($this->token)->withoutVerifying()->withOptions(["verify" => false])->acceptJson();
        $names = [];
        foreach ($request->allFiles() as $k => $v) {
            if(is_array($v)){
                foreach($v as $vk){
                    $name = $vk->getClientOriginalName();
                    $post->attach($k.'[]', file_get_contents($vk), $name);
                    $names[]=$name;
                }
            }else{
                $name = $v->getClientOriginalName();
                $post->attach($k, file_get_contents($v), $name);
                $names[]=$name;
            }
        }
        // dd($names);
        
        $response = $post->post($cdn, ["prefix" => $tiket->slug]);
        // dd($response->json());
        // dd($response->json());
        $result = json_decode(json_encode($response->json()));
        $banner = 0;
        $gallery = [];
        
        $data = '';

        $banner = $result->banner->id;
        if(isset($result->gallery)){
            for ($i = 0; $i < sizeof($result->gallery); $i++) {
                $gallery[] = $result->gallery[$i]->id;
            }
        }

		$tiket->banner = $banner;
		$tiket->gallery = implode(',',$gallery);
		$tiket->save();

        if($request->holiday_price_status){
            if($request->has('dates')){
                $dates = $request->dates;
                if(!is_array($request->dates)){
                    $temp = str_replace('[','',$request->dates);
                    $temp = str_replace(']','',$request->dates);
                    $temp = str_replace(' ','',$request->dates);
                    $dates = explode(',',$temp);
                }
                for($i=0;$i<sizeof($dates);$i++){
                    TiketWisataDate::updateOrCreate(
                        [
                            'target_id'=>$tiket->id,
                            'start_date'=>$dates[$i]
                        ],
                        [
                            'status'=>$request->holiday_price_status ? 1 : 0,
                        ]
                    );
                }
            }
        }else{
            TiketWisataDate::where('target_id',$tiket->id)->update(['status' => 0]);
        }

        return response()->json(
			[
				'success'=>true,
				'message'=>'Tiket berhasil disimpan',
				'data'=>$tiket,
			]
		);
    }

    public function update($id, Request $request){
        $tiket = TiketWisata::find($id);
        if($tiket){
            $tiket->update($request->except(['banner','gallery','_method']));
            $this->token = $request->bearerToken();
			
			$cdn = $this->prod ? "https://cdn.mykomodo.kabtour.com/v2/media_files" : "https://cdn.mykomodo.kabtour.com/v2/media_files";
			$post = Http::withToken($this->token)->withoutVerifying()->withOptions(["verify" => false])->acceptJson();
			$names = [];
			foreach ($request->allFiles() as $k => $v) {
				if(is_array($v)){
					foreach($v as $vk){
						$name = $vk->getClientOriginalName();
						$post->attach($k.'[]', file_get_contents($vk), $name);
						$names[]=$name;
					}
				}else{
					$name = $v->getClientOriginalName();
					$post->attach($k, file_get_contents($v), $name);
					$names[]=$name;
				}
			}
			// dd($names);
			$result = '';
			if(sizeof($names)>0){
				$response = $post->post($cdn, ["prefix" => $tiket->slug]);
				// dd($response->json());
				// dd($response->json());
				$result = json_decode(json_encode($response->json()));
				$banner = 0;
				$gallery = [];
				
				$data = '';

				if(isset($result->banner)){
					$banner = $result->banner->id;
					$tiket->banner = $banner;
				}
				if(isset($result->gallery)){
					for ($i = 0; $i < sizeof($result->gallery); $i++) {
						$gallery[] = $result->gallery[$i]->id;
					}
					$tiket->gallery = implode(',',$gallery);
				}
				$tiket->save();
			}

            if($request->holiday_price_status){
                if($request->has('dates')){
                    $dates = $request->dates;
                    if(!is_array($request->dates)){
                        $temp = str_replace('[','',$request->dates);
                        $temp = str_replace(']','',$request->dates);
                        $temp = str_replace(' ','',$request->dates);
                        $dates = explode(',',$temp);
                    }
                    for($i=0;$i<sizeof($dates);$i++){
                        TiketWisataDate::updateOrCreate(
                            [
                                'target_id'=>$tiket->id,
                                'start_date'=>$dates[$i]
                            ],
                            [
                                'status'=>$request->holiday_price_status ? 1 : 0,
                            ]
                        );
                    }
                    TiketWisataDate::where('target_id',$tiket->id)->whereNotIn('start_date',$dates)->update(['status'=>0]);
                }
                if($tiket->price_holiday==0){
                    TiketWisataDate::where('target_id',$tiket->id)->update(['status' => 0]);
                }
            }else{
                TiketWisataDate::where('target_id',$tiket->id)->update(['status' => 0]);
            }

            return response()->json([
                'success'=>true,
				'message'=>'Data berhasil diubah',
				'data'=>$tiket,
            ]);
        }
        return response()->json(
			[
				'success'=>false,
				'message'=>'Data tidak ditemukan',
				'data'=>null,
			]
		);
    }

    public function myProducts(){
        $tiket = TiketWisata::where('create_user',Auth::id())->with(['category','specialDates'])->get();
        $check_date = date("Y-m-d", strtotime("+8 Hours"));
        foreach ($tiket as &$k) {
            $preurl = $this->prod ? "https://cdn.mykomodo.kabtour.com/uploads/" : "https://cdn.mykomodo.kabtour.com/uploads/";
			$banner = Media::find($k->banner);
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
            $k->price_normal = $k->price;
            $getCalendars = TiketWisataDate::where(['target_id'=>$k->id,'start_date'=>$check_date,'status'=>1])->first();
            if ($getCalendars) {
                if ($k->price_holiday && $k->price_holiday > 0) {
                    $k->price = $k->price_holiday;
                }
            }
        }
        return response()->json([
			'success'=>true,
			'message'=>'ticket fetched',
			'data'=>$tiket
		]);
    }

    public function destroy($id){
        $tiket = TiketWisata::find($id);
        if($tiket){
            TiketWisataDate::where('target_id',$tiket->id)->delete();
            $tiket->delete();
            return response()->json(
                [
                    'success'=>true,
                    'message'=>'Data berhasil dihapus'
                ]
            );
        }
        return response()->json(
			[
				'success'=>false,
				'message'=>'Data tidak ditemukan'
			]
		);
    }

    private function generateSlug($name)
    {
        $slug = str_replace(' ', '-', $name).'-'.Auth::id();
        $check = TiketWisata::where('slug', 'LIKE','%'.$slug.'%')->count();
        if ($check > 0) {
            $slug .= '-' . $check + 1;
        }
        return $slug;
    }
    
    public function checkinWisata(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
            'tiket_id' => 'required|integer',
            'nama' => 'required|string',
            'dewasa' => 'required|integer',
            'anak' => 'required|integer'
        ]);
        if ($validator->fails()) {
            return response()->json([$validator->errors(),$request->all()], 422);
        }
        $user = User::find($request->user_id);
        $date = Carbon::now('Asia/Jakarta')->addHour()->format('Y-m-d');
        $insertTime = Carbon::now('Asia/Jakarta')->addHour()->format('Y-m-d H:i:s');
        $total = $request->dewasa + $request->anak;

        $checkin = null;
        $checkinCheck = BravoTiketWisataCheckin::where('tanggal',$date)->where('tiket_id',$request->tiket_id)->first();
        if($checkinCheck){
            $checkin = BravoTiketWisataCheckin::find($checkinCheck->id);
            $checkin->total_wisatawan = $checkin->total_wisatawan + $total;
            $checkin->updated_at = $insertTime;
        }else{
            $checkin = new BravoTiketWisataCheckin();
            $checkin->tiket_id = $request->tiket_id;
            $checkin->tanggal = $date;
            $checkin->total_wisatawan = $total;
            $checkin->created_at = $insertTime;
            $checkin->updated_at = $insertTime;
        }
        $checkin->save();
        
        $manifest = null;
        $manifestCheck = BravoTiketWisataManifest::where('checkin_id',$checkin->id)->where('user_id',$user->id)->where('tiket_id',$request->tiket_id)->where('tanggal',$date)->first();
        if($manifestCheck){
            $manifest = BravoTiketWisataManifest::find($manifestCheck->id);
        }else{
            $manifest = new BravoTiketWisataManifest();
            $manifest->created_at = $insertTime;
        }
        $manifest->checkin_id = $checkin->id;
        $manifest->tiket_id = $request->tiket_id;
        $manifest->tanggal = $date;
        if($request->has('user_id')){
            $manifest->user_id = $request->user_id;
            $manifest->point = 5;
        }
        $manifest->nama = $request->nama;
        $manifest->phone = $request->phone;
        $manifest->email = $request->email;
        $manifest->dewasa = $request->dewasa;
        $manifest->anak = $request->anak;
        $manifest->total_guests = $total;
        $manifest->updated_at = $insertTime;
        $manifest->save();
        
        $totalWisatawan = BravoTiketWisataManifest::where('checkin_id',$checkin->id)->where('tiket_id',$request->tiket_id)->where('tanggal',$date)->sum('total_guests');
        $checkin->total_wisatawan = $totalWisatawan;
        $checkin->save();
        
        // Add Booking Data
        if(!$manifest->booking_id){
            $tw = TiketWisata::find($request->tiket_id);
            $customer = $user;
            // if ($tw->create_user == $customer->id) {
            //     return response()->json(
            //         [
            //             'success' => false,
            //             'message' => 'Anda tidak dapat memesan Paket Anda sendiri',
            //             'user' => $customer
            //         ]
            //     );
            // }
            $media = MediaFile::find($tw->banner);
            $pre_url = $this->prod ?  'https://cdn.mykomodo.kabtour.com/uploads/' : 'https://cdn.mykomodo.kabtour.com/uploads/';
            $tw->banner = [
                "original" => $pre_url . $media->file_path,
                "200x150" => $media->file_resize_200 != null ? $pre_url . $media->file_resize_200 : null,
                "250x200" => $media->file_resize_250 != null ? $pre_url . $media->file_resize_250 : null,
                "400x350" => $media->file_resize_400 != null ? $pre_url . $media->file_resize_400 : null,
            ];
            // $tour_parent = TourParent::find($request->parent_id);
    
            $price = 0;
            $total = 0;
            $total_guests = $manifest->total_guests;
    
            $start_date = $insertTime;
            $end_date = $insertTime;
    
            $admin_fee = 0;
            // if (!$is_private) {
            //     $admin_fee = $admin_fee * $total_guests;
            // }
    
            $list_buyer_fees = json_encode(['admin_fee' => $admin_fee, 'transfer_fee' => 0]);
            // $start_date = new \DateTime($request->start_date);
    
            $data_detail = [];
            $data_detail['tiket-wisata'] = $tw;
            // $data_detail['tour_parent'] = $tour_parent;
            $data_detail['datestart'] = $start_date;
            $data_detail['dewasa'] = $request->dewasa;
            $data_detail['anak'] = $request->anak;
            $data_detail['total_guests'] = $total_guests;
            // $start_date->modify('+ ' . max(1, $tour->duration) . ' hours');
            $data_detail['dateend'] = $end_date;
            // $data_detail['locname'] = Location::find($tour_parent->location_id)->name;
            $data_detail['locname'] = Location::find($tw->location_id)->name;
    
            $booking = new Booking();
            $booking->code = $this->generateWFCode();
            $booking->app_id = $request->app_id ?? $this->app_id;
            $booking->status = 'Selesai';
            $booking->object_id = $tw->id;
            $booking->object_model = 'tiket-wisata';
            $booking->vendor_id = $tw->create_user;
            $booking->customer_id = $customer->id;
            $booking->total = 0;
            $booking->data_detail = json_encode($data_detail);
            $booking->facilities_detail = NULL;
            $booking->total_guests = $total_guests ?? 1;
            $booking->start_date = $data_detail['datestart'];
            $booking->end_date = $data_detail['dateend'];
            $booking->create_user = $customer->id;
            $booking->vendor_service_fee_amount = 0.00;
            $booking->vendor_service_fee = 0.00;
            $booking->buyer_fees = $list_buyer_fees ?? '';
            $booking->total_before_fees = $total;
            $booking->first_name = $request->first_name ?? $customer->first_name;
            $booking->last_name = $request->last_name ?? $customer->last_name;
            $booking->email = $request->email ?? $customer->email;
            $booking->address = $request->line1 ?? $request->address ?? $customer->address;
            // $booking->address2 = $request->line2 ?? $request->address2 ?? $customer->address;
            $booking->country = $request->country ?? $customer->country;
            $booking->zip_code = $request->zipcode ?? $customer->zip_code;
            $booking->city = $request->city ?? $customer->city;
            $booking->state = $request->province ?? $customer->state;
            $booking->phone = $request->phone ?? $customer->phone;
            $booking->gateway = 'xendit';
            $booking->status = 'Selesai';
            $booking->save();
            
            $payment = new Payment;
            $payment->trx_id = Str::orderedUuid();
            $payment->code = $booking->code;
            $payment->channel_code = 'FREE';
            $payment->channel_name = 'FREE';
            $payment->bill_no = str_replace("-","",$checkin->tanggal).$booking->code;
            $payment->bill_date = $insertTime;
            $payment->bill_expired = $insertTime;
            $payment->redirect_url = null;
            $payment->status = 'Payment Sukses';
            $payment->payment_date = $insertTime;
            $payment->created_at = $insertTime;
            $payment->updated_at = $insertTime;
            $payment->save();
            
            $manifest->booking_id = $booking->id;
            $manifest->save();
        }else{
            $tw = TiketWisata::find($request->tiket_id);
            $customer = $user;
            // if ($tw->create_user == $customer->id) {
            //     return response()->json(
            //         [
            //             'success' => false,
            //             'message' => 'Anda tidak dapat memesan Paket Anda sendiri',
            //             'user' => $customer
            //         ]
            //     );
            // }
            $media = MediaFile::find($tw->banner);
            $pre_url = $this->prod ?  'https://cdn.mykomodo.kabtour.com/uploads/' : 'https://cdn.mykomodo.kabtour.com/uploads/';
            $tw->banner = [
                "original" => $pre_url . $media->file_path,
                "200x150" => $media->file_resize_200 != null ? $pre_url . $media->file_resize_200 : null,
                "250x200" => $media->file_resize_250 != null ? $pre_url . $media->file_resize_250 : null,
                "400x350" => $media->file_resize_400 != null ? $pre_url . $media->file_resize_400 : null,
            ];
            // $tour_parent = TourParent::find($request->parent_id);
    
            $price = 0;
            $total = 0;
            $total_guests = $manifest->total_guests;
    
            $start_date = $insertTime;
            $end_date = $insertTime;
    
            $admin_fee = 0;
            // if (!$is_private) {
            //     $admin_fee = $admin_fee * $total_guests;
            // }
    
            $list_buyer_fees = json_encode(['admin_fee' => $admin_fee, 'transfer_fee' => 0]);
            // $start_date = new \DateTime($request->start_date);
    
            $data_detail = [];
            $data_detail['tiket-wisata'] = $tw;
            // $data_detail['tour_parent'] = $tour_parent;
            $data_detail['datestart'] = $start_date;
            $data_detail['dewasa'] = $request->dewasa;
            $data_detail['anak'] = $request->anak;
            $data_detail['total_guests'] = $total_guests;
            // $start_date->modify('+ ' . max(1, $tour->duration) . ' hours');
            $data_detail['dateend'] = $end_date;
            // $data_detail['locname'] = Location::find($tour_parent->location_id)->name;
            $data_detail['locname'] = Location::find($tw->location_id)->name;
            
            $booking = Booking::find($manifest->booking_id);
            $booking->data_detail = json_encode($data_detail);
            $booking->total_guests = $manifest->total_guests;
            $booking->save();
        }
        

        return response()->json([
            'success'=>true,
            'message'=>'Checkin Success'
        ]);
    }
    
    public function generateWFCode()
    {
        $code = 'WF-2-' . substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 6);
        if (Booking::where('code', $code)->doesntExist())
            return $code;
        $this->generateWFCode();
    }

    public function getTotalWisatawan(Request $request){
        $q = BravoTiketWisataCheckin::query();
        if($request->has('tiket_id')){
            $q->where('tiket_id',$request->tiket_id);
        }
        if($request->has('tanggal')){
            $q->where('tanggal',$request->tanggal);
        }
        $totalWisatawan = $q->sum('total_wisatawan');
        $data = $q->get();
        return response()->json([
            'success' => true,
            'message' => 'data fetched',
            'total_wisatawan' => $totalWisatawan,
            'data' => $data
        ]);
    }

    public function getManifest(Request $request){
        $q = BravoTiketWisataManifest::query();
        if($request->has('checkin_id')){
            $q->where('checkin_id',$request->tiket_id);
        }
        if($request->has('tiket_id')){
            $q->where('tiket_id',$request->tiket_id);
        }
        if($request->has('tanggal')){
            $q->where('tanggal',$request->tanggal);
        }
        $data = $q->get();
        return response()->json([
            'success' => true,
            'message' => 'data fetched',
            'data' => $data
        ]);
    }
    
    public function getCategory(){
        $data = BravoTiketWisataCategory::makeHidden(['parent_id'])->get();
        return response()->json([
            'success'=>true,
            'data'=>$data,
            'message'=>'category fetched'
        ]);
    }
    
    public function addCategory(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        $category = new BravoTiketWisataCategory;
        $category->name = $request->name;
        $category->created_at = date('Y-m-d H:i:s');
        $category->updated_at = date('Y-m-d H:i:s');
        $category->save();
        return response()->json([
            'success'=>true,
            'message'=>'data saved successfully',
            'data'=> [
                'id' => $category->id,
                'name' => $category->name,
                'created_at' => $category->created_at,
                'updated_at' => $category->updated_at
            ]
        ]);
    }
    
    public function updateCategory($id, Request $request){
        $category = BravoTiketWisataCategory::find($id);
        if($category){
            $category->name = $request->name ?? $category->name;
            $category->updated_at = date('Y-m-d H:i:s');
            $category->save();
            return response()->json([
                'success'=>true,
                'message'=>'data updated successfully',
                'data'=> [
                    'id' => $category->id,
                    'name' => $category->name,
                    'created_at' => $category->created_at,
                    'updated_at' => $category->updated_at
                ]
            ]);
        }
        return response()->json(
			[
				'success'=>false,
				'message'=>'Data tidak ditemukan',
				'data'=>null,
			]
		);
    }
    
    public function deleteCategory($id){
        if($id==1){
            return response()->json(
    			[
    				'success'=>false,
    				'message'=>'Data default tidak dapat dihapus',
    			]
    		);
        }
        $category=BravoTiketWisataCategory::find($id);
        if($category){
            TiketWisata::where('category_id',$category->id)->update(['category_id',1]);
            $category->delete();
            return response()->json(
                [
                    'success'=>true,
                    'message'=>'Data deleted successfully'
                ]
            );
        }
        return response()->json(
			[
				'success'=>false,
				'message'=>'Data tidak ditemukan',
				'data'=>null,
			]
		);
    }

}
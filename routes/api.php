<?php

use App\Models\User;
use App\Models\Order;
use App\Models\OrderItem;

use App\Http\Controllers\BookingController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\VerifyEmailController;
use App\Http\Controllers\Api\User\EditUser;
use App\Http\Controllers\Api\User\OrderUser;
use App\Http\Controllers\Api\User\BookingUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Homestay\HomestayAPIController;
use App\Http\Controllers\Api\Pemda\PemdaAPIController;
use App\Http\Controllers\Api\Tiket\TiketWisataAPIController;
use App\Models\Location;
use App\Http\Controllers\Api\Review\ReviewAPIController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/location', function(){
    $listloc=[];
    $location = Location::where('status', 'publish')->get();
	if($location){
        foreach($location as $b=>$v){
            $listloc[]=$v;
        }
    }
    return response()->json($listloc);
});

Route::get('/homestaytest', [HomestayAPIController::class,'homestayListTest']);
Route::get('/check-date',[ProductController::class,'checkDate']);
Route::get('/review',[ReviewAPIController::class,'getReview']);

Route::get('/homestay', [HomestayAPIController::class,'homestayList']);
Route::get('/homestay/search', [HomestayAPIController::class, 'homestayList']);
Route::get('/homestay/detail/{hotel_id}', [HomestayAPIController::class, 'homestaySingle']);
Route::get('/tiket-wisata', [TiketWisataAPIController::class,'index']);
Route::get('/resto-list',[ProductController::class, 'restoList']);
Route::get('/food-beverage', [ProductController::class, 'fnbList']);
Route::get('/umkm', [ProductController::class, 'umkmList']);
Route::get('/layanan-jasa', [ProductController::class, 'layananJasaList']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/search', [ProductController::class, 'search']);
Route::get('/products/detail/{id}', [ProductController::class, 'show']);
Route::put('/store-player-id', function(Request $request){
    $uid = $request->uid;
    $user = User::find($uid);
    if(!$user->player_id || $user->player_id==''){
        $user->player_id=$request->token;
    }
    else{
        $player_ids = explode(',',$user->player_id);
        if(!in_array($request->token,$player_ids)){
            $user->player_id.=','.$request->token;
        }
    }
    $user->update_user=$user->id;
    $user->updated_at=date('Y-m-d H:i:s',strtotime('+7 Hours'));
    $user->save();
    return response()->json(['success'=>true,'message'=>'Token successfully stored.']);
});

Route::get('/keyword-search',[ProductController::class,'keywordSearch']);

Route::get('/mailtest/{code}',[OrderUser::class,'testSendMail']);
Route::get('/about-kabupaten',[PemdaAPIController::class,'index']);
Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('/my-products/{category_id}', [ProductController::class,'myProducts']);
    Route::post('/product/add', [ProductController::class,'store']);
    Route::put('/product/update/{id}',[ProductController::class,'update']);
    Route::put('/product/stock/update',[ProductController::class,'updateStock']);
    Route::put('/product/status/update',[ProductController::class,'updateStatus']);
    Route::delete('/product/delete/{id}',[ProductController::class,'deleteProduct']);
    Route::delete('/product/variant/delete/{id}',[ProductController::class,'deleteVariant']);
    Route::delete('/product/variant/delete-all/{id}',[ProductController::class,'deleteAllVariant']);
    Route::post('/get-calendar', [ProductController::class,'getCalendar']);
    Route::put('/save-calendar', [ProductController::class,'saveCalendar']);
    Route::post('/order/umkm', [OrderUser::class,'createOrderUMKM']);
    Route::post('/order/food-beverage', [OrderUser::class,'createOrderFB']);
    Route::put('/order/update/{code}', [OrderUser::class,'updateOrderStatus']);

    //homestay
    Route::get('/my-homestay',[HomestayAPIController::class,'myHomestay']);
    Route::post('/homestay/save',[HomestayAPIController::class,'saveHomestay']);
    Route::put('/homestay/update/{id}',[HomestayAPIController::class,'updateHomestay']);
    Route::delete('/homestay/delete/{id}',[HomestayAPIController::class,'deleteHomestay']);
    Route::get('/get-rooms/{hotel_id}',[HomestayAPIController::class,'listRoom']);
    Route::post('/homestay/{hotel_id}/room/save',[HomestayAPIController::class,'saveRoom']);
    Route::put('/homestay/room/update/{id}',[HomestayAPIController::class,'updateRoom']);
    Route::delete('/homestay/room/delete/{id}',[HomestayAPIController::class,'deleteRoom']);

    //tiket wisata
    Route::get('/my-tickets',[TiketWisataAPIController::class,'myProducts']);
    Route::post('/ticket/add',[TiketWisataAPIController::class,'store']);
    Route::put('/ticket/update/{id}',[TiketWisataAPIController::class,'update']);
    Route::delete('/ticket/delete/{id}',[TiketWisataAPIController::class,'destroy']);
    Route::post('/ticket/category/add',[TiketWisataAPIController::class,'addCategory']);
    Route::put('/ticket/category/update/{id}',[TiketWisataAPIController::class,'updateCategory']);
    Route::delete('/ticket/category/delete/{id}',[TiketWisataAPIController::class,'deleteCategory']);

    //review
    Route::post('/review/add',[ReviewAPIController::class,'addReview']);
    Route::put('/review/update/{id}',[ReviewAPIController::class,'updateReview']);
    Route::get('/review/summary',[ReviewAPIController::class,'getReviewSummary']);

    //courier tracking
    Route::post('/store-courier-location',[BookingController::class,'storeCourierLocation']);
    Route::get('/get-courier-location',[BookingController::class,'getCourierLocation']);
    Route::get('/get-shipping-orders',[BookingController::class,'getShippingOrders']);

    //test notif
    Route::get('test-notif/{id}',[ReviewAPIController::class,'testSendNotif']);
    
    //check request
    Route::post('/check-request',[HomestayAPIController::class,'checkRequest']);

    //tentang Kabupaten
    // Route::get('/about-kabupaten',[PemdaAPIController::class,'index']);
    Route::post('/about-kabupaten',[PemdaAPIController::class,'store']);
    Route::put('/about-kabupaten/{id}',[PemdaAPIController::class,'update']);
});
// Ini Hoax
// Route::post('/add-product',function(Request $request){
//     return response()->json(['status'=>200,'msg'=>'Produk Berhasil Ditambah','id'=>157,$request->all()]);
// });
// Route::put('/update-product/{product_id}',function(Request $request,$product_id){
//     return response()->json(['status'=>200,'msg'=>'Produk Berhasil Diubah','id'=>$product_id,$request->all()]);
// });
// Route::get('/delete-product/{product_id}',function($product_id){
//     return response()->json(['status'=>200,'msg'=>'Produk Berhasil Dihapus']);
// });

Route::post('/mitra-verification/{mitra_id}',function(Request $request,$mitra_id){
    return response()->json([
        'status'=>200,
        'msg'=>'Data Verifikasi telah diterima, mohon menunggu data Anda diverifikasi oleh Verifikator',
        'mitra_id'=>$mitra_id,
        'data'=>collect()->merge($request->all())
    ]);
});

Route::get('/mitra-suspend/{mitra_id}',function(Request $request,$mitra_id){
    return response()->json([
        'status'=>200,
        'msg'=>'User berhasil di Suspend',
        'data'=>[
            'mitra_id'=>$mitra_id,
            'email' => 'bariza.fahri@gmail.com',
            'status'=>'Suspended',
        ]
    ]);
});

Route::get('/mitra-unsuspend/{mitra_id}',function(Request $request,$mitra_id){
    return response()->json([
        'status'=>200,
        'msg'=>'Suspend telah dibuka',
        'data'=>[
            'mitra_id'=>$mitra_id,
            'email' => 'bariza.fahri@gmail.com',
            'status'=>'Active',
        ]
    ]);
});
// End of Hoax

Route::get('forgot-password/{token}', [AuthController::class, 'forgotPasswordValidate']);
Route::post('forgot-password', [AuthController::class, 'resetPassword']);

Route::put('reset-password', [AuthController::class, 'updatePassword']) ;

Route::group(['middleware' => 'api','prefix' => 'auth'], function ($router) {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);

    // User cuk!
    Route::get('/user-profile', [EditUser::class, 'userProfile']);    
    Route::put('/user-profile/{id}', [EditUser::class, 'update']);

    // Ordernya User
    Route::get('/order-list', [OrderUser::class, 'orderList']);
    Route::get('/order-detail/{order_id}', [OrderUser::class, 'orderDetail']);
    Route::put('/order-cancel/{order_id}', [OrderUser::class, 'cancelOrder']);

    // Bookingnya User
    Route::get('/booking-list', [BookingUser::class, 'bookingList']);

    // Verify email
    Route::get('/email/verify/{id}/{hash}', [VerifyEmailController::class, '__invoke'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    // Resend link to verify email
    Route::post('/email/verify/resend', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();
        // return back()->with('message', 'Verification link sent!');
        return response()->json(['message' => 'Verification link sent!'],200);
    })->middleware(['auth:api', 'throttle:6,1'])->name('verification.send');


    // Route::post('/products', [ProductController::class, 'store']);
    // Route::put('/products/{id}', [ProductController::class, 'update']);
    // Route::delete('/products/{id}', [ProductController::class, 'destroy']);
});

Route::post('/checkin-wisata',[TiketWisataAPIController::class,'checkinWisata']);
Route::get('/tiket-wisata/total-wisatawan',[TiketWisataAPIController::class,'getTotalWisatawan']);
Route::get('/tiket-wisata/manifest-total-wisatawan',[TiketWisataAPIController::class,'getManifest']);
Route::get('/tiket-wisata/get-category',[TiketWisataAPIController::class,'getCategory']);
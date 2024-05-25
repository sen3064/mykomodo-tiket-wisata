<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\User;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderMail;
use App\Models\Media;

class OrderUser extends Controller
{
    public function orderList()
    {
        $orders = Order::where('user_id',Auth::user()->id)->get();
        return response()->json(['status'=>200,'message'=>'Data retrive success',$orders]);
    }

    public function orderDetail($order_id)
    {
        $this->order_items = OrderItem::with('order')->where('order_id',$order_id)->get();
        // dd($this->orders);
        $this->transaction = Transaction::where('order_id',$order_id)->first(); 
        $this->order = Order::where('id',$order_id)->first();
        $ords = $this->order->code_booking;
        $this->payment = Payment::where('code',$ords)->first();
        $this->status_order = "Selesai";
        $this->order_id = $order_id;

        return response()->json(['status'=>200, 'message'=>'Data retrive',$this->order_items]);        
    }

    public function createOrderUMKM(Request $request){
        // $total = intval(str_replace(',','',session()->get('checkout')['total']));
		// $total = ($total+4440);
        $total = 0;
        $subtotal = 0;
        $admin_fee = 0;
        $orderItems = [];
        $user = Auth::user();
		$order = new Order();
		$order->user_id = $user->id;
		$order->subtotal = 0;
		$order->discount = 0;
		$order->tax = 0;
		$order->total = 0;
		$order->firstname = $request->firstname ?? $request->first_name ?? $user->first_name;
		$order->lastname = $request->lastname ?? $request->last_name ?? $user->last_name;
        $mobile = $request->phone ?? $user->phone; 
    	if(substr($mobile,0,1)=='0'){
			$temp_phone = substr($mobile,1);
			$mobile='62'.$temp_phone;
		}
		$order->mobile = $mobile;
		$order->email = $request->email ?? $user->email;
		$order->line1 = $request->line1 ?? $request->address ?? $user->address;
		$order->line2 = $request->line2 ?? $request->address2 ?? $user->address2;
		$order->city = $request->city ?? $user->city;
		$order->province = $request->province ?? $user->state;
		$order->country = $request->country ?? $user->country;
		$order->zipcode = $request->zipcode ?? $user->zip_code;
		$order->status = 'draft';
		$order->mode_pay = $request->payment_channel ?? 'offline_payment';
		$order->shiptype = $request->shiptype ?? 'Diantar';
    	$order->code_booking = $this->generateCode('U');
		$order->is_shipping_different = $request->shipToDiff ? 1:0;
		$order->save();

		foreach (json_decode($request->item) as $item) 
		{
            $product = Product::find($item->id);
			$orderItem = new OrderItem();
			$orderItem->product_id = $product->id;
			$orderItem->name_product = $product->name;
			$orderItem->image_product = Media::find($item->banner);
			$orderItem->order_id = $order->id;
			$orderItem->mode_pay = $order->mode_pay;
        	$orderItem->ship_address = $order->line1;
			$orderItem->shiptype = $order->shiptype;
			// $orderItem->sku = $item->sku;
            $orderItem->status = 'draft';
			$orderItem->code_booking = $order->code_booking;
			$orderItem->price = $item->price*$item->qty;
			$orderItem->quantity = $item->qty;
			$orderItem->save();
            array_push($orderItems,$orderItem);
            $subtotal += $orderItem->price;
            $total += $orderItem->price;
			
			// mengurangi stok produk
			// $product = Product::find($item->id);
			$substock = $product->stock_quantity - $orderItem->quantity;
			$product->stock_quantity = $substock;
			if($product->stock_quantity==0){
				$product->stock_status = 'kosong';
			}
			$product->save();
			$admin_fee = $admin_fee+(getAdminFee($item->price)*$item->qty);
            $total += $admin_fee;
        }
        $order->subtotal = $subtotal;
		$order->total = $total;
		$order->save();
        return response()->json([
            'success'=>true,
            'message'=>'Order ditambahkan',
            'data'=>[
                'admin_fee'=>$admin_fee,
                'order'=>$order,
                'items'=>$orderItems
            ]
        ]);
    }

    public function createOrderFB(Request $request){
        // $total = intval(str_replace(',','',session()->get('checkout')['total']));
		// $total = ($total+4440);
        $total = 0;
        $subtotal = 0;
        $admin_fee = 0;
        $orderItems = [];
        $user = Auth::user();
		$order = new Order();
		$order->user_id = $user->id;
		$order->subtotal = 0;
		$order->discount = 0;
		$order->tax = 0;
		$order->total = 0;
		$order->firstname = $request->firstname ?? $request->first_name ?? $user->first_name;
		$order->lastname = $request->lastname ?? $request->last_name ?? $user->last_name;
        $mobile = $request->phone ?? $user->phone; 
    	if(substr($mobile,0,1)=='0'){
			$temp_phone = substr($mobile,1);
			$mobile='62'.$temp_phone;
		}
		$order->mobile = $mobile;
		$order->email = $request->email ?? $user->email;
		$order->line1 = $request->line1 ?? $request->address ?? $user->address;
		$order->line2 = $request->line2 ?? $request->address2 ?? $user->address2;
		$order->city = $request->city ?? $user->city;
		$order->province = $request->province ?? $user->state;
		$order->country = $request->country ?? $user->country;
		$order->zipcode = $request->zipcode ?? $user->zip_code;
		$order->status = 'draft';
		$order->mode_pay = $request->payment_channel ?? 'offline_payment';
		$order->shiptype = $request->shiptype ?? 'Diantar';
    	$order->code_booking = $this->generateCode('FB');
		$order->is_shipping_different = $request->shipToDiff ? 1:0;
		$order->save();

		foreach ($request->item as $item) 
		{
            $orderItem = new OrderItem();
            if($item->variant_id && $item->variant_id!='null'){
                $product = ProductVariant::find($item->variant_id);
                $orderItem->product_id = $product->product_id;
                $orderItem->variant_id = $product->id;
            }else{
                $product = Product::find($item->id);
                $orderItem->product_id = $product->id;
                $orderItem->variant_id = null;
            }
			
			$orderItem->name_product = $product->name;
			$orderItem->image_product = Media::find($item->banner);
			$orderItem->order_id = $order->id;
			$orderItem->mode_pay = $order->mode_pay;
        	$orderItem->ship_address = $order->line1;
			$orderItem->shiptype = $order->shiptype;
			// $orderItem->sku = $item->sku;
            $orderItem->status = 'draft';
			$orderItem->code_booking = $order->code_booking;
			$orderItem->price = $item->price*$item->qty;
			$orderItem->quantity = $item->qty;
			$orderItem->save();
            array_push($orderItems,$orderItem);
            $subtotal += $orderItem->price;
            $total += $orderItem->price;
			
			// mengurangi stok produk
			// $product = Product::find($item->id);
			$substock = $product->stock_quantity - $orderItem->quantity;
			$product->stock_quantity = $substock;
			if($product->stock_quantity==0){
				$product->stock_status = 'kosong';
			}
			$product->save();
			$admin_fee = $admin_fee+(getAdminFee($item->price)*$item->qty);
            $total += $admin_fee;
        }
        $order->subtotal = $subtotal;
		$order->total = $total;
		$order->save();
        return response()->json([
            'success'=>true,
            'message'=>'Order ditambahkan',
            'data'=>[
                'admin_fee'=>$admin_fee,
                'order'=>$order,
                'items'=>$orderItems
            ]
        ]);
    }

    public function updateOrderStatus(Request $request, $code){
        $response = [
            'success' => false,
            'message' => ''
        ];
        // return $code;
        $temp_order = Order::where('code_booking',$code)->first();
        if($temp_order){
            $order = Order::find($temp_order->id);
            $order->status = $request->status;
            if($request->has('gateway')){
                $order->mode_pay = $request->gateway;
                // $temp_fee = json_decode($order->buyer_fees);
                $temp_total = $order->total+4440;
                $order->total = $temp_total;
            }
            if($request->has('shiptype')){
                $order->shiptype = $request->shiptype;
            }
            if($request->has('firstname') || $request->has('first_name')){
                $order->firstname = $request->firstname ?? $request->first_name;
            }
            if($request->has('lastname') || $request->has('last_name')){
                $order->firstname = $request->lastname ?? $request->last_name;
            }
            if($request->has('phone')){
                $mobile = $request->phone; 
                if(substr($mobile,0,1)=='0'){
                    $temp_phone = substr($mobile,1);
                    $mobile='62'.$temp_phone;
                }
                $order->mobile = $mobile;
            }
            if($request->has('address')){
                $order->line1 = $request->address;
            }
            if($request->has('notes')){
                $order->notes = $request->notes;
            }
            $order->save();

            $this->token = $request->bearerToken();
            $payurl = "https://pgapidev.pulo1000.com/v2/create-payment/".$code;
            $pay = json_decode(
                json_encode(
                    Http::withToken($this->token)->withoutVerifying()->withOptions(["verify" => false])->acceptJson()->get($payurl)->json()
                )
            );
            // return $pay;
            $order->payment_id = $pay->data->id;
            $order->save();

            $orderItems = OrderItem::where('code_booking',$code)->get();

            foreach($orderItems as $orderItem){
                $item = OrderItem::find($orderItem->id);
                $item->mode_pay = $order->mode_pay;
                $item->ship_address = $order->line1;
                $item->shiptype = $order->shiptype;
                $item->payment_id = $order->payment_id;
                $item->save();
            }

            $this->sendOrderConfirmationMail($order);

            $response['success']=true;
            $response['message']='Your order has been updated';
            $response['order_data']=$order;
            $response['payment_data']=$pay;
        }else{
            $response['success']=false;
            $response['message']='Invalid order Code';
            $response['order_data']=null;
            $response['payment_data']=null;
        }
        return response()->json($response);
    }

    private function generateCode($prefix){
        $code = $prefix.substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 6);
        if(Order::where('code_booking',$code)->doesntExist())
            return $code;
        $this->generateCode($prefix);
    }

    public function testSendMail($code){
        $checkOrder = Order::where('code_booking',$code)->first();
        if($checkOrder){
            $order = Order::find($checkOrder->id);
            $this->sendOrderConfirmationMail($order);
            return response()->json('Order Found, Please check Your Email',200);
        }
    }

    public function sendOrderConfirmationMail($order)
    {
        Mail::to($order->email)->send(new OrderMail($order));
    }

    public function cancelOrder($order_id)
    {
        $order = Order::find($order_id);
        $order->status = "Canceled";
        $order->canceled_date = DB::raw('CURRENT_DATE');
        $order->save();
        // session()->flash('order_message','Order canceled!');
        return response()->json($order);
        
        // $this->emitTo('cart-count-component','refreshComponent');
    }
}

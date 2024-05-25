<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\CourierTracking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
    public function index()
    {
        return Booking::all();
    }

    public function storeCourierLocation(Request $request)
    {
        $dt = date('Y-m-d H:i:s', strtotime("+8 Hours"));

        if ($request->has('booking_ids')) {
            $booking_ids = explode(',', $request->booking_ids);
            
            foreach ($booking_ids as $k => $v) {
                $tracking = new CourierTracking();
                $tracking->booking_id = $v;
                $tracking->courier_id = Auth::id();
                $tracking->latitude = $request->latitude;
                $tracking->longitude = $request->longitude;
                $tracking->created_at = $dt;
                $tracking->updated_at = $dt;
                $tracking->save();
            }
        }else{
            $tracking = new CourierTracking();
            $tracking->booking_id = null;
            $tracking->courier_id = Auth::id();
            $tracking->latitude = $request->latitude;
            $tracking->longitude = $request->longitude;
            $tracking->created_at = $dt;
            $tracking->updated_at = $dt;
            $tracking->save();
        }

        return response()->json(['success' => true, 'message' => 'courier location received'], 200);
    }

    public function getCourierLocation(Request $request)
    {
        if (!$request->has('courier_id')) {
            return response()->json(['success' => false, 'message' => 'Required courier_id'], 401);
        }
        $query = CourierTracking::where('courier_id', $request->courier_id);

        if ($request->has('booking_id')) {
            $query->where('booking_id', $request->booking_id);
        }

        $data = $query->latest()->first();

        return response()->json(['success'=>true, 'message'=>'data fetched', 'data'=>$data], 200);
    }


    public function getShippingOrders(){
        $success = true;
        $message = 'Data Fetched';
        $data = Booking::where('courier_id',Auth::id())->whereIn('status',['Shipping','shipping','Dikirim','dikirim'])->get('id');
        if(!$data){
            $success = false;
            $message = 'No Data';
        }
        return response()->json(['success'=>$success,'message'=>$message, 'data'=>$data],200);
    }
}

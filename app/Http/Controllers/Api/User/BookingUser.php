<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\User;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class BookingUser extends Controller
{
    
    public function bookingList()
    {
        $bookings = Booking::where('customer_id',Auth::user()->id)->where('status','!=','draft')->orderBy('id','DESC')->get();
        return response()->json(['status'=>200,'message'=>'Data retrive success',$bookings]);
    }

}

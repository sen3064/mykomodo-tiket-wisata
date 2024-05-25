<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Mail\ResetPassword;
use App\Mail\VarifyPassword;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;
use DB;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6|string|confirmed'
        ]);

        $data = $request->all();
        $check = $this->create($data);
        if($check){
          DB::table('core_model_has_roles')->insert([
            'role_id'=>2,
            'model_type'=>'App\User',
            'model_id'=>$check->id
          ]);
        }
        Auth::login($check);

        $token = $check->createToken('myapptoken')->accessToken;

        $response = [
            'user' => $check,
            'token' => $token
        ];

        return response($response, 201);
    }

    public function create(array $data)
    {
        return User::create([
            'name' => $data['first_name'] . ' ' . $data['last_name'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'is_open' => 1,
            'always_open' => 0,
            'email' => $data['email'],
            // 'email_verified_at' => now(),
            'password' => Hash::make($data['password'])
        ]);
    }

    public function login(Request $request){
        $fields = $request->validate([
            'email' => 'required|string',
            'password' => 'required|string'
        ]);

        $user = User:: where('email', $fields['email'])->first();
        if(!$user || !Hash::check($fields['password'], $user->password)){
            return response([
                'message' => 'Salah masukin'
            ],401);
        }

        $token = $user->createToken('myapptoken')->plainTextToken;

        $response = [
            'user' => $user,
            'token' => $token
        ];

        return response($response, 201);
    }
    
    public function forgotPasswordValidate($token)
    {
        $user = User::where('token', $token)->where('is_verified', 0)->first();
        if ($user) {
            $email = $user->email;
            
        return response([
                'status' => 200,
                'message'=>'Success'
            ],200);
        }
            return response([
                'status' => 401,
                'message' => 'Failed.'
            ],401);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            $status = 404;
            $message = 'Failed! email is not registered.';

            $response = ['status'=> $status,'message' => $message];
            return response()->json($response);
        }

        // $token = $user->createToken('myapptoken')->plainTextToken;

        // $response = [
        //     'user' => $user,
        //     'token' => $token
        // ];

        // $token = Str::random(60);
        $token = random_int(100000, 999999);
        $checkToken = User::where('token',$token)->first();
        if(!$checkToken){
            $user['token'] = $token;
            $user['is_verified'] = 0;
            $user->save();
        }
        

        Mail::to($request->email)->send(new ResetPassword($user->name, $token));

        if(Mail::failures() != 0) {
            $status = 200;
            $message = 'Kode OTP reset password sudah dikirim ke email anda';
        }
        $response = ['status'=> 200,'message' => $token];
        return response()->json($response);
        // return back()->with('failed', 'Failed! there is some issue with email provider');
    }

    /**
     * Change password
     * @param request
     * @return response
     */
    public function updatePassword(Request $request) {
        $this->validate($request, [
            'email' => 'required',
            'password' => 'required|min:6',
            'confirm_password' => 'required|same:password'
        ]);

        $user = User::where('email', $request->email)->first();
        if ($user) {
            $user['is_verified'] = 0;
            $user['token'] = '';
            $user['password'] = Hash::make($request->password);
            $user->save();
            return response(['messages' => 'Success'],200);
        }
        return response(['messages' => 'Failed'],401);
    }

    public function logout(Request $request){
        auth()->user()->tokens()->delete();

        return [
            'message' => 'Logged Out'
        ];
    }
}

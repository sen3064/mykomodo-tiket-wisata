<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Mail\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Validator;
use DB;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct() {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }
    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request){
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        if (! $token = auth()->attempt($validator->validated())) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        return $this->createNewToken($token);
    }
    /**
     * Register a User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request) {
        // $validator = Validator::make($request->all(), [
        //     'first_name' => 'required|string|between:2,100',
        //     'last_name' => 'required|string|between:2,100',
        //     'email' => 'required|string|email|max:100|unique:users',
        //     'password' => 'required|string|confirmed|min:6',
        // ]);

        // $check = $this->create($validator);
        // if($check){
        //   DB::table('core_model_has_roles')->insert([
        //     'role_id'=>1,
        //     'model_type'=>'App\User',
        //     'model_id'=>$check->id
        //   ]);
        // }
        $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'role_id' => 'required'
        ]);
           
        $data = $request->all();
        $check = $this->create($data);
        if($check){
          DB::table('core_model_has_roles')->insert([
            'role_id'=>$request->role_id,
            'model_type'=>'App\User',
            'model_id'=>$check->id
          ]);
        }
        Auth::login($check);

        return response()->json([
            'message' => 'User successfully registered',
            // 'user' => $user
        ], 201);
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
            'password' => Hash::make($data['password'])
        ]);
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

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout() {
        auth()->logout();
        return response()->json(['message' => 'User successfully signed out']);
    }
    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh() {
        return $this->createNewToken(auth()->refresh());
    }
    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    // public function userProfile() {
    //     return response()->json(auth()->user());
    // }
    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function createNewToken($token){
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            'user' => auth()->user()
        ]);
    }
}

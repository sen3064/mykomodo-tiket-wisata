<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class EditUser extends Controller
{
    public function userProfile() {
        return response()->json(auth()->user());
    }    

    public function update(Request $request, $id)
    {
        $users = User::find($id);
        $users->update($request->all());
        return $users;
    }
}

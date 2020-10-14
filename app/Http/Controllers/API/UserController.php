<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Http\Controllers\Controller;

class UserController extends Controller
{
    public function __construct() {
        $this->middleware('check.role:users:getAll')->only(['index', 'destroy']);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $users = User::all();
            return response($users, 200);
        } catch (\Exception $e){
            return response(["error"=>$e->getMessage()], 500);
        }

    }

    public function logout() {
        $user = auth()->user();
        try {
            $user->currentAccessToken()->delete();
            return response(["message"=>"See you soon!"], 200);
        } catch (\Exception $e){
            return response(["error"=>$e->getMessage()],500);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function signUp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "name" => ['required'],
            "password" => ['min:6','required'],
            "email" => ['email:rfc,dns', 'required'],
            "phoneNo" => ['size:10','starts_with:024,054,059,055,020,050,026,056,027,057', 'required']
        ]);

        if($validator->fails()){
            return response()->json($validator->messages(),400);
        }

        try {
            $user = User::create($request->all());
            $user->save();
            $token = $user->generateAuthToken();
            return response(["user"=>$user, "token"=>$token], 201);
        } catch (\Exception $e) {
            return response(["error"=>$e->getMessage()], 500);
        }

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getUser()
    {
        $user = auth()->user();
        return response()->json($user, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            "email" => ['email:rfc,dns'],
            "phoneNo" => ['size:10','starts_with:024,054,059,055,020,050,026,056,027,057']
        ]);

        if($validator->fails()){
            return response()->json($validator->messages(),400);
        }

        try {
            $user->update($request->all());
            return response()->json($user, 200);
        }  catch (\Exception $e) {
            return response()->json(["error"=>"An error occurred"], 500);
        }

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
            try {
                $user = User::where('id',$id)->first();
                User::deleteUser($user);
                return response(["message"=>"User: deleted"], 200);
            } catch (\Exception $e){
                return response(["error"=>$e->getMessage()], 500);
            }
    }

    public function deleteMe(){
        $user = auth()->user();
        try {
            User::deleteUser($user);
            return response()->json(["message"=>"Sad to see you go"], 200);
        }catch (\Exception $e){
            return response()->json(["error"=>$e->getMessage()], 500);
        }
    }

    public function login(Request $request) {
        $email = $request["email"];
        $password = $request["password"];
        try {
            $user = User::getUserWithCredentials($email, $password);
            $token = $user->generateAuthToken();
            return response()->json(["user"=>$user, "token"=>$token],200);
        } catch (\Exception $e){
            return response()->json(["error"=>$e->getMessage()],404);
        }

    }
}

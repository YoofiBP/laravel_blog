<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Http\Controllers\Controller;

class UserController extends Controller
{
    //TODO: Implement something with query strings https://laravel.com/docs/8.x/requests#retrieving-input
    public function __construct() {
        $this->middleware('check.role:role:admin')->only(['index', 'destroy']);
    }

    public function validateEntries($input, $rules) {
        $validator = Validator::make($input, $rules);
        if($validator->fails()){
            return $validator->messages();
        }
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
        try {
            $user = auth()->user();
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
        $rules = [
            "name" => ['required'],
            "password" => ['min:6','required'],
            "email" => ['email', 'required'],
            "phoneNo" => ['size:10','starts_with:024,054,059,055,020,050,026,056,027,057', 'required']
        ];

        $validationMessages = $this->validateEntries($request->all(), $rules);

        if($validationMessages){
            return response()->json($validationMessages, 400);
        } else {
            try {
                $user = User::create($request->all());
                $user->save();
                $token = $user->generateAuthToken();
                return response(["user" => $user, "token" => $token], 201);
            } catch (\Exception $e) {
                return response(["error" => $e->getMessage()], 500);
            }
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
        $rules = [
            "email" => ['email'],
            "phoneNo" => ['size:10','starts_with:024,054,059,055,020,050,026,056,027,057']
        ];

        $validationMessages = $this->validateEntries($request->except('password'), $rules);

        if($validationMessages){
            return response()->json($validationMessages,400);
        } else {
            try {
                $user->update($request->except('password'));
                return response()->json($user, 200);
            }  catch (\Exception $e) {
                return response()->json(["error"=>$e->getMessage()], 500);
            }
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

<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\StoreUser;
use App\Http\Requests\UpdateUser;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Http\Controllers\Controller;

class UserController extends Controller
{
    //TODO: Implement something with query strings https://laravel.com/docs/8.x/requests#retrieving-input

    public function __construct()
    {
        $this->middleware('check.role:role:admin')->only(['index', 'destroy']);
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
        } catch (\Exception $e) {
            return response(["error" => $e->getMessage()], 500);
        }

    }

    public function logout()
    {
        try {
            $user = auth()->user();
            $user->currentAccessToken()->delete();
            return response(["message" => "See you soon!"], 200);
        } catch (\Exception $e) {
            return response(["error" => $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */


    public function signUp(StoreUser $request)
    {
        //TODO: Refactor to move user creation logic out of controller

        $data = $request->validated();

        try {
            $user = User::create($data);
            $user->save();
            $token = $user->generateAuthToken();
            return response(["user" => $user, "token" => $token], 201);
        } catch (\Exception $e) {
            return response(["error" => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
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
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateUser $request, User $user)
    {

        $data = $request->validated();

        try {
            $user->update($data);
            return response()->json($user, 200);
        } catch (\Exception $e) {
            return response()->json(["error" => $e->getMessage()], 500);
        }

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //Refactor out delete user function to handle all deletion
        try {
            $user = User::where('id', $id)->first();
            User::deleteUser($user);
            return response(["message" => "User: deleted"], 200);
        } catch (\Exception $e) {
            return response(["error" => $e->getMessage()], 500);
        }
    }

    public function deleteMe()
    {
        $user = auth()->user();
        try {
            User::deleteUser($user);
            return response()->json(["message" => "Sad to see you go"], 200);
        } catch (\Exception $e) {
            return response()->json(["error" => $e->getMessage()], 500);
        }
    }

    public function login(Request $request)
    {
        $email = $request["email"];
        $password = $request["password"];
        try {
            $user = User::getUserWithCredentials($email, $password);
            $token = $user->generateAuthToken();
            return response()->json(["user" => $user, "token" => $token], 200);
        } catch (\Exception $e) {
            return response()->json(["error" => $e->getMessage()], 404);
        }

    }
}

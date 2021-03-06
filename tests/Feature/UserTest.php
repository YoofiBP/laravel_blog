<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class UserTest extends TestCase
{
    use RefreshDatabase;

    private $user;

    public function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub
        $this->user = User::factory()->create();
    }

    public function testShouldCreateNewUserSuccessfully() {
        $userInfo = ["name" => "Yoofi", "email"=>"yoofi@gmail.com", "password" => "Dilweed86!", "phoneNo" => "0248506381", "address" => "Comm 18"];
        $response = $this->withHeader("Accept", "application/json")->post(route('user.signup'), $userInfo);
        $expectedResponse = ["name" => "Yoofi", "email"=>"yoofi@gmail.com", "phoneNo" => "233248506381", "address" => "Comm 18"];
        $response->assertStatus(201);
        $response->assertJson(["user" => $expectedResponse]);
        $response->assertJsonStructure(['user', 'token']);
        $this->assertDatabaseHas('users', $expectedResponse);
    }

    public function testShouldNotCreateNewUserWhenEmailAlreadyExists() {
        $userInfo = ["name" => "Yoofi", "email"=>$this->user["email"], "password" => "Dilweed86!", "phoneNo" => "0248506381", "address" => "Comm 18"];
        $response = $this->withHeader("Accept", "application/json")->post(route('user.signup'), $userInfo);
        $response->assertStatus(500);
    }

    public function testShouldNotCreateNewUserWithoutRequiredFields()
    {
        $userInfo = ["email" => "yoofi@gmail.com", "password" => "Dilweed86!", "phoneNo" => "0248506381", "address" => "Comm 18"]; //no name
        $response = $this->withHeader("Accept", "application/json")->post(route('user.signup'), $userInfo);
        $response->assertStatus(400);

        $userInfo = ["name" => "Yoofi", "password" => "Dilweed86!", "phoneNo" => "0248506381", "address" => "Comm 18"]; // no email
        $response = $this->withHeader("Accept", "application/json")->post(route('user.signup'), $userInfo);
        $response->assertStatus(400);

        $userInfo = ["name" => "Yoofi", "email" => "yoofi@gmail.com", "phoneNo" => "0248506381", "address" => "Comm 18"]; //no password
        $response = $this->withHeader("Accept", "application/json")->post(route('user.signup'), $userInfo);
        $response->assertStatus(400);

        $userInfo = ["name" => "Yoofi", "email" => "yoofi@gmail.com", "password" => "Dilweed86!", "address" => "Comm 18"]; //no phone number
        $response = $this->withHeader("Accept", "application/json")->post(route('user.signup'), $userInfo);
        $response->assertStatus(400);
    }

    public function testShouldNotCreateNewUserWhenPasswordLengthIsBelowSix() {
        $userInfo = ["name" => "Yoofi", "email"=>"yoofi@gmail.com", "password" => "pass", "phoneNo" => "0248506381", "address" => "Comm 18"];
        $response = $this->withHeader("Accept", "application/json")->post(route('user.signup'), $userInfo);
        $response->assertStatus(400);
    }

    public function testShouldNotCreateNewUserWhenPhoneNumberLengthIsWrong() {
        $userInfo = ["name" => "Yoofi", "email"=>"yoofi@gmail.com", "password" => "testPassword", "phoneNo" => "02485063", "address" => "Comm 18"];
        $response = $this->withHeader("Accept", "application/json")->post(route('user.signup'), $userInfo);
        $response->assertStatus(400);
}

    public function testShouldLoginUserSuccessfully() {
        $userData = ["email" => $this->user["email"], "password" => "testPassword"];
        $response = $this->withHeader("Accept", "application/json")->post(route('login'), $userData);

        $response->assertStatus(200);
        $response->assertJsonStructure(['user', 'token']);
    }

    public function testShouldCreateTokenWhenUserLogsIn() {
        $this->assertDatabaseCount('personal_access_tokens',0);
        $userData = ["email" => $this->user["email"], "password" => "testPassword"];
        $response = $this->withHeader("Accept", "application/json")->post(route('login'), $userData);

        $response->assertStatus(200);
        $this->assertDatabaseCount('personal_access_tokens',1);

        $loggedInUserId = User::where('email', $this->user["email"])->first()['id'];
        $this->assertDatabaseHas('personal_access_tokens', ['tokenable_id' => $loggedInUserId]);
    }

    public function testShouldNotLoginUserWhenPasswordIsWrong() {
        $userData = ["email" => $this->user["email"], "password" => "wrongPassword"];
        $response = $this->withHeader("Accept", "application/json")->post(route('login'), $userData);

        $response->assertStatus(404);
        $response->assertExactJson(["error" => "Unable to Login"]);
    }

    public function testShouldNotLoginUserWhenEmailDoesNotExist() {
        $userData = ["email" => "newemail@gmail.com", "password" => "testPassword"];
        $response = $this->withHeader("Accept", "application/json")->post(route('login'), $userData);

        $response->assertStatus(404);
        $response->assertExactJson(["error" => "Unable to Login"]);
    }

    public function testShouldRemoveUserTokenWhenUserLogsOut() {
        $this->assertDatabaseCount('personal_access_tokens',0);

        $userData = ["email" => $this->user["email"], "password" => "testPassword"];
        $response = $this->withHeader("Accept", "application/json")->post(route('login'), $userData);
        $token = $response["token"];

        $loggedInUserId = User::where('email', $this->user["email"])->first()['id'];

        $this->assertDatabaseCount('personal_access_tokens',1);
        $this->assertDatabaseHas('personal_access_tokens', ['tokenable_id' => $loggedInUserId]);

        $response = $this->withHeaders(["Accept", "application/json", "Authorization" => "Bearer ".$token])->post(route('logout'));

        $response->assertStatus(200);
        $this->assertDatabaseCount('personal_access_tokens',0);
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $loggedInUserId]);
    }

    public function testShouldGetUsersProfileSuccessfullyWithoutHiddenFields() {
        $this->user = Sanctum::actingAs(User::factory()->create(),['*']);
        $response = $this->withHeader("Accept", "application/json")->get(route('user.getUser'));
        $expectedJson = ["name" => $this->user["name"], "email" => $this->user["email"], "phoneNo" => $this->user["phoneNo"]];
        $hiddenJson = ["password" => $this->user["password"], "remember_token" => $this->user["remember_token"], "isAdmin" => false];

        $response->assertStatus(200);
        $response->assertJson($expectedJson);
        $response->assertJsonMissing($hiddenJson);
    }

    public function testShouldNotGetUserProfileWhenNotAuthenticated() {
        $response = $this->withHeader("Accept", "application/json")->get(route('user.getUser'));
        $response->assertStatus(401);
    }

    public function testShouldGetAllUsersWhenUserHasAdminRole() {
        User::factory()->count(2)->create();
        $this->user = Sanctum::actingAs(User::factory()->create(),['role:admin']);
        $response = $this->withHeader("Accept", "application/json")->get(route('user.index'));

        $response->assertStatus(200);
        $response->assertJsonCount(4);
    }

    public function testShouldNotGetAllUsersWhenUserDoesNotHaveAdminRole() {
        User::factory()->count(2)->create();
        $this->user = Sanctum::actingAs(User::factory()->create(),['role:user']);
        $response = $this->withHeader("Accept", "application/json")->get(route('user.index'));

        $response->assertStatus(403);
        $response->assertJsonStructure(['error']);
    }

    //TODO: Add test to ensure that when user is an admin the token generated is different for him

    public function testShouldDeleteUsersWhenUserHasAdminRole()  {
        $this->user = Sanctum::actingAs(User::factory()->create(),['role:admin']);
        $userId = User::first()["id"];

        $response = $this->withHeader("Accept", "application/json")->delete(route('user.destroy', ['user' => $userId]));
        $response->assertStatus(200);

        $this->assertDatabaseMissing('users', ['id' => $userId]);
        $this->assertDeleted('users', ['id' => $userId]);
    }

    public function testShouldDeleteAllAccessTokensWhenUserIsDeleted() {
        $this->assertDatabaseCount('personal_access_tokens',0);

        $userData = ["email" => $this->user["email"], "password" => "testPassword"];
        $this->withHeader("Accept", "application/json")->post(route('login'), $userData);
        $loggedInUserId = User::where('email', $this->user["email"])->first()['id'];

        $this->assertDatabaseCount('personal_access_tokens',1);
        $this->assertDatabaseHas('personal_access_tokens', ['tokenable_id' => $loggedInUserId]);

        $this->user = Sanctum::actingAs(User::factory()->create(),['role:admin']);
        $this->withHeader("Accept", "application/json")->delete(route('user.destroy', ['user' => $loggedInUserId]));

        $this->assertDatabaseCount('personal_access_tokens',0);
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $loggedInUserId]);

    }

    public function testShouldNotDeleteUsersWhenUserDoesNotHaveAdminRole() {
        $this->user = Sanctum::actingAs(User::factory()->create(),['users:viewOnly']);
        $userId = User::first()["id"];
        $response = $this->withHeader("Accept", "application/json")->delete(route('user.destroy', ['user' => $userId]));
        $response->assertStatus(403);
        $this->assertDatabaseHas('users', ['id' => $userId]);
    }

    public function testShouldUpdateUserSuccessfully() {
        $jsonUpdate = ["name" => "Yoofi", "email" => "yoofi@gmail.com", "phoneNo" => "0248506381"];
        $this->user = Sanctum::actingAs(User::factory()->create(),['users:viewOnly']);
        $userIdFromDB = User::where("email",$this->user["email"])->first()["id"];

        $response = $this->withHeader("Accept", "application/json")->patch(route('user.update', ['user' => $userIdFromDB]), $jsonUpdate);
        $response->assertStatus(200);

        $expectedRecord = ["id" => $userIdFromDB, "name" => $jsonUpdate['name'], "email" => $jsonUpdate['email'], "phoneNo" => "233". substr($jsonUpdate['phoneNo'], -9)];
        $this->assertDatabaseHas('users', $expectedRecord);
    }

    public function testShouldNotUpdateUserWhenNotAuthenticated() {
        $jsonUpdate = ["name" => "Yoofi", "email" => "yoofi@gmail.com", "phoneNo" => "0248506381"];
        $userIdFromDB = User::first()["id"];
        $response = $this->withHeader("Accept", "application/json")->patch(route('user.update', ['user' => $userIdFromDB]), $jsonUpdate);
        $response->assertStatus(401);
    }

    public function testShouldDeleteCurrentUser() {
        $this->user = Sanctum::actingAs(User::factory()->create(),['users:getAll']);
        $response = $this->withHeader("Accept", "application/json")->delete(route('user.deleteMe'));
        $response->assertStatus(200);
        $this->assertDatabaseMissing('users', ['email' => $this->user["email"]]);
    }

}

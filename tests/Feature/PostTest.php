<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->has(Post::factory()->count(1))->create();
    }

    public function testShouldStorePostSuccessfully() {
        $postJson = ["post_title" => "New Title", "post_body" => "New Body", "post_description" => "New Description"];
        $this->user = Sanctum::actingAs(User::factory()->create(),['users:getAll']);
        $response = $this->withHeader("Accept", "application/json")->post(route('posts.store'), $postJson);
        $response->assertStatus(201);
        $this->assertDatabaseHas('posts', $postJson);
    }

    public function testShouldNotStorePostIfUnauthenticated() {
        $postJson = ["post_title" => "New Title", "post_body" => "New Body", "post_description" => "New Description"];
        $response = $this->withHeader("Accept", "application/json")->post(route('posts.store'), $postJson);
        $response->assertStatus(401);
        $this->assertDatabaseMissing('posts', $postJson);
    }

    public function testShouldNotStorePostIfAnyFieldsAreEmpty() {
        $this->user = Sanctum::actingAs(User::factory()->create(),['users:getAll']);

        $postJsonNoTitle = ["post_body" => "New Body", "post_description" => "New Description"];
        $response = $this->withHeader("Accept", "application/json")->post(route('posts.store'), $postJsonNoTitle);
        $response->assertStatus(400);
        $this->assertDatabaseMissing('posts', $postJsonNoTitle);

        $postJsonNoBody = ["post_title" => "New Title", "post_description" => "New Description"];
        $response = $this->withHeader("Accept", "application/json")->post(route('posts.store'), $postJsonNoBody);
        $response->assertStatus(400);
        $this->assertDatabaseMissing('posts', $postJsonNoBody);

        $postJsonNoDescription = ["post_title" => "New Title", "post_body" => "New Body"];
        $response = $this->withHeader("Accept", "application/json")->post(route('posts.store'), $postJsonNoDescription);
        $response->assertStatus(400);
        $this->assertDatabaseMissing('posts', $postJsonNoDescription);
    }

    public function testShouldGetAllUserPostsSuccessfully() {
        $this->user = Sanctum::actingAs(User::factory()->has(Post::factory()->count(3))->create(),['users:getAll']);
        $response = $this->withHeader("Accept", "application/json")->get(route('posts.index'));
        $response->assertStatus(200);
        $response->assertJsonCount(3);
    }

    public function testShouldNotGetAllUserPostsIfNotAuthenticated() {
        $response = $this->withHeader("Accept", "application/json")->get(route('posts.index'));
        $response->assertStatus(401);
    }

    public function testShouldShowSpecificPostSuccessfully() {
        $this->user = Sanctum::actingAs(User::factory()->has(Post::factory()->count(3))->create(),['users:getAll']);
        $userIdFromDB = User::where('email', $this->user["email"])->first()["id"];
        $postIdFromDB = Post::where('user_id', $userIdFromDB)->first()["id"];
        $response = $this->withHeader("Accept", "application/json")->get(route('posts.show', ["post" => $postIdFromDB]));
        $response->assertStatus(200);
        $response->assertJson(["user_id" => $userIdFromDB]);
    }

    public function testShouldNotGetPostIfNotByUser() {
        Sanctum::actingAs(User::factory()->has(Post::factory()->count(3))->create(),['users:getAll']); // create three posts by another user
        $this->user = Sanctum::actingAs(User::factory()->has(Post::factory()->count(3))->create(),['users:getAll']);
        $userIdFromDB = User::where('email', $this->user["email"])->first()["id"];
        $postIdNotForUser = Post::where('user_id', '<>', $userIdFromDB)->first()["id"];
        $response = $this->withHeader("Accept", "application/json")->get(route('posts.show', ["post" => $postIdNotForUser]));
        $response->assertStatus(403);
        $response->assertJsonMissing(["id" => $postIdNotForUser]);
    }

    public function testShouldNotGetPostIfNotAuthenticated() {
        $this->user = User::factory()->has(Post::factory()->count(3))->create();
        $userIdFromDB = User::where('email', $this->user["email"])->first()["id"];
        $postIdFromDB = Post::where('user_id', $userIdFromDB)->first()["id"];
        $response = $this->withHeader("Accept", "application/json")->get(route('posts.show', ["post" => $postIdFromDB]));
        $response->assertStatus(401);
        $response->assertJsonMissing(["id" => $postIdFromDB, "user_id" => $userIdFromDB]);
    }

    public function testShouldUpdatePostSuccessfully() {
        $newPost = ["post_title" => "Title Changed"];
        $this->user = Sanctum::actingAs(User::factory()->has(Post::factory()->count(3))->create(),['users:getAll']);
        $userIdFromDB = User::where('email', $this->user["email"])->first()["id"];
        $postIdFromDB = Post::where('user_id', $userIdFromDB)->first()["id"];
        $response = $this->withHeader("Accept", "application/json")->patch(route('posts.update', ["post" => $postIdFromDB]), $newPost);
        $response->assertStatus(200);
        $this->assertDatabaseHas('posts', ['id' => $postIdFromDB, 'post_title' => $newPost['post_title']]);
    }

    public function testShouldNotUpdatePostIfNotAuthenticated() {
        $newPost = ["post_title" => "Title Changed"];
        $this->user = User::factory()->has(Post::factory()->count(3))->create();
        $userIdFromDB = User::where('email', $this->user["email"])->first()["id"];
        $postIdFromDB = Post::where('user_id', $userIdFromDB)->first()["id"];
        $response = $this->withHeader("Accept", "application/json")->patch(route('posts.update', ["post" => $postIdFromDB]), $newPost);
        $response->assertStatus(401);
        $this->assertDatabaseMissing('posts', ['id' => $postIdFromDB, 'post_title' => $newPost['post_title']]);
    }

    public function testShouldNotUpdatePostIfNotOwner() {
        $newPost = ["post_title" => "Title Changed"];
        Sanctum::actingAs(User::factory()->has(Post::factory()->count(3))->create(),['users:getAll']);
        $this->user = Sanctum::actingAs(User::factory()->has(Post::factory()->count(3))->create(),['users:getAll']);
        $userIdFromDB = User::where('email', $this->user["email"])->first()["id"];
        $postIdNotForUser = Post::where('user_id', '<>', $userIdFromDB)->first()["id"];
        $response = $this->withHeader("Accept", "application/json")->patch(route('posts.update', ["post" => $postIdNotForUser]), $newPost);
        $response->assertStatus(403);
    }

    public function testShouldDestroyPostSuccessfully() {
        $this->user = Sanctum::actingAs(User::factory()->has(Post::factory()->count(3))->create(),['users:getAll']);
        $userIdFromDB = User::where('email', $this->user["email"])->first()["id"];
        $postIdFromDB = Post::where('user_id', $userIdFromDB)->first()["id"];
        $response = $this->withHeader("Accept", "application/json")->delete(route('posts.destroy', ["post" => $postIdFromDB]));
        $response->assertStatus(200);
        $this->assertSoftDeleted('posts', ['id' => $postIdFromDB]);
    }

    public function testShouldNotDestroyPostIfUnAuthenticated() {
        $this->user = User::factory()->has(Post::factory()->count(3))->create();
        $userIdFromDB = User::where('email', $this->user["email"])->first()["id"];
        $postIdFromDB = Post::where('user_id', $userIdFromDB)->first()["id"];
        $response = $this->withHeader("Accept", "application/json")->delete(route('posts.destroy', ["post" => $postIdFromDB]));
        $response->assertStatus(401);
    }

    public function testShouldNotDestroyPostIfNotForUser() {
        Sanctum::actingAs(User::factory()->has(Post::factory()->count(3))->create(),['users:getAll']);
        $this->user = Sanctum::actingAs(User::factory()->has(Post::factory()->count(3))->create(),['users:getAll']);
        $userIdFromDB = User::where('email', $this->user["email"])->first()["id"];
        $postIdNotForUser = Post::where('user_id', '<>', $userIdFromDB)->first()["id"];
        $response = $this->withHeader("Accept", "application/json")->delete(route('posts.destroy', ["post" => $postIdNotForUser]));
        $response->assertStatus(403);
    }

    public function testShouldCreateCommentForPostSuccessfully() {
        $commentJson = ['comment_body' => "This is a test comment"];
        $this->user = Sanctum::actingAs(User::factory()->has(Post::factory()->count(3))->create(),['users:getAll']);
        $userIdFromDB = User::where('email', $this->user["email"])->first()["id"];
        $postIdFromDB = Post::where('user_id', $userIdFromDB)->first()["id"];
        $response = $this->withHeader("Accept", "application/json")->post(route('comments.store', ["post" => $postIdFromDB]), $commentJson);
        $response->assertStatus(200);
        $this->assertDatabaseHas('comments', ['post_id' => $postIdFromDB, 'comment_body' => $commentJson["comment_body"]]);
    }

    public function testShouldNotCreateCommentIfUnauthenticated() {
        $commentJson = ['comment_body' => "This is a test comment"];
        $this->user = User::factory()->has(Post::factory()->count(3))->create();
        $postIdFromDB = $this->user->posts[0]["id"];
        $response = $this->withHeader("Accept", "application/json")->post(route('comments.store', ["post" => $postIdFromDB]), $commentJson);
        $response->assertStatus(401);
        $response->assertJsonStructure(['message']);
    }

    public function testShouldGetAllCommentsOnPostSuccessfully() {
        $this->user = Sanctum::actingAs(User::factory()->create(),['users:getAll']);
        $comments = Comment::factory()->count(3)->for(Post::factory())->create();
        $postId =  $comments[0]["post_id"];
        $response = $this->withHeader("Accept", "application/json")->get(route('comments.index', ["post" => $postId]));
        $response->assertStatus(200);
        $response->assertJsonCount(3);
    }

    public function testShouldNotGetAllCommentsIfUnauthenticated() {
        $this->user = User::factory()->create();
        $comments = Comment::factory()->count(3)->for(Post::factory())->create();
        $postId =  $comments[0]["post_id"];
        $response = $this->withHeader("Accept", "application/json")->get(route('comments.index', ["post" => $postId]));
        $response->assertStatus(401);
        $response->assertJsonStructure(['message']);
    }

    public function testShouldDeleteCommentSuccessfully() {
        $this->user = Sanctum::actingAs(User::factory()->has(Post::factory()->has(Comment::factory())->count(3))->create(),['users:getAll']);
        $postId = $this->user->posts[0]["id"];
        $commentId = $this->user->posts[0]->comments[0]["id"];
        $this->assertDatabaseHas('comments', ['id' => $commentId]);
        $response = $this->withHeader("Accept", "application/json")->delete(route('comments.destroy', ["post" => $postId, "comment" => $commentId]));
        $response->assertStatus(200);
        $this->assertDatabaseMissing('comments', ['id' => $commentId]);
    }

    public function testShouldNotDeleteCommentIfNotFound() {
        $this->user = Sanctum::actingAs(User::factory()->has(Post::factory()->has(Comment::factory())->count(3))->create(),['users:getAll']);
        $postId = $this->user->posts[1]["id"];
        $commentId = $this->user->posts[0]->comments[0]["id"];
        $this->assertDatabaseHas('comments', ['id' => $commentId]);
        $response = $this->withHeader("Accept", "application/json")->delete(route('comments.destroy', ["post" => $postId, "comment" => $commentId]));
        $response->assertStatus(404);
        $response->assertJsonStructure(['error']);
        $this->assertDatabaseHas('comments', ['id' => $commentId]);
    }

    public function testShouldNotDeleteCommentIfUnauthenticated() {
        $this->user = User::factory()->has(Post::factory()->has(Comment::factory())->count(3))->create();
        $postId = $this->user->posts[0]["id"];
        $commentId = $this->user->posts[0]->comments[0]["id"];
        $this->assertDatabaseHas('comments', ['id' => $commentId]);
        $response = $this->withHeader("Accept", "application/json")->delete(route('comments.destroy', ["post" => $postId, "comment" => $commentId]));
        $response->assertStatus(401);
    }

}

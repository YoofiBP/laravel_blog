<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
//use Dotenv\Validator;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Post;

class PostController extends Controller
{
    //TODO: Create route for searching posts
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $currentUser = auth()->user();
            $userPosts = $currentUser->posts;
            return response()->json($userPosts, 200);
        } catch ( \Exception $e){
            return response()->json(["error" => $e->getMessage()] ,500);
        }

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'post_title' => ['required'],
            'post_description' => ['required'],
            'post_body' => ['required']
        ]);

        if($validator->fails()){
            return response()->json($validator->messages(), 400);
        }

        try {
            $currentUser = auth()->user();
            $post = Post::create($request->all());
            $currentUser->posts()->save($post);
            return response()->json(["post" => $post], 201);
        } catch (\Exception $e) {
            return response()->json(['error'=>$e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Post $post)
    {
        $currentUser = auth()->user();
        if($post["user_id"] === $currentUser["id"]){
            return response()->json($post, 200);
        } else {
            return response()->json(["error"=>"You are not authorized to view this"],403);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Post $post)
    {
        $currentUser = auth()->user();
        if($post["user_id"] === $currentUser["id"]){
            try {
                $currentUser->posts()->update($request->all());
                return response()->json($post, 200);
            } catch (\Exception $e){
                return response()->json(["error"=>"An error occured"],500);
            }

        } else {
            return response()->json(["error"=>"You are not authorized to view this"],403);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Post $post)
    {
        $currentUser = auth()->user();
        if($post["user_id"] === $currentUser["id"]){
            $post->delete();
            return response()->json($post,200);
        } else {
            return response()->json(["error"=>"You are not authorized to view this"], 403);
        }
    }

    public function saveComment(Request $request, Post $post)
    {
        $currentUser = auth()->user();
        if($post["user_id"] === $currentUser["id"]){
            try {
                $comment = Comment::create($request->all());
                $post->comments()->save($comment);
                return response()->json(["comment"=>$comment],200);
            }catch (\Exception $e) {
                return response()->json(['error'=>$e->getMessage()], 500);
            }
        }
    }

    public function viewComments(Post $post) {
        try {
            $comments = $post->comments;
            return response()->json($comments, 200);
        } catch (\Exception $e) {
            return response()->json(["error" => $e->getMessage()], 500);
        }
    }

    public function deleteComment(Post $post, Comment $comment) {
        try {
            if($post["id"] === $comment["post_id"]){
                $comment->delete();
                return response()->json($comment, 200);
            } else {
                return response()->json(["error" => "Comment does not exits"], 404);
            }
        } catch (\Exception $e) {
            return response()->json(["error" => $e->getMessage()], 500);
        }
    }
}

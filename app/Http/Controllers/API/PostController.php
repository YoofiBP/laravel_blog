<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;

use App\Http\Requests\StorePost;
use App\Http\Requests\UpdatePost;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Post;

class PostController extends Controller
{
    //TODO: Create route for searching posts
    //TODO: Implement file upload management
    public function __construct()
    {
        $this->middleware('can:update,post')->only('show', 'update', 'destroy');
        $this->middleware('can:delete,comment')->only('deleteComment');
    }

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
        } catch (\Exception $e) {
            return response()->json(["error" => $e->getMessage()], 500);
        }

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(StorePost $request)
    {
        $data = $request->validated();

        try {
            $currentUser = auth()->user();
            $post = Post::create($data);
            $currentUser->posts()->save($post);
            return response()->json(["post" => $post], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show(Post $post)
    {
        return response()->json($post, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdatePost $request, Post $post)
    {
        $data = $request->validated();
        try {
            $post->update($data);
            return response()->json($post, 200);
        } catch (\Exception $e) {
            return response()->json(["error" => "An error occurred"], 500);
        }


    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Post $post)
    {
        //Gate::authorize('update', $post);
        try {
            $post->delete();
            return response()->json($post, 200);
        } catch (\Exception $e){
            return response()->json(["error" => $e->getMessage()], 500);
        }

    }

    public function saveComment(Request $request, Post $post)
    {
        $currentUser = auth()->user();
        try {
            $comment = Comment::create($request->all());
            $post->comments()->save($comment);
            $currentUser->comments()->save($comment);
            return response()->json(["comment" => $comment], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function viewComments(Post $post)
    {
        try {
            $comments = $post->comments;
            return response()->json($comments, 200);
        } catch (\Exception $e) {
            return response()->json(["error" => $e->getMessage()], 500);
        }
    }

    public function deleteComment(Comment $comment)
    {
        try {
            $comment->delete();
            return response()->json($comment, 200);
        } catch (\Exception $e) {
            return response()->json(["error" => $e->getMessage()], 500);
        }
    }

}

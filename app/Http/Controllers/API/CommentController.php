<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\Request;
use App\Models\Post;

class CommentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Post $post)
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
            $postComments = $post->comments;
            return response()->json(["posts"=>$postComments], 200);
        } else{
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
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}

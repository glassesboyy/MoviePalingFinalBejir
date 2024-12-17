<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Film;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class MovieController extends Controller
{
    public function index()
    {
        $movie = Film::all();
        return response()->json([
            'status' => true,
            'message' => 'List Movies',
            'data' => $movie,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'poster' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'judul' => 'required|string|max:255',
            'deskripsi' => 'required|string',
            'genre' => 'required|array',
            'tanggalRilis' => 'required|date',
            'duration' => 'required|integer|min:1',
            'status' => 'required|in:now playing,upcoming,expired',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ],422);
        }

        $data = $request->all();
        
        if ($request->hasFile('poster')) {
            $file = $request->file('poster');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->storeAs('public/posters', $filename);
            $data['poster'] = $filename;
        }

        $movie = Film::create($data);
        return response()->json([
            'status' => true,
            'message' => 'Movies Created',
            'data' => $movie
        ],201);
    }

    public function show(string $id)
    {
        $movie = Film::findOrFail($id);
        return response()->json([
            'status' => true,
            'message' => 'Data Movie Finded',
            'data' => $movie,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'poster' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'judul' => 'required|string|max:255',
            'deskripsi' => 'required|string',
            'genre' => 'required|array',
            'tanggalRilis' => 'required|date',
            'duration' => 'required|integer|min:1',
            'status' => 'required|in:now playing,upcoming,expired',
        ]);

        if($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Movie Error Updated',
                'errors' => $validator->errors()
            ],422);
        }

        $movie = Film::findOrFail($id);
        $data = $request->all();

        if ($request->hasFile('poster')) {
            // Delete old poster if exists
            if ($movie->poster) {
                Storage::delete('public/posters/' . $movie->poster);
            }
            
            $file = $request->file('poster');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->storeAs('public/posters', $filename);
            $data['poster'] = $filename;
        }

        $movie->update($data);
        return response()->json([
            'status' => true,
            'message' => 'Movie Updated Successfully',
            'data' => $movie
        ],200);
    }

    public function destroy(string $id)
    {
        
        $movie = Film::findOrFail($id);
        $movie->delete();
        return response()->json([
            'status' => 'true',
            'message' => 'Movie Delete Successfully',
        ],204);
    }
}

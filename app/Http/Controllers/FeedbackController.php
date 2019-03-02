<?php

namespace App\Http\Controllers;

use App\Events\FeedbackReceived;
use App\Feedback;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $words = $request->get('words');

        // tidak boleh mengandung tanda koma (,)
        if (strpos($words, ",") !== false) {
            return response()->json([
                "message" => "Kata tidak boleh ada tanda koma (,)",
            ], 400);
        }

        $words = explode(" ", $words);

        // tidak boleh lebih dari 3 kata
        if (count($words) > 3) {
            return response()->json([
                "message" => "Tidak boleh lebih dari 3 kata",
            ], 400);
        }

        foreach ($words as $key => $word) {
            $this->createOrIncrement($word);
        }

        // broadcast with new data
        $data = json_decode($this->getData());
        broadcast(new FeedbackReceived($data));

        return response()->json("OK");
    }

    public function dashboard()
    {
        return view('dashboard');
    }

    public function dashboardData()
    {
        return response()->json($this->getData());
    }

    protected function getData()
    {
        $top_ten =
        Feedback::orderBy('count', 'DESC')
            ->get()
            ->take(10);

        return $top_ten;
    }

    public function input()
    {
        return view('input');
    }

    protected function createOrIncrement(String $word)
    {
        // jadikan lowercase
        $word = strtolower($word);

        $feedback = Feedback::where('word', $word)->first();

        if ($feedback) {
            $feedback->increment('count');
        } else {
            Feedback::create([
                "word" => $word,
                "count" => 1,
            ]);
        }
    }

}

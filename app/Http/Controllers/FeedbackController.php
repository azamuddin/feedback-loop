<?php

namespace App\Http\Controllers;

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

        return response()->json("OK");
    }

    public function dashboard()
    {
        $top_ten = Feedback::orderBy('count', 'DESC')->get()->take(10);
        return response()->json([
            'data' => $top_ten,
        ]);
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

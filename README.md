# realtime-feedback

## Langkah

1. Buat project laravel baru

    ```bash
    laravel new realtime-feedback
    ```

2. Copy `.env-example` ke `.env` (Linux or Mac)

    ```
    cp .env-example .env
    ```

3. Buka phpmyadmin kamu, lalu buat database baru dengan nama `realtime-feedback`

4. Bukan file `.env` yang tadi lalu sesuaikan konfigurasi database.

    ```
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=realtime-feedback
    DB_USERNAME=USERNAME_MYSQLMU
    DB_PASSWORD=supersecretpasswordKAMU
    ```

5. jalankan composer dumpautoload

    ```
    composer dumpautoload
    ```

6. Isi API_KEY

    Buka kembali `.env` lalu ubah API_KEY

    ```
    API_KEY=
    ```

    menjadi


    ```
    API_KEY=somerandomSECRET
    ```

7. Nyalakan server
    ```
    php -S localhost:9000 -t public
    ```

## Langkah aplikasi

-   php artisan make:migration create_feedback_table
-   buka file migration yang baru dibuat di `database/migrations/xxxxxx_create_feedback_table.php`
-   buat model Feedback
-   buat controller FeedbackController

```
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
        // TODO: verify
        // text doesn't contains ","
        // text exploded maximum 3 in length

        $words = explode(" ", $words);

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

```

-   kedua cara di atas bisa dibuat satu-satu atau pake make:model --resource
-   add field 'word', dan 'count' sebagai \$fillable di Model
-   tambahkan routes/web.php kode berikut
    ```
    Route::group(['prefix'=> 'api/v1'], function(){
        Route::post('feedback', 'FeedbackController@store');
    });
    ```
-   disable csrfToken for "api/v1" -> app/Http/Middleware/VerifyCsrfToken.php

## Realtime feature

-   install laravel web socket

    ```
    composer require beyondcode/laravel-websockets
    ```

-   publish migration dari laravel-websockets
    ```
    php artisan vendor:publish --provider="BeyondCode\LaravelWebSockets\WebSocketsServiceProvider" --tag="migrations"
    ```
-   php artisan migrate
-   install Pusher SDK
    ```
    composer require pusher/pusher-php-server "~3.0"
    ```
-   ganti BROADCAST_DRIVER menjadi pusher di `.env`
-   ubah `config/broadcasting.php`
    ```
                'options' => [
                 'cluster' => env('PUSHER_APP_CLUSTER'),
                 'encrypted' => true,
                 'host' => '127.0.0.1',
                 'port' => 6001,
                 'scheme' => 'http',
             ],
    ```
-   ubah config di `.env`

    ```
    PUSHER_APP_ID=realtime-feedback
    PUSHER_APP_KEY=pusherKey
    PUSHER_APP_SECRET=pusherSecret
    PUSHER_APP_CLUSTER=mt1

    MIX_PUSHER_APP_KEY=pusherKey
    MIX_PUSHER_APP_CLUSTER=mt1

    ```

-   ubah bootstrap.js (uncomment)

    ```
    import Echo from "laravel-echo"

    window.Pusher = require('pusher-js');

    window.Echo = new Echo({
        broadcaster: 'pusher',
        key: 'your-pusher-key',
        wsHost: window.location.hostname,
        wsPort: 6001,
        disableStats: true,
    });

    ```

-   websocket serve
    ```
    php artisan websockets:serve
    ```

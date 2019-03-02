# realtime-feedback

## Requirements

-   PHP versi 7.1 ke atas
-   Node + NPM

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

    protected function getData()
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

### Laravel websockets

-   install laravel web socket

    ```
    composer require beyondcode/laravel-websockets
    ```

-   publish migration dari laravel-websockets
    ```
    php artisan vendor:publish --provider="BeyondCode\LaravelWebSockets\WebSocketsServiceProvider" --tag="migrations"
    ```
-   php artisan migrate

### Broadcasting

-   install Pusher SDK
    ```
    composer require pusher/pusher-php-server "~3.0"
    ```
-   ganti BROADCAST_DRIVER menjadi pusher di `.env`
-   ubah `config/broadcasting.php`
    ```
                'options' => [
                 'cluster' => env('PUSHER_APP_CLUSTER'),
                 'encrypted' => false,
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

-   Uncomment broadcasting provider in `app/config.php`
    ```
     App\Providers\BroadcastServiceProvider::class,
    ```
-   buat Event `php artisan make:event FeedbackReceived`
-   Configure `FeedbackReceived`

    ```
        <?php

        namespace App\Events;

        use Illuminate\Broadcasting\Channel;
        use Illuminate\Broadcasting\InteractsWithSockets;
        use Illuminate\Foundation\Events\Dispatchable;
        use Illuminate\Queue\SerializesModels;

        class FeedbackReceived
        {
            use Dispatchable, InteractsWithSockets, SerializesModels;

            /**
            * Create a new event instance.
            *
            * @return void
            */
            public function __construct($data)
            {
                $this->payload = $data;
            }

            public function broadcastWith()
            {
                return $this->payload;
            }

            /**
            * Get the channels the event should broadcast on.
            *
            * @return \Illuminate\Broadcasting\Channel|array
            */
            public function broadcastOn()
            {
                return new Channel('feedback-received');
            }
        }
    ```

-   add broadcast in FeedbackController.store
    ```
        // broadcast with new data
        $data = json_decode($this->getData());
        broadcast(new FeedbackReceived($data));
    ```

### Laravel Mix

-   npm install
-   npm install laravel-echo --save
-   npm install pusher-js --save
-   npm run watch
-   install laravel-echo dan pusher-js

### User Interface

-   Uncomment line di `resources/js/app.js`

    ```

    const files = require.context("./", true, /\.vue$/i);
    files.keys().map(key =>
        Vue.component(
            key
                .split("/")
                .pop()
                .split(".")[0],
            files(key).default
        )
    );
    ```

#### Input feedback

-   Buat routes untuk input feedback

    ```
    Route::get('feedback/input', 'FeedbackController@input');
    ```

-   Buat input method di `FeedbackController`
    ```
    public function input(){
        return view('input');
    }
    ```
-   Buat view input di `resources/views/input.blade.php`

    ```
    <html>
         <head>
             <title>Feedback Loop</title>
             <link rel="stylesheet" href="/css/app.css"/>
         </head>
         <body>
         <div id="app">

         </div>
         <script src="/js/app.js"></script>
         </body>
     </html>
    ```

-   Buat vue component feedback-input di `resources/js/components/FeedbackInput.Vue`

-   Gunakan component feedback-input di view `input.blade.php`
    ```
     <html>
         <head>
             <title>Feedback Loop</title>
         </head>
         <body>
         <div id="app">
             <feedback-input></feedback-input>
         </div>
         <script src="/js/app.js"></script>
         </body>
     </html>
    ```

#### Dashboard

-   Buat routes untuk dashboard
    ```
    Route::get('feedback/dashboard', 'FeedbackController@dashboard');
    ```
-   buat method dashboard di FeedbackController
    ```
    public function dashboard()
    {
        return view('dashboard');
    }
    ```
-   buat view di resources/views/dashboard.blade.php

    ```
    <html>
        <head>
            <title>Feedback Loop</title>
            <link rel="stylesheet" href="/css/app.css"/>
        </head>
        <body>
        <div id="app">
            <feedback-dashboard></feedback-dashboard>
        </div>
        <script src="/js/app.js"></script>
        </body>
    </html>
    ```

-   buat method di controller feedback

    ```
    public function dashboardData()
    {
        return response()->json($this->getData());
    }
    ```

-   buat routes untuk mengambil data via API
    ```
    Route::get('feedback/data', 'FeedbackController@dashboardData');
    ```
-   buat feedback-dashboard vue component di `resources/js/components/FeedbackDashboard.vue`

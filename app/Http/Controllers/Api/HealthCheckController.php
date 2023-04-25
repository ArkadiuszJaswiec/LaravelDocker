<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Tests\TestCase;
use MongoDB\Client;
class HealthCheckController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
// POSTGRES
        try {
            DB::connection('pgsql')->getPdo();
            $komunikat_postgres= "ok";
        } catch (\Exception $e) {
            $komunikat_postgres="failed";
        }
//REDIS
        $komunikat_redis = 'to check';
        try {
            // Pobierz wartość z Redis pod kluczem "test"
            $value = Redis::get('test');

            // Sprawdź, czy wartość jest poprawna
            if ($value === null) {
                // Jeśli wartość jest pusta, to klucz nie istnieje, więc zapisz jakąś wartość
                Redis::set('test', '1');
                $komunikat_redis = 'ok';
            } else {
                $komunikat_redis = 'ok';
            }
        } catch (\Exception $e) {
            // W przypadku wystąpienia błędu, ustawiamy wartość 'nok'
            $komunikat_redis = 'failed';
        }
//Rabbit
        $komunikat_rabbit = 'to check';
        $connection = new AMQPStreamConnection(
            'localhost', // adres serwera RabbitMQ
            5672, // numer portu
            'root', // użytkownik
            'root' // hasło
        );
        if ($connection->isConnected()) {
            $komunikat_rabbit = 'ok';
        } else {
            $komunikat_rabbit = 'failed';
        }
//MongoDB
        $komunikat_mongo = "to check";
        try {
            // Sprawdzenie połączenia z bazą danych MongoDB
            DB::connection('mongodb')->getMongoClient();
            // Połączenie z bazą danych MongoDB działa
            $komunikat_mongo= 'ok';
        } catch (\Exception $e) {
            // Połączenie z bazą danych MongoDB nie działa
            $komunikat_mongo= 'failed';
        }

        return response()->json(['mongo' => $komunikat_mongo,'redis'=>$komunikat_redis,'postgres'=>$komunikat_postgres,'rabbit'=>$komunikat_rabbit]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
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

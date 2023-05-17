<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Carbon\Carbon;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['register', 'confirmRegistration']]);
    }
    public function register(Request $request){

        $data = $request->json()->all();

//SPRAWDZENIE CZY UŻYTKOWNIK ISTNIEJE
        $isEmail=DB::table('users')
            ->where('email', '=', $data['email'])
            ->count();

        if($isEmail>0)
        {
            return response()->json(['message' => 'Użytkownik o podanym adresie e-mail już istnieje'], 400);
        }
            $user = User::create([
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'firstName' => $data['firstName'],
                'lastName' => $data['lastName'],
                'country' => $data['country'],
                'city' => $data['city'],
                'street' => $data['street'],
                'zipCode' => $data['zipCode'],
            ]);
        $id_usr =$user->id;

        // Generowanie tokena JWT
        $token = JWTAuth::fromUser($user);

        $now = Carbon::now()->tz('Europe/Warsaw');
        $codeLife = $now->copy()->addMinutes(10)->format('Y-m-d H:i:s');

        $update_usr = User::findOrFail($id_usr);
        $update_usr->activationCode = $token;
        $update_usr->codeLife=$codeLife;
        $update_usr->save();

        return response()->json(['message' => 'Użytkownik został zarejestrowany', 'Token do potwierdzenia'=>$token], 201);
    }
    public function confirmRegistration(Request $request)
    {

        $data = $request->json()->all();

        // Wyszukanie użytkownika po kodzie aktywacyjnym
        $user = User::where('activationCode', $data['activationCode'])->first();


        $now = Carbon::now()->tz('Europe/Warsaw');

        $terazDateTime = new DateTime($now->format('Y-m-d H:i:s'));
        $czasZyciaDateTime = new DateTime($user->codeLife);


        // Sprawdzenie, czy kod aktywacyjny istnieje
        if (!$user) {
            return response()->json(['message' => 'Nieprawidłowy kod aktywacyjny'], 404);
        }


        // Sprawdzenie, czy kod aktywacyjny wygasł
        if ($terazDateTime > $czasZyciaDateTime) {
            return response()->json(['message' => 'Kod aktywacyjny wygasł'], 404);
        }


        // Kod aktywacyjny jest poprawny i ważny
        $update_usr = User::findOrFail($user->id);
        $update_usr->status=1;
        $update_usr->activationCode="";
        $update_usr->save();

        // Zwrócenie odpowiedzi sukcesu
        return response()->json(['message' => 'Aktywowano użytkownika.'], 200);

    }
}

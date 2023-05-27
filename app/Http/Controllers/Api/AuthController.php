<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use Carbon\Carbon;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;


class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['register', 'confirmRegistration', 'login', 'refresh']]);
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

        // koduaktywacyjny
        $code = Str::random(6, 'alnum');

        $now = Carbon::now()->tz('Europe/Warsaw');
        $codeLife = $now->copy()->addMinutes(10)->format('Y-m-d H:i:s');

        $update_usr = User::findOrFail($id_usr);
        $update_usr->activationCode = $code;
        $update_usr->codeLife=$codeLife;
        $update_usr->save();

        return response()->json(['message' => 'Użytkownik został zarejestrowany', 'Kod potwierdzający'=>$code], 201);
    }
    public function confirmRegistration(Request $request)
    {

        $data = $request->json()->all();

        $isUser=DB::table('users')
            ->where('activationCode', '=', $data['activationCode'])
            ->count();

        if($isUser==0)
        {
            return response()->json(['message' => 'Nieprawidłowy kod aktywacyjny'], 404);
        }

        // Wyszukanie użytkownika po kodzie aktywacyjnym
        $user = User::where('activationCode', $data['activationCode'])->first();


        $now = Carbon::now()->tz('Europe/Warsaw');

        $terazDateTime = new DateTime($now->format('Y-m-d H:i:s'));
        $czasZyciaDateTime = new DateTime($user->codeLife);

        // Sprawdzenie, czy kod aktywacyjny wygasł
        if ($terazDateTime > $czasZyciaDateTime) {
            return response()->json(['message' => 'Kod aktywacyjny wygasł'], 404);
        }


        // Kod aktywacyjny jest poprawny i ważny
        $update_usr = User::findOrFail($user->id);
        $update_usr->status=1;
        $update_usr->activationCode=null;
        $update_usr->codeLife=null;
        $update_usr->save();

        // Zwrócenie odpowiedzi sukcesu
        return response()->json(['message' => 'Aktywowano użytkownika.'], 200);

    }

    public function login(Request $request)
    {
        $data = $request->json()->all();

        $email = $data['email'];
        $password = $data['password'];
        // Sprawdź, czy istnieje użytkownik o podanym adresie e-mail
        $user = User::where('email', $email)->first();

        if (!$user) {
            return response()->json(['error' => 'Nieprawidłowy adres e-mail'], 401);
        }
        // Sprawdź poprawność hasła
        if (!Hash::check($password, $user->password)) {
            return response()->json(['error' => 'Nieprawidłowe hasło'], 401);
        }
        // Sprawdź, czy konto jest aktywne
        if ($user->status != 1) {
            return response()->json(['error' => 'Konto nie jest aktywne'], 401);
        }
        $key = 'example_key';
        $payload = [
            'iat' =>  Carbon::now()->timestamp,
            'exp'=>Carbon::now()->addMinutes(5)->timestamp,
            'sub'=> $email
            ];
        $payload_refresh = [
            'iat' =>  Carbon::now()->timestamp,
            'exp'=>Carbon::now()->addMinutes(60)->timestamp,
            'sub'=> $email
        ];
        $token = JWT::encode($payload, $key, 'HS256');
        $token_refresh = JWT::encode($payload_refresh, $key, 'HS256');
        return response()->json([
            'access_token' => $token,
            'refresh_token' => $token_refresh,
            'token_type' => 'bearer',
        ]);
    }

    public function refresh(Request $request)
    {
        $data = $request->json()->all();
        $key = 'example_key';

        $refresh_token = $data['refresh_token'];
        $decode=JWT::decode($refresh_token, new Key($key, 'HS256'));
        $decoded_array = json_decode(json_encode($decode),true);


        $now = Carbon::now()->timestamp;

        $exp=$decoded_array['exp'];
        $email=$decoded_array['sub'];

        if($exp < $now) {
            return response()->json(['error' => 'Unauthorized: Błędny lub przedawniony token'], 401);
        }

        $payload = [
            'iat' =>  Carbon::now()->timestamp,
            'exp'=>Carbon::now()->addMinutes(5)->timestamp,
            'sub'=> $email
        ];

        $payload_refresh = [
            'iat' =>  Carbon::now()->timestamp,
            'exp'=>Carbon::now()->addMinutes(60)->timestamp,
            'sub'=> $email
        ];

        $token = JWT::encode($payload, $key, 'HS256');
        $token_refresh = JWT::encode($payload_refresh, $key, 'HS256');

        return response()->json([
            'access_token' => $token,
            'refresh_token' => $token_refresh,
        ]);
    }


}

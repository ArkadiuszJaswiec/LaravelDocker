<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeamController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['index', 'show', 'store', 'update', 'destroy']]);
    }
    private function getJwtTokenFromHeaders()
    {
        $headers = getallheaders();
        try{
            if (isset($headers['Authorization'])) {
                $authorizationHeader = $headers['Authorization'];
                $jwtToken = str_replace('Bearer ', '', $authorizationHeader);
                return $jwtToken;
            } else {
                return response()->json(['error' => 'Błąd pobrania tokena'], 401);
            }
        }
        catch (\Exception $e) {
            return response()->json(['error' => 'Unauthorized: Błędny lub przedawniony token'], 401);

        }
    }

    private function getUserIdFromToken($jwtToken)
    {
        try {
            $key = 'example_key';
            $decoded = JWT::decode($jwtToken, new Key($key, 'HS256'));
            $decoded_array = json_decode(json_encode($decoded), true);

            $now = Carbon::now()->timestamp;

            $exp = $decoded_array['exp'];
            $email = $decoded_array['sub'];

            if ($exp < $now) {
                return response()->json(['error' => 'Token stracił ważność'], 401);
            }

            $user = User::where('email', $email)->first();

            if (!$user) {
                return response()->json(['error' => 'Nieprawidłowy adres e-mail'], 401);
            }

            $usr_id = $user->id;
            return $usr_id;
        } catch (ExpiredException $e) {
            return response()->json(['error' => 'Token stracił ważność'], 401);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unauthorized: Błędny token'], 401);
        }
    }
    public function index($id_project)
    {
        $jwtToken = $this->getJwtTokenFromHeaders();
        $usr_id = $this->getUserIdFromToken($jwtToken);
        if(is_int($usr_id)) {

            $isOwner =  DB::table('projects')
                ->where('id', '=', $id_project)
                ->where('owner', '=', $usr_id)
                ->count();

            $isTeam =  DB::table('teams')
                ->where('project_id', '=', $id_project)
                ->where('user_id', '=', $usr_id)
                ->count();

            if($isOwner > 0 OR $isTeam > 0)
            {
                $isTask = DB::table('teams')
                    ->where('project_id', '=', $id_project)
                    ->count();

                if ($isTask > 0) {
                    $project_team = DB::table('teams')
                        ->where('project_id', '=', $id_project)
                        ->select('teams.*')
                        ->get();
                    return response()->json(["Członkowie zespołu projektu o id: ".$id_project => $project_team]);

                } else {
                    return response()->json(['massage' => "Projekt o id: " . $id_project . " nie ma przypisanych członków zespołu."]);
                }
            }
            else
                return response()->json(['error' => "Brak uprawnień"]);
        }
        else
            return $usr_id;
    }

    public function store(Request $request, $id_project)
    {
        $data = $request->json()->all();

        $jwtToken = $this->getJwtTokenFromHeaders();
        $usr_id = $this->getUserIdFromToken($jwtToken);
        if(is_int($usr_id)) {

            $isUser = DB::table('projects')
                ->where('id', '=', $data['user_id'])
                ->count();
            if($isUser > 0) {

                $isProject = DB::table('projects')
                    ->where('id', '=', $id_project)
                    ->count();
                if ($isProject == 0)
                    return response()->json(['error' => 'Projekt nie został znaleziony'], 404);
                else {
                    $project_owner_count = DB::table('projects')
                        ->where('projects.owner', '=', $usr_id)
                        ->where('projects.id', '=', $id_project)
                        ->count();
                    if ($project_owner_count > 0) {
                        $isInTeam = DB::table('teams')
                            ->where('project_id', '=', $id_project)
                            ->where('user_id', '=', $data['user_id'])
                            ->count();

                        if ($isInTeam > 0)
                            return response()->json(['error' => "Użytkownik jest już członkiem tego projektu"]);
                        else {
                            $team = new Team();
                            $team->user_id = $data['user_id'];
                            $team->project_id = $id_project;
                            $team->save();

                            return response()->json(['message' => "Dodano użytkownika o id: " . $data['user_id'] . " do projektu o id: " . $id_project]);
                        }
                    } else
                        return response()->json(['error' => "Brak uprawnień do dodania użytkowników do projektu o id: " . $id_project]);
                }
            }
            else
                return response()->json(['error' => "Brak takiego użytkownika"]);

        }
        else
            return $usr_id;
    }

    public function show($id)
    {
        //
    }

    public function update(Request $request, $id)
    {
        //
    }

    public function destroy(Request $request, $id_project)
    {
        $data = $request->json()->all();

        $jwtToken = $this->getJwtTokenFromHeaders();
        $usr_id = $this->getUserIdFromToken($jwtToken);

        if(is_int($usr_id)) {
            $isProject = DB::table('projects')
                ->where('id', '=', $id_project)
                ->count();
            if($isProject == 0)
                return response()->json(['error' => 'Projekt nie został znaleziony'], 404);
            else {

                $project_owner_count = DB::table('projects')
                    ->where('projects.owner', '=', $usr_id)
                    ->where('projects.id', '=', $id_project)
                    ->count();
                if ($project_owner_count > 0) {

                    $delete = DB::table("teams")
                        ->where('project_id', '=', $id_project)
                        ->where('user_id', '=', $data['user_id'])
                        ->delete();

                    return response()->json(['massage' => "Usunięto użytkownika o id: " . $data['user_id'] . " z zespołu projektu o id: " . $id_project]);

                } else
                    return response()->json(['error' => "Brak uprawnień do usunięcia użytkownika z teamu projektu o id: " . $id_project]);
            }
        }
        else
            return $usr_id;
    }
}

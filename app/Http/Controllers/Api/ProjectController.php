<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
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
            return response()->json(['error' => 'Unauthorized: Błędny token'], 401);
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
            return response()->json(['error' => 'Unauthorized: Błędny lub przedawniony token'], 401);
        }
    }

    public function index(Request $request)
    {
        $jwtToken = $this->getJwtTokenFromHeaders();
        $usr_id = $this->getUserIdFromToken($jwtToken);
         if(is_int($usr_id))
         {
            $project_owner_count= DB::table('projects')
                ->where('projects.owner', '=', $usr_id)
                ->count();
            $project_team_count= DB::table('teams')
                ->Where('teams.user_id', '=', $usr_id)
                ->count();
            if($project_owner_count > 0) {
                $project= DB::table('projects')
                    ->where('projects.owner', '=', $usr_id)
                    ->select('projects.*')
                    ->orderBy('id')
                    ->get();
            }
            else
            {
                $project = "Użytkownik o id: ".$usr_id." nie jest właścicielem żadnego projektu.";
            }
            if($project_team_count >0)
            {
                $project_team= DB::table('projects')
                    ->join('teams', 'projects.id','=', 'teams.project_id' )
                    ->Where('teams.user_id', '=', $usr_id)
                    ->select('projects.*')
                    ->orderBy('project.id')
                    ->get();
            }
            else
            {
                $project_team = "Użytkownik o id: ".$usr_id." nie jest w zespole żadnego projektu.";
            }
            return response()->json([
                'leader projektu' => $project,
                'team' => $project_team,
            ]);
        }
        else
          return  $usr_id;
    }

    public function store(Request $request)
    {
        $data = $request->json()->all();
        $jwtToken = $this->getJwtTokenFromHeaders();
        $usr_id = $this->getUserIdFromToken($jwtToken);

        if(is_int($usr_id)) {
            $project = new Project();
            $project->name = $data['name'];
            $project->description = $data['description'];
//            $project->owner = (int)$usr_id;
            $project->owner = $usr_id;
            $project->deadline = $data['deadline'];
            $project->save();

            $project_id = $project->id;
            return response()->json($project_id, 201);
        }
        else
            return  $usr_id;
    }

    public function show($id)
    {
        $project= DB::table('projects')
            ->where('id', '=', $id)
            ->select('projects.*')
            ->first();

        if ($project) {
            return response()->json($project, 200);
        } else {
            return response()->json(['error' => 'Projekt nie został znaleziony'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $data = $request->json()->all();

        $jwtToken = $this->getJwtTokenFromHeaders();
        $usr_id = $this->getUserIdFromToken($jwtToken);
        if(is_int($usr_id)) {
            $isOwner = DB::table('projects')
                ->where('id', '=', $id)
                ->where('owner', '=', $usr_id)
                ->first();
            if ($isOwner) {
                $update_project = Project::findOrFail($id);
                $update_project->name = $data['name'];
                $update_project->description = $data['description'];
                $update_project->finishDate = $data['finishDate'];
                $update_project->deadline = $data['deadline'];
                $update_project->save();
                return response()->json(['message' => 'Projekt został zaktualizowany']);
            } else
                return response()->json(['error' => 'Brak uprawnien lub brak takiego projektu']);
        }
        else
            return $usr_id;
    }

    public function destroy($id)
    {
        $jwtToken = $this->getJwtTokenFromHeaders();
        $usr_id = $this->getUserIdFromToken($jwtToken);

        if(is_int($usr_id)) {
            $isOwner = DB::table('projects')
                ->where('id', '=', $id)
                ->where('owner', '=', $usr_id)
                ->first();

            if ($isOwner) {
                $delete = DB::table("projects")
                    ->where('id', '=', $id)
                    ->delete();
                return response()->json(['massage' => 'Usunięto projekt']);
            } else
                return response()->json(['error' => 'Brak uprawnien lub brak takiego projektu']);
        }
        else
            return $usr_id;
    }
}

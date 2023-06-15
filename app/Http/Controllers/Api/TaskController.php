<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaskController extends Controller
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

    public function index(Request $request, $id_project)
    {
        $jwtToken = $this->getJwtTokenFromHeaders();
        $usr_id = $this->getUserIdFromToken($jwtToken);
        if(is_int($usr_id)) {
            $isProject = DB::table('projects')
                ->where('id', '=', $id_project)
                ->count();
            if($isProject == 0)
                return response()->json(['error' => 'Projekt nie został znaleziony'], 404);
            else {

                $isTask = DB::table('tasks')
                    ->where('project_id', '=', $id_project)
                    ->count();

                if ($isTask > 0) {
                    $project_tasks = DB::table('tasks')
                        ->where('project_id', '=', $id_project)
                        ->select('tasks.*')
                        ->get();
                    return response()->json($project_tasks);
                } else {
                    return response()->json(['massage' => "Projekt o id: " . $id_project . " nie ma przypisanych zadań."]);
                }
            }
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
            $isOwner =  DB::table('projects')
                ->where('id', '=', $id_project)
                ->where('owner', '=', $usr_id)
                ->count();
            $isTeam =  DB::table('teams')
                ->where('project_id', '=', $id_project)
                ->where('user_id', '=', $usr_id)
                ->count();
            if($isOwner > 0 OR $isTeam > 0) {
                $isProject = DB::table('projects')
                    ->where('id', '=', $id_project)
                    ->count();
                if ($isProject == 0)
                    return response()->json(['error' => 'Projekt nie został znaleziony'], 404);
                else {
                    $task = new Task();
                    $task->name = $data['name'];
                    $task->description = $data['description'];
                    $task->priority = $data['priority'];
                    $task->deadline = $data['deadline'];
                    $task->project_id = $id_project;
                    $task->save();
                    $task_id = $task->id;
                    return response()->json($task_id, 201);
                }
            }
            else
            return response()->json(['error' => 'Brak uprawnień'], 404);
        }
        else
            return $usr_id;
    }


    public function show($id)
    {
        $jwtToken = $this->getJwtTokenFromHeaders();
        $usr_id = $this->getUserIdFromToken($jwtToken);
        if(is_int($usr_id)) {

            $isTask = DB::table('tasks')
                ->where('id', '=', $id)
                ->count();

            if ($isTask > 0) {
                $task = DB::table('tasks')
                    ->where('id', '=', $id)
                    ->select('tasks.*')
                    ->first();
                return response()->json($task, 200);
            } else {
                return response()->json(['error' => "Brak zadania o id: " . $id], 404);
            }
        }
        else
            return $usr_id;
    }

    public function update(Request $request, $id)
    {
        $data = $request->json()->all();

        $jwtToken = $this->getJwtTokenFromHeaders();
        $usr_id = $this->getUserIdFromToken($jwtToken);
        if(is_int($usr_id)) {

            $isTask = DB::table('tasks')
                ->where('id', '=', $id)
                ->count();
            if ($isTask > 0) {
                $updateTask = Task::findOrfail($id);
                $updateTask->name = $data['name'];
                $updateTask->description = $data['description'];
                $updateTask->priority = $data['priority'];
                $updateTask->deadline = $data['deadline'];
                $updateTask->save();

                return response()->json(['massage' => "Zadanie o id: " . $id . " zostało zaktualizowane."]);
            } else
                return response()->json(['error' => "Brak zadania o id: " . $id]);
        }
        else
            return $usr_id;

    }

    public function destroy(Request $request, $id)
    {
        $data = $request->json()->all();

        $jwtToken = $this->getJwtTokenFromHeaders();
        $usr_id = $this->getUserIdFromToken($jwtToken);
        if(is_int($usr_id)) {
            $id_project = DB::table('tasks')
                ->where('id', '=', $id)
                ->first()->project_id;
            $isOwner =  DB::table('projects')
                ->where('id', '=', $id_project)
                ->where('owner', '=', $usr_id)
                ->count();

            $isTeam =  DB::table('teams')
                ->where('project_id', '=', $id_project)
                ->where('user_id', '=', $usr_id)
                ->count();

            if($isOwner > 0 OR $isTeam > 0) {

                $isTask = DB::table('tasks')
                    ->where('id', '=', $id)
                    ->count();
                if ($isTask > 0) {
                    $delete = DB::table("tasks")
                        ->where('id', '=', $id)
                        ->delete();
                    return response()->json(['massage' => 'Usunięto zadanie']);
                } else
                    return response()->json(['error' => "Brak zadania o id: " . $id]);
            }
            else
                return response()->json(['error' => "Brak uprawnień"]);

        }
        else
            return $usr_id;
    }
}

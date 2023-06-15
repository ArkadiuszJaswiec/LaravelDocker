<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Task;
use DateTime;
use Firebase\JWT\ExpiredException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use Carbon\Carbon;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class CommentsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['index', 'show', 'store', 'update', 'destroy']]);
    }

    private function getJwtTokenFromHeaders()
    {
        $headers = getallheaders();
        try {
            if (isset($headers['Authorization'])) {
                $authorizationHeader = $headers['Authorization'];
                $jwtToken = str_replace('Bearer ', '', $authorizationHeader);
                return $jwtToken;
            } else {
                return response()->json(['error' => 'Błąd pobrania tokena'], 401);
            }
        } catch (\Exception $e) {
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

    public function index($id_task)
    {
        $jwtToken = $this->getJwtTokenFromHeaders();
        $usr_id = $this->getUserIdFromToken($jwtToken);

        if (is_int($usr_id)) {
            $id_project = DB::table('tasks')
                ->where('id', '=', $id_task)
                ->first()->project_id;

            $isOwner = DB::table('projects')
                ->where('id', '=', $id_project)
                ->where('owner', '=', $usr_id)
                ->count();

            $isTeam = DB::table('teams')
                ->where('project_id', '=', $id_project)
                ->where('user_id', '=', $usr_id)
                ->count();

            if ($isOwner > 0 or $isTeam > 0) {
                $isComment = DB::table('comments')
                    ->where('task_id', '=', $id_task)
                    ->count();

                if ($isComment > 0) {
                    $task_comments = DB::table('comments')
                        ->join('users', 'comments.user_id', '=', 'users.id')
                        ->where('comments.task_id', '=', $id_task)
                        ->select('users.firstName', 'users.lastName', 'comments.comment', 'comments.created_at')
                        ->orderBy('comments.created_at')
                        ->get();
                    return response()->json(["Komentarze do zadania o id: " . $id_task => $task_comments]);

                } else {
                    return response()->json(['massage' => "Zadanie o id: " . $id_task . " nie ma przypisanych komentarzy."]);
                }
            } else
                return response()->json(['error' => "Brak uprawnień"]);
        } else
            return $usr_id;
    }


    public function store(Request $request, $id_task)
    {
        $data = $request->json()->all();

        $jwtToken = $this->getJwtTokenFromHeaders();
        $usr_id = $this->getUserIdFromToken($jwtToken);
        if (is_int($usr_id)) {

            $id_project = DB::table('tasks')
                ->where('id', '=', $id_task)
                ->first()->project_id;

            $isOwner = DB::table('projects')
                ->where('id', '=', $id_project)
                ->where('owner', '=', $usr_id)
                ->count();

            $isTeam = DB::table('teams')
                ->where('project_id', '=', $id_project)
                ->where('user_id', '=', $usr_id)
                ->count();

            if ($isOwner > 0 or $isTeam > 0) {

                $isTask = DB::table('tasks')
                    ->where('id', '=', $id_task)
                    ->count();
                if ($isTask == 0)
                    return response()->json(['error' => "Task o id: " . $id_task . "nie został znaleziony"], 404);
                else {
                    $comment = new Comment();
                    $comment->user_id = $usr_id;
                    $comment->comment = $data['comment'];
                    $comment->task_id = $id_task;
                    $comment->save();

                    $comment = $comment->id;
                    return response()->json(['massage' => "Dodano komentarz o id: " . $comment . "."]);

                }
            } else
                return response()->json(['error' => 'Brak uprawnień'], 404);
        } else
            return $usr_id;
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    public function update(Request $request, $id)
    {
        //
    }

    public function destroy(Request $request, $id)
    {

        $jwtToken = $this->getJwtTokenFromHeaders();
        $usr_id = $this->getUserIdFromToken($jwtToken);

        if (is_int($usr_id)) {

            $id_project = DB::table('comments')
                ->join('tasks', 'comments.task_id', '=', 'task.id')
                ->join('project', 'tasks.project_id', '=', 'project.id')
                ->where('comments.id', '=', $id)
                ->first()->project_id;

            $isOwner = DB::table('projects')
                ->where('id', '=', $id_project)
                ->where('owner', '=', $usr_id)
                ->count();

            $isTeam = DB::table('teams')
                ->where('project_id', '=', $id_project)
                ->where('user_id', '=', $usr_id)
                ->count();

            if ($isOwner > 0 or $isTeam > 0)
            {
                $isComment = DB::table('comment')
                    ->where('id', '=', $id)
                    ->count();
                if ($isComment == 0)
                    return response()->json(['error' => "Brak komentarza o id: " . $id], 404);
                else
                {
                    $project_owner_count = DB::table('projects')
                        ->where('projects.owner', '=', $usr_id)
                        ->where('projects.id', '=', $id_project)
                        ->count();
                    if ($project_owner_count > 0)
                    {
                        $delete = DB::table("comments")
                            ->where('id', '=', $id)
                            ->delete();
                        return response()->json(['massage' => "Usunięto komentarz o id: " . $id]);
                    }
                    else
                        return response()->json(['error' => "Brak uprawnień do usunięcia komentarza o id: " . $id]);
                }
            }
            else
                return $usr_id;
        }
    }
}


<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (auth()->user()->is_admin) {
            $tasks = Task::all();
        }
        else
        {
            $tasks = Task::where('user_id', auth()->user()->id)->get();
        }
        if ($tasks->isEmpty()) {
            return response()->json(['message' => 'No tasks found.'], 404);
        }
        return response()->json(TaskResource::collection($tasks));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTaskRequest $request)
    {
        $validated = $request->validated();
        $validated['user_id'] = auth()->user()->id;
        $task = Task::create($validated);
        return response()->json(new TaskResource($task));
    }

    /**
     * Display the specified resource.
     */
    public function show(Task $task)
    {
        if (auth()->user()->cannot('view', $task)) {
            return response()->json(['message' => 'You are not authorized to view this task.'], 403);
        }
        return response()->json(new TaskResource($task));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTaskRequest $request, Task $task)
    {
        if (auth()->user()->cannot('update', $task)) {
            return response()->json(['message' => 'You are not authorized to update this task.'], 403);
        }
        $task->update($request->validated());
        return response()->json(new TaskResource($task));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Task $task)
    {
        if (auth()->user()->cannot('delete', $task)) {
            return response()->json(['message' => 'You are not authorized to delete this task.'], 403);
        }

        $task->delete();
        return response()->noContent();
    }
}

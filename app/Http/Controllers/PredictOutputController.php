<?php

namespace App\Http\Controllers;

use App\Http\Requests\PredictOutputRequest;
use App\Services\PythonVideoService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class PredictOutputController extends Controller
{
    /**
     * Run the question's code in Python and return what it prints.
     */
    public function __invoke(PredictOutputRequest $request, PythonVideoService $python): JsonResponse
    {
        try {
            return response()->json($python->predictOutput($request->validated('code'), $request->validated('language') ?? 'python'));
        } catch (ConnectionException) {
            return response()->json(['message' => 'The Python service is not running.'], 503);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 502);
        }
    }
}

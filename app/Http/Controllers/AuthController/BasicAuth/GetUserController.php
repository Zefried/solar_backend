<?php

namespace App\Http\Controllers\AuthController\BasicAuth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class GetUserController extends Controller
{
    /**
     * Fetch employees with pagination.
     * Allows changing roles for different users if needed. ******
     *
     * Pagination parameters via query string:
     *  - page: current page number (default: 1)
     *  - perPage: items per page (default: 5)
     *
     * Example URLs:
     *  /fetch/employee?page=1&perPage=5
     *  /fetch/employee?page=2&perPage=5
     *  /fetch/employee?page=3&perPage=5
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    // employee role
    public function fetchEmployee(Request $request)
    {
        // Get current page and per-page limit from query, with defaults
        $page = (int) $request->query('page', 1);
        $perPage = (int) $request->query('perPage', 5);

        // Calculate how many records to skip for the current page
        $skip = ($page - 1) * $perPage;

  
        $query = User::where('role', 'employee');

        $totalEmployees = $query->count();

        $employees = $query->skip($skip)
            ->take($perPage)
            ->get();

        if ($employees->isEmpty()) {
            return response()->json([
                'status' => 404,
                'message' => 'No employees found'
            ]); 
        }

        return response()->json([
            'status' => 200,
            'data' => [
                'employees' => $employees,
                'totalPages' => ceil($totalEmployees / $perPage)
            ]
        ]);
    }

    public function searchEmployee(Request $request)
    {
        try {
            $query = $request->input('q'); // search term from frontend
            $limit = (int) $request->query('limit', 50); // configurable limit with default 50

            if (!$query) {
                return response()->json([
                    'status'  => 200,
                    'message' => 'No query provided',
                    'data'    => []
                ]);
            }

            $searchData = User::where('role', 'employee')
                ->where(function($q) use ($query) {
                    $q->where('phone', 'LIKE', '%'.$query.'%')
                    ->orWhere('email', 'LIKE', '%'.$query.'%');
                })
                ->limit($limit)
                ->get();

            return response()->json([
                'status'  => 200,
                'message' => 'Employees fetched successfully',
                'data'    => $searchData
            ]);
            
        } catch (\Exception $e) {
            
            return response()->json([
                'status' => 500,
                'message' => 'Something went wrong in server',
                'error' => 'Internal Server Error on searching employees method'
            ]);
        }
    }









}

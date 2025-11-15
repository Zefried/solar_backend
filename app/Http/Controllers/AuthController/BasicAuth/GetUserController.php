<?php

namespace App\Http\Controllers\AuthController\BasicAuth;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\User;
use App\Models\UserKyc\UserBankInfo;
use App\Models\UserKyc\UserDocuments;
use App\Models\UserKyc\UserExtraInfo;
use App\Models\UserKyc\UserPersonalInfo;
use Exception;
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

    // employee can search based on status for admin it returns all users
    public function searchUsers(Request $request)
    {
        try {
            $role   = 'user';
            $name   = $request->query('name');
            $status = $request->input('status', 'all');

            $query = User::where('role', $role);

            // Employee-specific restriction
            if ($request->user()->role === 'employee') {
                $query->where('employee_id', $request->user()->id);

                if ($status !== 'all') {
                    $query->whereHas('kycTrack', function ($q) use ($status) {
                        $q->where('user_kyc_status', $status);
                    });
                }
            }
            // Block all other roles except admin
            elseif ($request->user()->role !== 'admin') {
                return response()->json([
                    'status'  => 403,
                    'message' => 'Unauthorized role access',
                ]);
            }

            // Search by name / phone
            if ($name) {
                $query->where(function ($q) use ($name) {
                    $q->where('name', 'like', "%{$name}%")
                    ->orWhere('phone', 'like', "%{$name}%");
                });
            }

            // Attach employee relation
            $query->with('employee');

            // Build response data with employee_name
            $users = $query->get()->map(function ($u) {
                return array_merge($u->toArray(), [
                    'employee_name' => $u->employee->name ?? null,
                ]);
            });

            if ($users->isEmpty()) {
                return response()->json([
                    'status'  => 404,
                    'message' => 'No users found',
                ]);
            }

            return response()->json([
                'status'        => 200,
                'filter_status' => $request->input('status', 'all'),
                'data'          => $users,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'message' => 'Server error - searchUsers',
                'error'   => $e->getMessage(),
            ]);
        }
    }


    public function searchEmployeeInRegister(Request $request)
    {
        try {
            $role = 'employee';
            $name = $request->query('name');

            $query = User::where('role', $role);
            if ($name) {
                $query->where(function ($q) use ($name) {
                    $q->where('name', 'like', '%' . $name . '%')
                    ->orWhere('phone', 'like', '%' . $name . '%');
                });
            }

            $employees = $query->get();

            if ($employees->isEmpty()) {
                return response()->json(['status' => 404, 'message' => 'No employees found']);
            }

            return response()->json(['status' => 200, 'data' => $employees]);
        } catch (\Exception $e) {
            return response()->json(['status' => 500, 'message' => 'Server error - searchEmployee']);
        }
    }

    
    public function getUsersList(Request $request)
    {
        try {
            $user = $request->user();
            $status = $request->input('status', 'all'); // get status from POST

            $query = User::where('role', 'user')
                ->with(['employee' => function ($q) {
                    $q->select('id', 'name');
                }]);

            // ✅ Role handling (keep existing logic)
            if ($user->role === 'employee') {
                $query->where('employee_id', $user->id);
            } elseif ($user->role !== 'admin') {
                return response()->json([
                    'status' => 403,
                    'message' => 'Unauthorized role access'
                ]);
            }

            // ✅ Optional status filtering (for employee or admin)
            if ($status !== 'all') {
                $query->whereHas('kycTrack', function ($q) use ($status) {
                    $q->where('user_kyc_status', $status);
                });
            }

            $users = $query->get();

            if ($users->isEmpty()) {
                return response()->json([
                    'status' => 404,
                    'message' => 'No users found for status: ' . $status
                ]);
            }

            $users->transform(function ($u) {
                $u->employee_name = $u->employee->name ?? null;
                unset($u->employee);
                return $u;
            });

            return response()->json([
                'status' => 200,
                'message' => 'User list retrieved successfully',
                'filter_status' => $status,
                'data' => $users
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Server error - getUsersList',
                'error' => $e->getMessage()
            ]);
        }
    }

    
    public function getUserBankInfo(Request $request, $id)
    {
        try {
            $authUser = $request->user();
            $role = $authUser->role;

            if (!in_array($role, ['admin', 'employee'])) {
                return response()->json([
                    'status' => 403,
                    'message' => 'Unauthorized role access'
                ]);
            }

            $user = User::where('id', $id)->where('role', 'user')->first();

            if (!$user) {
                return response()->json([
                    'status' => 404,
                    'message' => 'User not found'
                ]);
            }

            // Employee can only access their associated users
            if ($role === 'employee' && $user->employee_id != $authUser->id) {
                return response()->json([
                    'status' => 403,
                    'message' => 'You are not authorized to view this user’s bank info'
                ]);
            }

            $bankInfo = UserBankInfo::where('user_id', $user->id)->first();

            if (!$bankInfo) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Bank information not found for this user'
                ]);
            }

            return response()->json([
                'status' => 200,
                'data' => $bankInfo
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Server error - getUserBankInfo',
                'error' => $e->getMessage()
            ]);
        }
    }

    public function getUserDocInfo(Request $request, $id)
    {
        try {
            $authUser = $request->user();
            $role = $authUser->role;

            if (!in_array($role, ['admin', 'employee'])) {
                return response()->json([
                    'status' => 403,
                    'message' => 'Unauthorized role access'
                ]);
            }

            $user = User::where('id', $id)->where('role', 'user')->first();

            if (!$user) {
                return response()->json([
                    'status' => 404,
                    'message' => 'User not found'
                ]);
            }

            // Employee can only access their associated users
            if ($role === 'employee' && $user->employee_id != $authUser->id) {
                return response()->json([
                    'status' => 403,
                    'message' => 'You are not authorized to view this user’s document info'
                ]);
            }

              $docInfo = UserDocuments::where('user_id', $user->id)->first();

            if (!$docInfo) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Document information not found for this user'
                ]);
            }

            return response()->json([
                'status' => 200,
                'data' => $docInfo
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Server error - getUserDocInfo',
                'error' => $e->getMessage()
            ]);
        }
    }

    public function getUserPersonalInfo(Request $request, $id)
    {
        try {
            $authUser = $request->user();
            $role = $authUser->role;

            if (!in_array($role, ['admin', 'employee'])) {
                return response()->json([
                    'status' => 403,
                    'message' => 'Unauthorized role access'
                ]);
            }

            $user = User::where('id', $id)->where('role', 'user')->first();

            if (!$user) {
                return response()->json([
                    'status' => 404,
                    'message' => 'User not found'
                ]);
            }

            // Employee can only access their associated users
            if ($role === 'employee' && $user->employee_id != $authUser->id) {
                return response()->json([
                    'status' => 403,
                    'message' => 'You are not authorized to view this user’s personal info'
                ]);
            }

            $personalInfo = UserPersonalInfo::where('user_id', $user->id)->first();

            if (!$personalInfo) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Personal information not found for this user'
                ]);
            }

            return response()->json([
                'status' => 200,
                'data' => $personalInfo
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Server error - getUserPersonalInfo',
                'error' => $e->getMessage()
            ]);
        }
    }

    public function getUserExtraInfo(Request $request, $id)
    {
        try {
            $authUser = $request->user();
            $role = $authUser->role;

            if (!in_array($role, ['admin', 'employee'])) {
                return response()->json([
                    'status' => 403,
                    'message' => 'Unauthorized role access'
                ]);
            }

            $user = User::where('id', $id)->where('role', 'user')->first();

            if (!$user) {
                return response()->json([
                    'status' => 404,
                    'message' => 'User not found'
                ]);
            }

            // Employee can only access their associated users
            if ($role === 'employee' && $user->employee_id != $authUser->id) {
                return response()->json([
                    'status' => 403,
                    'message' => 'You are not authorized to view this user’s extra info'
                ]);
            }

            $extraInfo = UserExtraInfo::where('user_id', $user->id)->first();

            if (!$extraInfo) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Extra information not found for this user'
                ]);
            }

            return response()->json([
                'status' => 200,
                'data' => $extraInfo
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Server error - getUserExtraInfo',
                'error' => $e->getMessage()
            ]);
        }
    }

    public function viewEmployee(Request $request)
    {
        try {
            $authUser = $request->user();
            $role = $authUser->role;

            if ($role !== 'admin') {
                return response()->json([
                    'status' => 403,
                    'message' => 'Unauthorized role access'
                ]);
            }

            $employees = User::where('role', 'employee')->get();

            if ($employees->isEmpty()) {
                return response()->json([
                    'status' => 404,
                    'message' => 'No employees found'
                ]);
            }

            return response()->json([
                'status' => 200,
                'data' => $employees
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Server error - viewEmployee',
                'error' => $e->getMessage()
            ]);
        }
    }

    public function updateEmployee(Request $request, $id){
        try{
            $user = User::findOrFail($id);

            $updateData = $request->only(['name','email','phone','pswView']);
            
            if($request->filled('pswView')){
                $updateData['password'] = bcrypt($request->pswView);
            }

            $user->update($updateData);

            return response()->json([
                'status'=>200,
                'message'=>'Employee updated successfully',
                'data'=>$user
            ]);
        }catch(Exception $e){
                return response()->json(['status'=>500,
                'message'=>'server error',
                'error'=>$e->getMessage()
            ]);
        }
    }

   
    public function addEmployeeByAdmin(Request $request){
        try{
            $validated = $request->validate([
                'name' => 'required|string',
                'email' => 'nullable|email',
                'phone' => 'required|string',
                'pswView' => 'required|string',
                'role' => 'required|string',
            ]);

            $validated['password'] = bcrypt($validated['pswView']);
            $user = User::create($validated);

            return response()->json(['status'=>200,'message'=>'Employee added successfully','data'=>$user]);
        }catch(Exception $e){
            return response()->json(['status'=>500,'message'=>'server error','error'=>$e->getMessage()]);
        }
    }

    public function getEmployeesList(Request $request)
    {
        try {
            $authUser = $request->user();
            $role = $authUser->role;

            if ($role !== 'admin') {
                return response()->json([
                    'status' => 403,
                    'message' => 'Unauthorized role access'
                ]);
            }

            $employees = User::where('role', 'employee')->get();

            if ($employees->isEmpty()) {
                return response()->json([
                    'status' => 404,
                    'message' => 'No employees found'
                ]);
            }

            return response()->json([
                'status' => 200,
                'data' => $employees
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Server error - getEmployeesList',
                'error' => $e->getMessage()
            ]);
        }
    }

    public function employeeUsersList(Request $request, $id)
    {
        try {
            $authUser = $request->user();
            if ($authUser->role !== 'admin') {
                return response()->json([
                    'status' => 403,
                    'message' => 'Unauthorized role access'
                ]);
            }

            $users = User::where('role', 'user')
                ->where('employee_id', $id)
                ->with([
                    'kycTrack.documents',
                    'kycTrack.personalInfo',
                    'kycTrack.bankInfo',
                    'kycTrack.extraInfo'
                ])
                ->get();

            if ($users->isEmpty()) {
                return response()->json([
                    'status' => 404,
                    'message' => 'No users found for this employee'
                ]);
            }

            return response()->json([
                'status' => 200,
                'data' => $users
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Server error - employeeUsersList',
                'error' => $e->getMessage()
            ]);
        }
    }


}

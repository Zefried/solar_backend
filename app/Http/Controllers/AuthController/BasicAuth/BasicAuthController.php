<?php

namespace App\Http\Controllers\AuthController\BasicAuth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class BasicAuthController extends Controller
{
    
    public function adminRegister(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            // 'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:3',
            'phone' => 'required|string|max:20|unique:users,phone', // Uncomment if phone login is required
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->errors()
            ]);
        }

        try {
            $user = User::create([
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'password' => bcrypt($request->input('password')),
                'pswView' => $request->input('password'),
                'phone' => $request->input('phone'),
                'role' => 'admin',
            ]);

            $token = null;
            if ($user) {
                $token = $this->autoLogin($user);
            }

            return response()->json([
                'status' => 200,
                'message' => 'Admin registered successfully',
                'data' => $user,
                'token' => $token
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function employeeRegister(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            //'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:3',
            'phone' => 'required|string|max:20|unique:users,phone', // Uncomment if phone login is required
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->errors()
            ]);
        }

        try {
            $user = User::create([
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'password' => bcrypt($request->input('password')),
                'pswView' => $request->input('password'),
                'phone' => $request->input('phone'),
                'role' => 'employee',
            ]);

            $token = null;
            if ($user) {
                $token = $this->autoLogin($user);
            }

            return response()->json([
                'status' => 200,
                'message' => 'Employee registered successfully',
                'data' => $user,
                'token' => $token
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => $e->getMessage()
            ]);
        }
    }


    public function userRegister(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            // 'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:3',
            'phone' => 'required|string|max:20|unique:users,phone', // Uncomment if phone login is required
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->errors()
            ]);
        }

        try {
            $user = User::create([
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'password' => bcrypt($request->input('password')),
                'pswView' => $request->input('password'),
                'phone' => $request->input('phone'),
                'role' => 'user',
                'employee_id' => $request->input('employee_id'), // associate an employee
            ]);

            $token = null;
            if ($user) {
                $token = $this->autoLogin($user);
            }

            return response()->json([
                'status' => 200,
                'message' => 'User registered successfully',
                'data' => $user,
                'token' => $token
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => $e->getMessage()
            ]);
        }
    }

    //// LOGIN FUNCTION ////

    public function login(Request $request)
    {
       
        $validator = Validator::make($request->all(), [
            // 'email' => 'required|email', // Uncomment if email login required
            // 'phone' => 'required|string|max:20', // Uncomment if phone login required
            // 'password' => 'required|string|min:6', // Uncomment if password is required
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->errors()
            ]);
        }

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ]);
        }

        $email = $request->input('email');
        $phone = $request->input('phone');
        $password = $request->input('password');

        if (!$email && !$phone) {
            return response()->json([
                'status' => 422,
                'message' => 'Email or phone is required'
            ]);
        }

        $user = null;

        // Email + password login
        if ($email && $password) {
            $user = User::where('email', $email)->first();
            if (!$user || !Hash::check($password, $user->password)) {
                return response()->json([
                    'status' => 401,
                    'message' => 'Invalid email or password'
                ]);
            }
        } 
        // Phone + password login
        elseif ($phone && $password) {
            $user = User::where('phone', $phone)->first();
            if (!$user || !Hash::check($password, $user->password)) {
                return response()->json([
                    'status' => 401,
                    'message' => 'Invalid phone or password'
                ]);
            }
        } 
        // Email-only login
        elseif ($email) {
            $user = User::where('email', $email)->first();
            if (!$user) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Email not found'
                ]);
            }
        } 
        // Phone-only login
        elseif ($phone) {
            $user = User::where('phone', $phone)->first();
            if (!$user) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Phone not found'
                ]);
            }
        }

        $token = $this->autoLogin($user);

        return response()->json([
            'status' => 200,
            'message' => 'Logged in successfully',
            'data' => $user->only(['id', 'gender', 'name', 'email', 'phone', 'role']),
            'token' => $token,
        ]);
    }

    public function autoLogin($user)
    {
        try {
            return $user->createToken('auth_token')->plainTextToken;

        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
    
}

<?php

namespace App\Http\Controllers\AuthModule\SimpleAuth\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminAuthController extends Controller
{
    
    public function adminRegister(Request $request)
    {
        try {
       
            $validator = Validator::make($request->all(), [
                'name' => 'required|string',
                // 'gender' => 'required|string',
                'phone' => 'required|digits:10|unique:users,phone',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Validation failed',
                    'errors' => $validator->messages()
                ], 422);
            }

                $data = $request->all();

                $user = User::create([
                    'name'     => $data['name'],
                    // 'gender'   => $data['gender'],
                    'phone'    => $data['phone'],
                    'role'     => 'admin',
                    'password' => Hash::make($data['phone']),
                    'pswView'  => $data['phone'],
                ]);

            $loginData = $this->autoLogin($user);

            return response()->json([
                'status' => 200,
                'message' => 'User registered successfully',
                'data' => [
                    'userData' => $user,
                    'loginData' => $loginData
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function autoLogin($user) {
        $token = $user->createToken('auth_token')->plainTextToken;
        return ['token_type' => 'Bearer', 'access_token' => $token];
    }


    public function adminLogin(Request $request) {
        try {
            $phone = $request->phone;
            $user = User::where('phone', $phone)->first();
    

            if ($user) {
                $user->tokens()->delete();
                $token = $user->createToken('auth_token')->plainTextToken;

                return response()->json([
                    'status' => 200,
                    'message' => 'Login successful',
                    'data' => [
                        'userData' => [
                            'id' => $user->id,
                            'name' => $user->name,
                            'role' => $user->role,
                            // 'gender' => $user->gender,
                        ],
                        'loginData' => [
                            'access_token' => $token,
                            'token_type' => 'Bearer',
                        ]
                    ]
                ]);
            }

            return response()->json([
                'status' => 404,
                'message' => 'User not found',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Login failed',
                'error' => $e->getMessage()
            ]);
        }
    }
}

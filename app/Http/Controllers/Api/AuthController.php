<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;
use App\Http\Controllers\Api\BaseApiController;
use Spatie\Permission\Models\Role;

class AuthController extends BaseApiController
{
    /**
     * Register a new user.
     *
     * @OA\Post(
     *     path="/api/register",
     *     summary="Register a new user",
     *     tags={"Authentication"},
     * 
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "password", "role"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="johndoe@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123"),
     *             @OA\Property(property="role", type="string", enum={"admin", "user"}, example="user")
     *         )
     *     ),
     *     @OA\Response(response=201, description="User registered successfully"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */

    public function register(Request $request)
    {
        try {
            // Validate input and get validated data
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|unique:users',
                'password' => 'required|string|min:8', // Ensure password confirmation
                'role' => 'required|string|in:admin,user', // Validate role input
            ]);

            // Create user using validated data
            $user = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'password' => Hash::make($validatedData['password']),
            ]);

            // Retrieve the existing role
            $role = Role::where('name', $validatedData['role'])->first();

            if (!$role) {
                return $this->sendError('Role not found', [], 404);
            }
            $user->assignRole($role);

            // Return success response
            return $this->sendSuccess(
                'User registered successfully',
                [
                    'user' => $user,
                    'role' => $role->name,
                ],
                201
            );
        } catch (ValidationException $e) {
            return $this->sendError('Validation error', $e->errors(), 422);
        } catch (QueryException $e) {
            return $this->sendError(
                'Database error',
                ['error' => $e->getMessage()],
                500
            );
        } catch (\Exception $e) {
            return $this->sendError(
                'Something went wrong',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Login user and return access token.
     *
     * @OA\Post(
     *     path="/api/login",
     *     summary="Login user",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="johndoe@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Login successful, returns access token"),
     *     @OA\Response(response=401, description="Invalid credentials"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */

    public function login(Request $request)
    {
        try {
            // Validate input and get validated data
            $validatedData = $request->validate([
                'email' => 'required|string|email',
                'password' => 'required|string',
            ]);

            // Check credentials using validated data
            $user = User::where('email', $validatedData['email'])->first();

            if (
                !$user ||
                !Hash::check($validatedData['password'], $user->password)
            ) {
                return $this->sendError('Invalid credentials', [], 401);
            }

            // Generate token
            $token = $user->createToken('auth_token')->plainTextToken;

            // Include user role in the response
            $role = $user->getRoleNames()->first(); // Get the first role assigned to the user

            // Return success response with token and role
            return $this->sendSuccess('Login successful', [
                'token' => $token,
                'role' => $role,
            ]);
        } catch (ValidationException $e) {
            return $this->sendError('Validation error', $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->sendError(
                'Something went wrong',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Logout user and revoke token.
     *
     * @OA\Post(
     *     path="/api/logout",
     *     summary="Logout user",
     *     security={{"sanctum":{}}},
     *     tags={"Authentication"},
     *     @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer token for authentication",
     *         @OA\Schema(type="string", example="Bearer your_token_here")
     *     ),
     *     @OA\Response(response=200, description="Logged out successfully"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=500, description="Something went wrong")
     * )
     */

    public function logout(Request $request)
    {
        try {
            // Revoke the current user's token
            $request
                ->user()
                ->currentAccessToken()
                ->delete();

            // Return success response
            return $this->sendSuccess('Logged out successfully');
        } catch (\Exception $e) {
            return $this->sendError(
                'Something went wrong',
                ['error' => $e->getMessage()],
                500
            );
        }
    }
}

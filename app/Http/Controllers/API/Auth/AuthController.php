<?php

namespace App\Http\Controllers\API\Auth;

use App\Enums\Roles;
use App\Enums\CustomerStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerRegistrationRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegistrationRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Repositories\CustomerRepository;
use App\Repositories\DeviceKeyRepository;
use App\Repositories\UserRepository;
use App\Repositories\WalletRepository;
use App\Services\UserPresenceService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Register a new user and return the registration result.
     *
     * @param  RegistrationRequest  $request  The registration request data
     * @return Some_Return_Value The registration result data
     */
    public function register(CustomerRegistrationRequest $request)
    {
        $businessCategoryId = null;
        if ($request->filled('business_category_id')) {
            $request->validate([
                'business_category_id' => ['integer', 'exists:business_categories,id'],
            ]);
            $businessCategoryId = $request->integer('business_category_id');
        }

        // Create a new user
        $user = UserRepository::registerNewUser($request);

        if ($request->device_key) {
            DeviceKeyRepository::storeByRequest($user, $request);
        }

        // Create a new customer
        CustomerRepository::storeByRequest($user, $businessCategoryId);

        // create wallet
        WalletRepository::storeByRequest($user);

        $user->assignRole(Roles::CUSTOMER->value);

        return $this->json('Registration successfully complete', [
            'user' => new UserResource($user),
            'access' => UserRepository::getAccessToken($user),
        ]);
    }

    /**
     * Login a user.
     *
     * @param  LoginRequest  $request  The login request data
     */
    public function login(LoginRequest $request)
    {
        $businessCategoryId = null;
        if ($request->filled('business_category_id')) {
            $request->validate([
                'business_category_id' => ['integer', 'exists:business_categories,id'],
            ]);
            $businessCategoryId = $request->integer('business_category_id');
        }

        // Authenticate the user
        $user = $this->authenticate($request);
        if ($user?->customer) {
            if (! $user->is_active) {
                return $this->json('Sorry, your account is not active', [], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            if ($user->customer->status !== CustomerStatus::ACTIVE->value) {
                return $this->json('Sorry, your account is banned', [], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            if ($request->device_key) {
                DeviceKeyRepository::storeByRequest($user, $request);
            }

            if (! is_null($businessCategoryId)) {
                CustomerRepository::updateSelectedBusinessCategory($user, $businessCategoryId);
                $user->refresh();
            }

            UserPresenceService::markOnline($user);

            return $this->json('Login successfully', [
                'user' => new UserResource($user),
                'access' => UserRepository::getAccessToken($user),
            ]);
        }

        return $this->json('Credential is invalid!', [], Response::HTTP_BAD_REQUEST);
    }

    /**
     * Authenticate the user and return the user.
     *
     * @param  LoginRequest  $request  The login request
     * @return User|null
     */
    private function authenticate(LoginRequest $request)
    {
        $user = UserRepository::findByPhone($request->phone);
        if (! is_null($user) && Hash::check($request->password, $user->password)) {
            return $user;
        }

        return null;
    }

    /**
     * Logout the user and revoke the token.
     *
     * @model User $user
     *
     * @return string
     */
    public function logout()
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if ($user) {
            UserPresenceService::markOffline($user);

            $tokenId = $user->currentAccessToken()?->id;
            if ($tokenId) {
                $user->tokens()->where('id', $tokenId)->delete();
            }

            return $this->json('Logged out successfully!');
        }

        return $this->json('User not found!', [], Response::HTTP_NOT_FOUND);
    }

    public function updateOnlineStatus(Request $request)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (! $user) {
            return $this->json('User not found!', [], Response::HTTP_NOT_FOUND);
        }

        $isOnline = $request->boolean('is_online', true);

        if ($isOnline) {
            UserPresenceService::touch($user);

            return $this->json('User status updated', [
                'is_online' => true,
                'last_online' => $user->fresh()?->last_online,
            ]);
        }

        UserPresenceService::markOffline($user);

        return $this->json('User status updated', [
            'is_online' => false,
            'last_online' => null,
        ]);
    }

    public function callback(Request $request) {}
}

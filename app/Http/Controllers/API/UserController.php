<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\UserAuthRequest;
use App\Http\Requests\UserRequest;
use App\Http\Resources\UserResource;
use App\Repositories\CustomerRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Returns the user profile.
     *
     * @return mixed
     */
    public function index()
    {
        $user = auth()->user();

        return $this->json('profile details', [
            'user' => UserResource::make($user),
        ]);
    }

    /**
     * Updates the user profile.
     *
     * @param  UserRequest  $request  The request object containing the updated user data.
     */
    public function update(UserRequest $request)
    {
        $user = UserRepository::updateByRequest($request, auth()->user());
        $user->refresh();

        return $this->json('Profile updated successfully', [
            'user' => UserResource::make($user),
        ]);
    }

    /**
     * Updates the user profile.
     *
     * @param  UserAuthRequest  $request  The request object containing the updated user data.
     */
    public function updateSingle(UserAuthRequest $request)
    {
        $user = UserRepository::updateSingle($request, auth()->user());
        $user->refresh();

        return $this->json('Profile updated successfully', [
            'user' => UserResource::make($user),
        ]);
    }

    /**
     * Change the user's password.
     *
     * @param  ChangePasswordRequest  $request  The request object containing the new password.
     * @return string The success message.
     *
     * @throws Some_Exception_Class If the current password does not match.
     */
    public function changePassword(ChangePasswordRequest $request)
    {
        /** @var User $user */
        $user = auth()->user();

        // if (! Hash::check($request->current_password, $user->password)) {
        //     return $this->json('Current password does not match', [], 422);
        // }
        if (auth()->user()) {
            // User is authenticated
        } else {
            return $this->json('User not authenticated', [], 401);
        }
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return $this->json('Password changed successfully');
    }

    public function updateSelectedBusinessCategory(Request $request)
    {
        $request->validate([
            'business_category_id' => ['required', 'integer', 'exists:business_categories,id'],
        ]);

        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        if (! $user || ! $user->customer) {
            return $this->json('Customer not found', [], 404);
        }

        CustomerRepository::updateSelectedBusinessCategory(
            $user,
            $request->integer('business_category_id')
        );

        $user->refresh();

        return $this->json('Selected business category updated successfully', [
            'user' => UserResource::make($user),
        ]);
    }
}

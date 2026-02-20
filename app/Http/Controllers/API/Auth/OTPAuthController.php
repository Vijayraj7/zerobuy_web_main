<?php

namespace App\Http\Controllers\API\Auth;

use App\Enums\Roles;
use App\Enums\CustomerStatus;
use App\Events\SendOTPMail;
use App\Http\Controllers\Controller;
use App\Http\Requests\OTPRequest;
use App\Http\Requests\OTPVerifyRequest;
use App\Http\Resources\UserResource;
use App\Models\VerifyManage;
use App\Repositories\DeviceKeyRepository;
use App\Repositories\UserRepository;
use App\Repositories\VerificationCodeRepository;
use App\Services\SmsGatewayService;
use App\Services\UserPresenceService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class OTPAuthController extends Controller
{
    /**
     * Send OTP for login/registration.
     * Works for both existing and new users.
     *
     * @param  OTPRequest  $request
     * @return json
     */
    public function sendLoginOTP(OTPRequest $request)
    {
        $test_numbers = config('app.test_phone_numbers', []);
        $user = UserRepository::findByPhone($request->phone);
        $isNewUser = is_null($user);

        // For new users, we still send OTP but don't create the user yet
        // For existing users, check if account is active
        if (! $isNewUser && ! $user->is_active) {
            return $this->json('Sorry, your account is not active', [], 422);
        }

        if (! $isNewUser && $user?->customer && $user->customer->status !== CustomerStatus::ACTIVE->value) {
            return $this->json('Sorry, your account is banned', [], 422);
        }

        $verifyManage = Cache::rememberForever('verify_manage', function () {
            return VerifyManage::first();
        });

        $type = $verifyManage?->register_otp_type ?? 'phone';

        // Check if phone is a test number
        $isTestNumber = in_array($request->phone, $test_numbers);

        if ($isTestNumber) {
            // For test numbers, always use OTP "1234"
            $verificationCode = VerificationCodeRepository::findOrCreateByContact($request->phone);
            $verificationCode->otp = '1234';
            $verificationCode->save();
            $OTP = '1234';
        } else {
            // Create a new verification code using the phone number
            $verificationCode = VerificationCodeRepository::findOrCreateByContact($request->phone);
            $OTP = $verificationCode->otp;
        }

        $messageType = $isNewUser ? 'Registration' : 'Login';
        $message = 'Your ' . $messageType . ' OTP is ' . $OTP;

        $responseMessage = null;
        $emailOrPhone = null;
        $phoneCode = $request->phone_code ?? '+91';

        if ($type == 'phone') {
            // Skip sending SMS for test numbers
            if (!$isTestNumber) {
                try {
                    $phoneNumber = $request->phone; // may be raw 10-digit or concatenated
                    (new SmsGatewayService)->sendSMS($phoneCode, $phoneNumber, $message, $OTP);
                } catch (\Throwable $e) {
                    // swallow SMS errors for security; do not leak
                }
            }
            $responseMessage = 'Your ' . $messageType . ' code is sent to your phone';
            $emailOrPhone = $phoneCode . $request->phone;
        } else {
            // Skip sending email for test numbers
            if (!$isTestNumber) {
                // Send OTP via email when register_otp_type is email
                try {
                    $email = $request->phone; // frontend passes email in `phone` field per existing validators
                    // Dispatch event with correct arguments (email, message, otp)
                    SendOTPMail::dispatch($email, $message, $OTP);
                } catch (\Throwable $e) {
                    // swallow email errors; do not leak
                }
            }
            $responseMessage = 'Your ' . $messageType . ' code is sent to your email';
            $emailOrPhone = $request->phone;
        }

        return $this->json($responseMessage, [
            'email_or_phone' => $emailOrPhone,
            'phone_code' => $phoneCode,
            'is_new_user' => $isNewUser,
            'otp' => (app()->environment('local') || $isTestNumber) ? $OTP : null,
        ]);
    }

    /**
     * Verify OTP for login/registration.
     * If user exists: login and return access token
     * If user is new: return flag to proceed with registration
     *
     * @param  OTPVerifyRequest  $request
     * @return json
     */
    public function verifyLoginOTP(OTPVerifyRequest $request)
    {
        $user = UserRepository::findByPhone($request->phone);
        $isNewUser = is_null($user);

        // Verify the OTP
        $verificationCode = VerificationCodeRepository::checkOTP($request->phone, $request->otp);

        if (!$verificationCode) {
            return $this->json('Invalid otp', [], Response::HTTP_BAD_REQUEST);
        }

        if ($isNewUser) {
            // New user - return token for registration step
            return $this->json('OTP verified. Please complete registration.', [
                'is_new_user' => true,
                'phone' => $request->phone,
                'verification_token' => $verificationCode->token,
            ]);
        } else {
            /** @var \App\Models\User $existingUser */
            $existingUser = $user;

            // Existing user - ensure user is a customer before allowing login
            // (same rule as AuthController::login)
            if (! $existingUser?->customer) {
                return $this->json('Login as customer only!', [], Response::HTTP_BAD_REQUEST);
            }

            if (! $existingUser->is_active) {
                return $this->json('Sorry, your account is not active', [], 422);
            }

            if ($existingUser->customer->status !== CustomerStatus::ACTIVE->value) {
                return $this->json('Sorry, your account is banned', [], 422);
            }

            // Existing user - login directly
            $verifyManage = Cache::rememberForever('verify_manage', function () {
                return VerifyManage::first();
            });
            $type = $verifyManage?->register_otp_type ?? 'phone';

            // Mark the user as verified
            if (! $existingUser->phone_verified_at) {
                $existingUser->update(['phone_verified_at' => now()]);
            }

            // Store device key if provided
            if ($request->device_key) {
                DeviceKeyRepository::storeByRequest($existingUser, $request);
            }

            // Delete the verification code after successful login
            $verificationCode->delete();

            UserPresenceService::markOnline($existingUser);

            return $this->json('Login successfully', [
                'is_new_user' => false,
                'user' => new UserResource($existingUser),
                'access' => UserRepository::getAccessToken($existingUser),
            ]);
        }
    }

    /**
     * Complete registration after OTP verification.
     * This is called for new users to complete their profile.
     *
     * @param  Request  $request
     * @return json
     */
    public function completeRegistration(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'phone' => 'required|unique:users,phone',
            'verification_token' => 'required',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'nullable|min:6|confirmed',
        ]);

        // Verify the token
        $verificationCode = VerificationCodeRepository::checkByToken($request->verification_token);
        if (!$verificationCode) {
            return $this->json('Invalid verification token', [], Response::HTTP_BAD_REQUEST);
        }

        $contact = $verificationCode->phone; // may be phone or email depending on OTP mode
        $isEmailContact = filter_var($contact, FILTER_VALIDATE_EMAIL) !== false;
        if ($isEmailContact) {
            if ($contact !== $request->email) {
                return $this->json('Invalid verification token', [], Response::HTTP_BAD_REQUEST);
            }
        } else {
            if ($contact !== $request->phone) {
                return $this->json('Invalid verification token', [], Response::HTTP_BAD_REQUEST);
            }
        }

        // Check if user already exists
        $existingUser = $isEmailContact
            ? \App\Models\User::where('email', $request->email)->first()
            : UserRepository::findByPhone($request->phone);
        if ($existingUser) {
            return $this->json('User already exists', [], Response::HTTP_BAD_REQUEST);
        }

        // Create the user
        /** @var \App\Models\User $user */
        $user = UserRepository::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'password' => $request->password ? \Illuminate\Support\Facades\Hash::make($request->password) : null,
            'phone_verified_at' => now(),
            'is_active' => true,
        ]);

        // Store device key if provided
        if ($request->device_key) {
            DeviceKeyRepository::storeByRequest($user, $request);
        }

        // Create customer profile
        \App\Repositories\CustomerRepository::storeByRequest($user);

        // Create wallet
        \App\Repositories\WalletRepository::storeByRequest($user);

        // Assign customer role
        $user->assignRole(Roles::CUSTOMER->value);

        // Delete the verification code
        $verificationCode->delete();

        UserPresenceService::markOnline($user);

        return $this->json('Registration successfully complete', [
            'user' => new UserResource($user),
            'access' => UserRepository::getAccessToken($user),
        ]);
    }
}

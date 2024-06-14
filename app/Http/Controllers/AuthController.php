<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\JsonResponse;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Models\UserVerification;
use Illuminate\Http\Request;
use Infobip\Configuration;
use Infobip\ApiException;
use Infobip\Model\SmsAdvancedTextualRequest;
use Infobip\Model\SmsDestination;
use Infobip\Model\SmsTextualMessage;
use Illuminate\Support\Carbon;
use Infobip\Api\SmsApi;


class AuthController extends Controller
{
        
    public function showUser()
    {
        $user = User::select('id', 'name', 'email', 'phone_number', 'role', 'provider_id', 'avatar', 'provider')->get();
        if ($user->isEmpty()) {
            return response()->json([
                'message' => 'There are no users in the list',
                'users' => $user
            ], 204);
        } else {
            return response()->json([
                'Message' => 'List of all users',
                'users' => $user
            ], 200);
        }
    }
    
    public function profile()
    {
        $user = auth()->user();
        
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => "User not authenticated",
            ], 401);
        }

        $userData = $user->only('id', 'name', 'email', 'phone_number', 'role', 'provider_id', 'avatar', 'provider');

        return response()->json([
            'status' => true,
            'message' => "Successfully Authorized",
            'user' => $userData,
        ]);
    }

    
    public function register(Request $request)
    {
        // Validate the request data
        $fields = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users,email',
            'phone_number' => 'required|string|unique:users,phone_number',
            'password' => 'required|string|confirmed|min:8',
            'provider' => 'nullable|string',
            'provider_id' => 'nullable|string',
            'avatar' => 'nullable|string'
        ]);        

        // Create a new user
        $providerID = Str::random(32);
        $user = User::create([
            'name' => $fields['name'],
            'email' => $fields['email'],
            'phone_number' => $fields['phone_number'],
            'password' => bcrypt($fields['password']),
            'provider' => $fields['provider'] ?? null,
            'provider_id' => $providerID,
            'avatar' => $fields['avatar'] ?? null,
            'role' => 'customer' // Assign default role as 'customer'
        ]);

        // Generate API token for the user
        $token = $user->createToken('myapptoken')->plainTextToken;

        // Prepare the response
        $response = [
            'user' => $user,
            'token' => $token
        ];

        return response($response, 201);
    }

    public function loginUser(Request $request)
    {
        try {
            if ($request->has(['email', 'password'])) {
                // Login with email/password
                $credentials = $request->only('email', 'password');

                if (!Auth::attempt($credentials)) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Email & Password do not match with our records.',
                    ], 401);
                }
            } elseif ($request->has(['mobile_no', 'password'])) {
                // Login with mobile_no/password
                $credentials = $request->only('mobile_no', 'password');

                if (!Auth::attempt($credentials)) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Mobile & Password do not match with our records.',
                    ], 401);
                }

                // Generate and send OTP
                $this->generateAndSendOTP($request->mobile_no);
                $user = Auth::user();
                return response()->json([
                    'status' => true,
                    'message' => 'OTP sent to your mobile for verification.',
                    'user' => $user,
                ], 200);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid login request. Please provide either email/password or mobile_no/password.',
                ], 400);
            }

            // If authentication is successful, return user data
            $user = Auth::user();
             // Token generation logic
            if (!empty($user)) {
            if (Hash::check($request->password, $user->password)) {
                // Login is ok
                $tokenInfo = $user->createToken("myapptoken");
                $token = $tokenInfo->plainTextToken; // Token value

                return response()->json([
                    'status' => true,
                    'message' => 'Login successful',
                    'token' => $token,
                    'user' => $user, // Adding user data to response
                ], 200);
            }
        }

        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    private function generateAndSendOTP($mobile_no)
    {
        // Find the user with the provided mobile number
        $user = User::where('mobile_no', $mobile_no)->first();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found with the provided mobile number.'
            ], 404);
        }

        // Check if a verification record already exists for the user
        $userVerification = UserVerification::where('user_id', $user->id)->latest()->first();
        $now = Carbon::now();
        $expireAt = $now->addMinutes(10);

        // If a verification record exists and it's not expired, return it
        if ($userVerification && $now->isBefore($userVerification->expire_at)) {
            return $userVerification;
        }

        // Generate OTP
        $otp = rand(100000, 999999);

        // Save OTP to the database
        $userVerification = UserVerification::create([
            'user_id' => $user->id,
            'otp' => $otp,
            'expire_at' => $expireAt,
        ]);

        // Send OTP via SMS
        $this->sendSMS($mobile_no, $otp);
    }





    public function sendSMS($mobile_no, $otp)
    {
        $configuration = new Configuration(
            host: '7e2fb382b290e1780bd19241e8af7b68-feef6bdc-1b8e-4b0d-a3e0-e1ae4ccf043f',
            apiKey: 'l3dqmj.api.infobip.com'
        );
        $sendSmsApi = new SmsApi(config: $configuration);

        // Format the mobile number to match Infobip's requirements
        $formatted_mobile_no = '+855' . preg_replace('/\D/', '', $mobile_no);

        // Prepare SMS message
        $message = new SmsTextualMessage(
            destinations: [
                new SmsDestination(to: $formatted_mobile_no)
            ],
            from: 'InfoSMS', // Sender ID
            text: 'Your OTP verification code is: ' . $otp
        );  

        // Create SMS request
        $request = new SmsAdvancedTextualRequest(messages: [$message]);

        try {
            // Send SMS message
            $smsResponse = $sendSmsApi->sendSmsMessage($request);
            return response()->json([
                'status' => true,
                'message' => 'OTP sent successfully via SMS.'
            ], 200);
        } catch (ApiException $apiException) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to send OTP via SMS: '
            ], 500);
        }
    }



    public function redirectToAuth(): JsonResponse
    {
        $redirectUrl = Socialite::driver('google')
            ->stateless()
            ->redirect()
            ->getTargetUrl();

        return response()->json([
            'url' => $redirectUrl,
        ]);
    }



    public function handleAuthCallback(): JsonResponse
    {
        try {
            /** @var SocialiteUser $socialiteUser */
            $socialiteUser = Socialite::driver('google')
                ->stateless()
                ->user();
        } catch (ClientException $e) {
            return response()->json(['error' => 'Invalid credentials provided.'], 422);
        }

        // Check if the user already exists in the database by their email
        $user = User::where('email', $socialiteUser->getEmail())->first();

        if ($user) {
            // If the user exists, log them in
            Auth::login($user);

            return response()->json([
                'user' => $user,
                'access_token' => $user->createToken('google-token')->plainTextToken,
                'token_type' => 'Bearer',
            ]);
        } else {
            // Generate a random password
            $password = Str::random(10);

            // Create a new user with the provided information
            $newUser = User::create([
                'name' => $socialiteUser->getName(),
                'email' => $socialiteUser->getEmail(),
                'password' => Hash::make($password),
                'email_verified_at' => now(),
                'provider' => 'google',
                'provider_id' => $socialiteUser->getId(),
                'avatar' => $socialiteUser->getAvatar(),
            ]);

            // Log in the new user
            Auth::login($newUser);

            return response()->json([
                'user' => $newUser,
                'access_token' => $newUser->createToken('google-token')->plainTextToken,
                'token_type' => 'Bearer',
            ]);
        }
    }

    public function loginAdmin(Request $request)
{
    try {
        if ($request->has(['email', 'password'])) {
            // Login with email/password
            $credentials = $request->only('email', 'password');

            if (!Auth::attempt($credentials)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Email & Password do not match with our records.',
                ], 401);
            }

            // If authentication is successful, check if the user is an admin
            $admin = Auth::user();
            
            if ($admin->role !== 'admin') { // Check if the role is not 'admin'
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized access. Only admins can log in here.',
                ], 403);
            }

            // Create token for the admin
            $token = $admin->createToken("adminToken")->plainTextToken;

            return response()->json([
                'status' => true,
                'message' => 'Login successful',
                'token' => $token,
                'admin' => $admin, // Adding admin data to response
            ], 200);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Invalid login request. Please provide email and password.',
            ], 400);
        }
    } catch (\Throwable $th) {
        return response()->json([
            'status' => false,
            'message' => $th->getMessage(),
        ], 500);
    }
}


}




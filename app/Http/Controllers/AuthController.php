<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Account;
use App\Models\OTP;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use App\Mail\OTPMail;
use OTPHP\TOTP;

class AuthController extends ApiController
{
    public function login(Request $request){
        try{
            // Kiểm tra đầu vào
            $data = $request->validate([
                'username' => 'required|string',
                'password' => 'required|string|min:6',
            ], [
                'username.required' => 'Please enter your username.',
                'password.required' => 'Please enter your password.',
                'password.min' => 'Password must a least 6',
            ]);

            // Kiểm tra username
            $user = Account::where('username', $data['username'])->first();

            if(!$user){
                return $this->sendResponse([], false, 'Username Incorrect', 400);
            }

            // Kiểm tra password
            if($user && Hash::check($data['password'], $user->password)){
                return $this->sendResponse([], true, 'Please Authen With Google Authenticator', 200);
            }
            else{
                return $this->sendResponse([], false, 'Password Incorrect', 400);
            }
        }
        catch(\Exception $e){
            return $this->sendResponse([], false, $e->getMessage(), 400);
        }
    }

    public function register(Request $request){
        try{
            //Kiểm tra đầu vào
            $data = $request->validate([
                'username' => 'required|string|unique:account',
                'password' => 'required|string|min:6',
                'repassword' => 'required|string|min:6|same:password',
                'email' => 'required',
                'phone' => 'required',
            ], [
                'username.required' => 'Please enter your username.',
                'password.required' => 'Please enter your password.',
                'repassword.required' => 'Please enter your password confirm.',
                'repassword.same' => 'The password confirm does not match',
                'password.min' => 'Password must a least 6',
                'email.required' => 'Please enter your email.',
                'email.email' => 'Please enter correct email format.',
                'phone.required' => 'Please enter your phone',
            ]);
    
            // Tạo account ứng với Account Model
            $account = new Account([
                'username' => $data['username'],
                'password' => Hash::make($data['password']),
                'email' => $data['email'],
                'phone' => $data['phone'],
                'status' => 'active',
            ]);
    
            $account->save();
    
            return $this->sendResponse([], true, 'Register Successfully', 200);
        }
        catch(\Exception $e){
            return $this->sendResponse([], false, $e->getMessage(), 400);
        }
    }

    public function showQRCode(Request $request){
        try{
            $request->validate([
                'username' => 'required',
            ]);

            $username = $request->username;

            $user = Account::where('username', $username)->first();

            if(!$user){
                return $this->sendResponse([], false, 'User Not Found', 400);
            }

            $otp = TOTP::generate();
            $secret = $otp->getSecret();
            
            $otp = TOTP::create($secret);
            $otp->setLabel('KTCN');

            $imageDataUri = $otp->getQrCodeUri(
                'https://api.qrserver.com/v1/create-qr-code/?data=[DATA]&size=300x300&ecc=M',
                '[DATA]'
            );

            if($user->qr_code == null && $user->secret == null){
                $user->qr_code = $imageDataUri;
                $user->secret = $secret;

                $user->save();
            }

            $result = [
                "qr_code" => $user->qr_code,
                "secret" => $user->secret,
            ];

            return $this->sendResponse($result);
        }
        catch(\Exception $e){
            return $this->sendResponse([], false, $e->getMessage(), 400);
        }
    }

    public function resetQRCode(Request $request){
        try{
            $request->validate([
                'username' => 'required',
            ]);

            $username = $request->username;

            $user = Account::where('username', $username)->first();

            if(!$user){
                return $this->sendResponse([], false, 'User Not Found', 400);
            }

            $user->qr_code = null;
            $user->secret = null;

            $user->save();

            return $this->sendResponse($user);
        }
        catch(\Exception $e){
            return $this->sendResponse([], false, $e->getMessage(), 400);
        }
    }

    public function confirmOTP(Request $request)
    {
        try {
            $request->validate([
                'username' => 'required',
                'otp' => 'required',
            ]);

            $username = $request->username;

            $user = Account::where('username', $username)->first();

            if(!$user){
                return $this->sendResponse([], false, 'User Not Found', 400);
            }

            $otp = TOTP::create($user->secret);

            if ($otp->verify($request->otp)) {
                return $this->sendResponse($user->toArray(), true, 'Login Successfully', 200);
            } 

            return $this->sendResponse([], false, 'OTP Code Is Incorrect');

        } catch (\Exception $e) {
            return $this->sendResponse([], false, $e->getMessage(), 400);
        }
    }
}

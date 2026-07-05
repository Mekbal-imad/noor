<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\ParentModel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
            'role'     => 'required|in:admin,teacher,parent',
        ]);

        if ($request->role === 'admin') {
            $user = User::where('email', $request->email)->first();
        } elseif ($request->role === 'teacher') {
            $user = Teacher::where('email', $request->email)->first();
        } else {
            $user = ParentModel::where('email', $request->email)->first();
        }

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['البريد الإلكتروني أو كلمة المرور غير صحيحة.'],
            ]);
        }

        $token = $user->createToken('noor-token')->plainTextToken;

        return response()->json([
            'message' => 'تم تسجيل الدخول بنجاح',
            'token'   => $token,
            'role'    => $request->role,
            'user'    => $user,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'تم تسجيل الخروج بنجاح'
        ]);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }
    public function myClasses(Request $request)
{
    $teacher = $request->user();

    $classes = $teacher->classes()
        ->with('grade')
        ->get();

    return response()->json($classes);
}
    public function changePassword(Request $request)
{
    $request->validate([
        'current_password' => 'required',
        'new_password'     => 'required|min:6',
    ]);

    $user = $request->user();

    if (!Hash::check($request->current_password, $user->password)) {
        return response()->json(['message' => 'كلمة المرور الحالية غير صحيحة'], 422);
    }

    $user->update(['password' => Hash::make($request->new_password)]);

    return response()->json(['message' => 'تم تغيير كلمة المرور بنجاح']);
}
}
<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Http\Requests\AdminLoginRequest;
use App\Http\Requests\UserLoginRequest;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Contracts\LogoutResponse;
use Illuminate\Support\Facades\Auth;
use App\Models\User;


class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::registerView(function () {
            return view('auth.register');
        });

        Fortify::loginView(function () {
            return view('auth.login');
        });

        Fortify::verifyEmailView(function() {
            return view('auth.verify-email');
        });

        Fortify::authenticateUsing(function ($request) {

        // 1. URLによって、実行するFormRequest（未入力バリデーション）を切り替える
        if ($request->is('admin*')) {
            app(AdminLoginRequest::class); // 管理者用
        } else {
            app(UserLoginRequest::class);  // 一般ユーザー用
        }

        $user = User::where('email', $request->email)->first();

        if ($request->is('admin*')) {
            if ($user && Hash::check($request->password, $user->password) && $user->is_admin === 1) {
                return $user;
            }
        }

        else {
            if ($user && Hash::check($request->password, $user->password)) {
                return $user;
            }
        }

        throw ValidationException::withMessages([
            Fortify::username() => 'ログイン情報が登録されていません',
        ]);
    });

        $this->app->singleton(LoginResponse::class, function ($request) {
        return new class implements LoginResponse {
            public function toResponse($request)
            {
               // 💡 ログインしたユーザー情報を取得
                $user = Auth::user();

                // 💡 ユーザーが存在し、かつ管理者（is_admin が 1 または true）の場合
                if ($user && $user->is_admin) { 
                    return redirect()->route('admin.index'); // 管理者一覧ページへ
                }

                // 一般ユーザー、または権限がない場合は通常画面へ
                return redirect(config('fortify.home'));
            }
        };
    });

        $this->app->singleton(LogoutResponse::class, function ($request) {
        return new class implements LogoutResponse {
            public function toResponse($request)
            {
                // 💡 ログアウトする直前のURLに 'admin' が含まれていた場合
                if ($request->is('admin*') || str_contains(url()->previous(), '/admin')) {
                    return redirect()->route('admin.login'); // 管理者ログイン画面へ
                }

                // 一般ユーザーは通常のログイン画面へ
                return redirect()->route('login');
            }
        };
    });

    RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->email;

            return Limit::perMinute(10)->by($email . $request->ip());
        });
    }
}



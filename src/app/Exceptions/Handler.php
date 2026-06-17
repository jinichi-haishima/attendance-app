<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // 💡 引数を「Throwable $e」という一番安全な形にして、500エラーを絶対に防ぐ
        $this->renderable(function (Throwable $e, $request) {
            if ($request->is('api/*')) {
                
                // 🔒 1. 認可エラー（403）の翻訳ルール
                if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                    return response()->json([
                        'error' => 'この操作を実行する権限がありません。'
                    ], 403);
                }

                // 🔍 2. データなしエラー（404）の翻訳ルール
                if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException || 
                    $e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                    return response()->json([
                        'error' => '指定されたデータが見つかりません。'
                    ], 404);
                }
            }
        });
    }
}


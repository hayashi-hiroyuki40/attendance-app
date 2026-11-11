<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * メール認証に関する処理を制御するコントローラー。
 *
 * ユーザーのメールアドレス確認画面の表示、認証処理、および認証メールの再送信を行う。
 */
class EmailVerificationController extends Controller
{
    /**
     * メール認証誘導画面（お知らせ画面）を表示する。
     *
     * すでにメール認証が完了している場合は勤怠画面へリダイレクトする。
     *
     * @param  Request  $request  現在のHTTPリクエスト
     * @return RedirectResponse|View 認証済みの場合はリダイレクト、未認証の場合は認証画面のビュー
     */
    public function notice(Request $request): RedirectResponse|View
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect('/attendance');
        }

        return view('auth.verify-email');
    }

    /**
     * メール認証を実行する。
     *
     * 送信された署名付きURLの検証を行い、認証を完了させて勤怠画面へリダイレクトする。
     *
     * @param  EmailVerificationRequest  $request  メール認証用の検証済みリクエスト
     * @return RedirectResponse 勤怠画面へのリダイレクト
     */
    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        $request->fulfill();

        return redirect('/attendance');
    }

    /**
     * メール認証通知を再送信する。
     *
     * すでに認証済みの場合は勤怠画面へリダイレクトする。
     *
     * @param  Request  $request  現在のHTTPリクエスト
     * @return RedirectResponse 直前のページ、または勤怠画面へのリダイレクト
     */
    public function resend(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect('/attendance');
        }

        $request->user()->sendEmailVerificationNotification();

        return back();
    }
}

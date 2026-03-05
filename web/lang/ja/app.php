<?php

return [
    /*
|--------------------------------------------------------------------------
| Application Language Lines
|--------------------------------------------------------------------------
|
| The following language lines are used by the paginator library to build
| the simple pagination links. You are free to change them to anything
| you want to customize your views to better match your application.
|
 */
    'errors'        => [
        '401'    => [
            'title'   => '認証が必要です',
            'message' => 'このページにアクセスするにはログインが必要です。',
            'login'   => 'ログイン',
            'back'    => '戻る',
        ],
        '403'    => [
            'title'   => 'アクセス権限がありません',
            'message' => 'このページにアクセスする権限がありません。',
            'back'    => '戻る',
            'home'    => 'ホームに戻る',
            'logout'  => 'ログアウト',
        ],
        '404'    => [
            'title'   => 'ページが見つかりません',
            'message' => 'お探しのページは存在しません。',
            'back'    => '戻る',
            'home'    => 'ホームに戻る',
        ],
        '419'    => [
            'title'         => 'ページの有効期限切れ',
            'message'       => 'セッションの有効期限が切れました。続行するには再度ログインしてください。',
            'back_to_login' => 'ログイン画面に戻る',
        ],
        '429'    => [
            'title'   => 'リクエストが多すぎます',
            'message' => 'リクエストが多すぎます。しばらくしてからもう一度お試しください。',
            'back'    => '戻る',
            'refresh' => '再試行',
        ],
        '500'    => [
            'title'   => 'サーバーエラー',
            'message' => 'サーバーで問題が発生しました。問題の解決に取り組んでいます。',
            'back'    => '戻る',
            'home'    => 'ホームに戻る',
            'report'  => '問題を報告',
        ],
        '503'    => [
            'title'   => 'サービス利用不可',
            'message' => 'サービスは一時的に利用できません。後でもう一度お試しください。',
            'back'    => '戻る',
            'refresh' => 'ページを更新',
        ],
        'common' => [
            'go_back' => '戻る',
            'go_home' => 'ホームに戻る',
            'login'   => 'ログイン',
            'logout'  => 'ログアウト',
            'refresh' => 'ページを更新',
        ],
    ],

    'data_deletion' => [
        'title'              => 'データ削除リクエスト',
        'page_title'         => 'データ削除リクエスト',
        'success_page_title' => 'リクエスト送信完了',
        'success_title'      => 'リクエスト送信完了',
        'send_request'       => 'リクエストを送信',
        'request_submitted'  => 'データ削除リクエストを受領いたしました',
        'back_to_form'       => 'フォームに戻る',
        'back_to_policy'     => 'プライバシーポリシーに戻る',
        'description_1'      => 'システムから個人データの削除をリクエストする場合は、以下のボタンをクリックしてデータ削除リクエストを送信できます。',
        'description_2'      => 'リクエストが送信されると、当社のチームがプライバシーポリシーに従ってレビューおよび処理を行います。リクエストが処理されると、確認メールが送信されます。',
        'description_3'      => 'このプロセスは完了までに時間がかかる場合があり、リクエストが承認されると、すべての個人データがシステムから永続的に削除されることにご注意ください。',
        'modal_title'        => 'データ削除リクエスト',
        'modal_message'      => 'データの削除をリクエストしてもよろしいですか？この操作は元に戻せません。',
        'modal_cancel'       => 'キャンセル',
        'success_message'    => 'リクエストが正常に送信されました。まもなく処理いたします。',
    ],

    'policy'        => [
        'title'               => 'プライバシーポリシー',
        'page_title'          => 'プライバシーポリシー',
        'last_updated'        => '最終更新',
        'content_unavailable' => 'コンテンツが利用できません',
        'updating_message'    => '現在、プライバシーポリシーを更新中です。後でもう一度ご確認ください。',
    ],
];

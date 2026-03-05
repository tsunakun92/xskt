<?php

return [
    'title'                => '権限管理',
    'group'                => 'グループ',
    'permissions'          => '権限',

    // Group titles
    'groups'               => [
        'admin'       => '管理者',
        'users'       => 'ユーザー',
        'roles'       => 'ロール',
        'permissions' => '権限',
        'profile'     => 'プロフィール',
        'changelog'   => '変更履歴',
    ],

    // Action labels
    'actions'              => [
        'index'      => '一覧表示',
        'show'       => '詳細表示',
        'create'     => '作成',
        'edit'       => '編集',
        'destroy'    => '削除',
        'permission' => '権限管理',
        'setting'    => '設定管理',
        'admin'      => 'ダッシュボードアクセス',
    ],

    // Specific permissions (override default actions)
    'specific_permissions' => [
        'changelog.index' => '変更履歴を見る',
        'admin'           => 'ダッシュボードアクセス',
        'crm.module'      => 'CRMモジュールアクセス',
        'admin.module'    => '管理者モジュールアクセス',
        'hr.module'       => '人事モジュールアクセス',
    ],

    // Buttons
    'back'                 => '戻る',
    'submit'               => '変更を保存',
    'check_all'            => 'すべて選択',
    'uncheck_all'          => 'すべて解除',
    'selected'             => '選択済み',
    'all_modules'          => 'すべてのモジュール',
    'modules'              => 'モジュール',
];

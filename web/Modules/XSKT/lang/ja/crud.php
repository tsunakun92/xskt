<?php

return [
    /*
    |--------------------------------------------------------------------------
    | CRUD Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used by the paginator library to build
    | the simple pagination links. You are free to change them to anything
    | you want to customize your views to better match your application.
    |
     */
    'create'                        => '作成',
    'edit'                          => '編集',
    'delete'                        => '削除',
    'save'                          => '保存',
    'back'                          => '戻る',
    'cancel'                        => 'キャンセル',
    'show'                          => '表示',
    'action'                        => 'アクション',
    'submit'                        => '送信',
    'list'                          => '一覧',
    'form'                          => 'フォーム',
    'index'                         => 'インデックス',
    'filter'                        => 'フィルター',
    'search'                        => '検索',
    'reset'                         => 'リセット',
    'column_groups'                 => '列グループ',
    'please_select'                 => '選択してください',
    'create_failed'                 => '作成に失敗しました: :value',
    'store_failed'                  => '保存に失敗しました: :value',
    'edit_failed'                   => '編集に失敗しました: :value',
    'update_failed'                 => '更新に失敗しました: :value',
    'delete_failed'                 => '削除に失敗しました: :value',
    'destroy_failed'                => '削除に失敗しました: :value',
    'create_success'                => ':value の作成に成功しました',
    'store_success'                 => ':value の保存に成功しました',
    'edit_success'                  => ':value の編集に成功しました',
    'update_success'                => ':value の更新に成功しました',
    'delete_success'                => ':value の削除に成功しました',
    'destroy_success'               => ':value の削除に成功しました',
    'delete_has_relationship_error' => ':name は :relation で使用されています。まず :relation を削除してください。',
    'delete_confirm'                => '削除の確認',
    'delete_confirm_message'        => 'この項目を削除してもよろしいですか？この操作は元に戻せません。',
    'draws'                         => [
        'title'         => '抽選',
        'id'            => 'ID',
        'region'        => '地域',
        'province_code' => '省コード',
        'station_code'  => '局コード',
        'draw_date'     => '抽選日',

        'confirmed_at'  => '確認日時',
        'status'        => 'ステータス',
        'created_by'    => '作成者',
        'created_at'    => '作成日時',
        'updated_at'    => '更新日時',
        'region_mb'     => '北部 (MB)',
        'region_mt'     => '中部 (MT)',
        'region_mn'     => '南部 (MN)',

        'filter'        => [
            'region'        => '地域',
            'province_code' => '省コード',
            'station_code'  => '局コード',
            'draw_date'     => '抽選日',

            'status'        => 'ステータス',
        ],
    ],
    'results'                       => [
        'title'             => '結果',
        'id'                => 'ID',
        'draw_id'           => '抽選ID',
        'draw_info'         => '抽選情報',
        'prize_code'        => '賞コード',
        'index_in_prize'    => '賞内インデックス',
        'number'            => '番号',
        'confirmed_by_rule' => 'ルールによる確認',
        'not_confirmed'     => '未確認',
        'confirmed'         => '確認済み',
        'status'            => 'ステータス',
        'created_by'        => '作成者',
        'created_at'        => '作成日時',
        'updated_at'        => '更新日時',
        'filter'            => [
            'draw_id'    => '抽選ID',
            'prize_code' => '賞コード',
            'status'     => 'ステータス',
        ],
    ],
];

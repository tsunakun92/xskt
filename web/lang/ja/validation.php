<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
     */

    'accepted'             => ':attribute を承認する必要があります。',
    'accepted_if'          => ':other が :value の場合、:attribute を承認する必要があります。',
    'active_url'           => ':attribute は有効なURLでなければなりません。',
    'after'                => ':attribute は :date より後の日付でなければなりません。',
    'after_or_equal'       => ':attribute は :date 以降の日付でなければなりません。',
    'alpha'                => ':attribute は文字のみを含む必要があります。',
    'alpha_dash'           => ':attribute は文字、数字、ダッシュ、アンダースコアのみを含む必要があります。',
    'alpha_num'            => ':attribute は文字と数字のみを含む必要があります。',
    'array'                => ':attribute は配列でなければなりません。',
    'ascii'                => ':attribute はシングルバイトの英数字と記号のみを含む必要があります。',
    'before'               => ':attribute は :date より前の日付でなければなりません。',
    'before_or_equal'      => ':attribute は :date 以前の日付でなければなりません。',
    'between'              => [
        'array'   => ':attribute の項目数は :min から :max の間でなければなりません。',
        'file'    => ':attribute のファイルサイズは :min から :max キロバイトの間でなければなりません。',
        'numeric' => ':attribute の値は :min から :max の間でなければなりません。',
        'string'  => ':attribute の文字数は :min から :max の間でなければなりません。',
    ],
    'boolean'              => ':attribute フィールドは true または false でなければなりません。',
    'can'                  => ':attribute フィールドには許可されていない値が含まれています。',
    'confirmed'            => ':attribute 確認フィールドが一致しません。',
    'contains'             => ':attribute フィールドに必要な値が含まれていません。',
    'current_password'     => 'パスワードが正しくありません。',
    'date'                 => ':attribute フィールドは有効な日付でなければなりません。',
    'date_equals'          => ':attribute フィールドは :date と等しい日付でなければなりません。',
    'date_format'          => ':attribute フィールドは :format 形式と一致していなければなりません。',
    'decimal'              => ':attribute フィールドは :decimal 小数点以下の桁数でなければなりません。',
    'declined'             => ':attribute フィールドは拒否されなければなりません。',
    'declined_if'          => ':attribute フィールドは :other が :value の場合に拒否されなければなりません。',
    'different'            => ':attribute フィールドと :other は異なっていなければなりません。',
    'digits'               => ':attribute フィールドは :digits 桁でなければなりません。',
    'digits_between'       => ':attribute フィールドは :min から :max 桁の間でなければなりません。',
    'dimensions'           => ':attribute フィールドの画像サイズが無効です。',
    'distinct'             => ':attribute フィールドに重複した値があります。',
    'doesnt_end_with'      => ':attribute フィールドは次のいずれかで終わってはなりません: :values。',
    'doesnt_start_with'    => ':attribute フィールドは次のいずれかで始まってはなりません: :values。',
    'email'                => ':attribute フィールドは有効なメールアドレスでなければなりません。',
    'ends_with'            => ':attribute フィールドは次のいずれかで終わらなければなりません: :values。',
    'enum'                 => '選択された :attribute は無効です。',
    'exists'               => '選択された :attribute は無効です。',
    'extensions'           => ':attribute フィールドは次の拡張子のいずれかでなければなりません: :values。',
    'file'                 => ':attribute フィールドはファイルでなければなりません。',
    'filled'               => ':attribute フィールドには値が必要です。',
    'gt'                   => [
        'array'   => ':attribute フィールドには :value 項目以上が必要です。',
        'file'    => ':attribute フィールドは :value キロバイトより大きくなければなりません。',
        'numeric' => ':attribute フィールドは :value より大きくなければなりません。',
        'string'  => ':attribute フィールドは :value 文字より大きくなければなりません。',
    ],
    'gte'                  => [
        'array'   => ':attribute フィールドには :value 項目以上が必要です。',
        'file'    => ':attribute フィールドは :value キロバイト以上でなければなりません。',
        'numeric' => ':attribute フィールドは :value 以上でなければなりません。',
        'string'  => ':attribute フィールドは :value 文字以上でなければなりません。',
    ],
    'hex_color'            => ':attribute フィールドは有効な16進数の色でなければなりません。',
    'image'                => ':attribute フィールドは画像でなければなりません。',
    'in'                   => '選択された :attribute は無効です。',
    'in_array'             => ':attribute フィールドは :other に存在しなければなりません。',
    'integer'              => ':attribute フィールドは整数でなければなりません。',
    'ip'                   => ':attribute フィールドは有効なIPアドレスでなければなりません。',
    'ipv4'                 => ':attribute フィールドは有効なIPv4アドレスでなければなりません。',
    'ipv6'                 => ':attribute フィールドは有効なIPv6アドレスでなければなりません。',
    'json'                 => ':attribute フィールドは有効なJSON文字列でなければなりません。',
    'list'                 => ':attribute フィールドはリストでなければなりません。',
    'lowercase'            => ':attribute フィールドは小文字でなければなりません。',
    'lt'                   => [
        'array'   => ':attribute フィールドは :value 項目未満でなければなりません。',
        'file'    => ':attribute フィールドは :value キロバイト未満でなければなりません。',
        'numeric' => ':attribute フィールドは :value 未満でなければなりません。',
        'string'  => ':attribute フィールドは :value 文字未満でなければなりません。',
    ],
    'lte'                  => [
        'array'   => ':attribute フィールドは :value 項目以下でなければなりません。',
        'file'    => ':attribute フィールドは :value キロバイト以下でなければなりません。',
        'numeric' => ':attribute フィールドは :value 以下でなければなりません。',
        'string'  => ':attribute フィールドは :value 文字以下でなければなりません。',
    ],
    'mac_address'          => ':attribute フィールドは有効なMACアドレスでなければなりません。',
    'max'                  => [
        'array'   => ':attribute フィールドは :max 項目を超えてはなりません。',
        'file'    => ':attribute フィールドは :max キロバイトを超えてはなりません。',
        'numeric' => ':attribute フィールドは :max を超えてはなりません。',
        'string'  => ':attribute フィールドは :max 文字を超えてはなりません。',
    ],
    'max_digits'           => ':attribute フィールドは :max 桁を超えてはなりません。',
    'mimes'                => ':attribute フィールドは次のタイプのファイルでなければなりません: :values。',
    'mimetypes'            => ':attribute フィールドは次のタイプのファイルでなければなりません: :values。',
    'min'                  => [
        'array'   => ':attribute フィールドは少なくとも :min 項目が必要です。',
        'file'    => ':attribute フィールドは少なくとも :min キロバイトでなければなりません。',
        'numeric' => ':attribute フィールドは少なくとも :min でなければなりません。',
        'string'  => ':attribute フィールドは少なくとも :min 文字でなければなりません。',
    ],
    'min_digits'           => ':attribute フィールドは少なくとも :min 桁でなければなりません。',
    'missing'              => ':attribute フィールドが存在しなければなりません。',
    'missing_if'           => ':other が :value の場合、:attribute フィールドが存在しなければなりません。',
    'missing_unless'       => ':other が :value でない限り、:attribute フィールドが存在しなければなりません。',
    'missing_with'         => ':values が存在する場合、:attribute フィールドが存在しなければなりません。',
    'missing_with_all'     => ':values が存在する場合、:attribute フィールドが存在しなければなりません。',
    'multiple_of'          => ':attribute フィールドは :value の倍数でなければなりません。',
    'not_in'               => '選択された :attribute は無効です。',
    'not_regex'            => ':attribute フィールドの形式が無効です。',
    'numeric'              => ':attribute フィールドは数値でなければなりません。',
    'password'             => [
        'letters'       => ':attribute フィールドには少なくとも1文字が含まれている必要があります。',
        'mixed'         => ':attribute フィールドには少なくとも1つの大文字と1つの小文字が含まれている必要があります。',
        'numbers'       => ':attribute フィールドには少なくとも1つの数字が含まれている必要があります。',
        'symbols'       => ':attribute フィールドには少なくとも1つの記号が含まれている必要があります。',
        'uncompromised' => '指定された :attribute はデータ漏洩に含まれています。別の :attribute を選択してください。',
    ],
    'present'              => ':attribute フィールドが存在しなければなりません。',
    'present_if'           => ':other が :value の場合、:attribute フィールドが存在しなければなりません。',
    'present_unless'       => ':other が :value でない限り、:attribute フィールドが存在しなければなりません。',
    'present_with'         => ':values が存在する場合、:attribute フィールドが存在しなければなりません。',
    'present_with_all'     => ':values が存在する場合、:attribute フィールドが存在しなければなりません。',
    'prohibited'           => ':attribute フィールドは禁止されています。',
    'prohibited_if'        => ':other が :value の場合、:attribute フィールドは禁止されています。',
    'prohibited_unless'    => ':other が :values に含まれていない限り、:attribute フィールドは禁止されています。',
    'prohibits'            => ':attribute フィールドは :other が存在することを禁止します。',
    'regex'                => ':attribute フィールドの形式が無効です。',
    'required'             => ':attribute フィールドは必須です。',
    'required_array_keys'  => ':attribute フィールドには次のエントリが含まれている必要があります: :values。',
    'required_if'          => ':other が :value の場合、:attribute フィールドは必須です。',
    'required_if_accepted' => ':other が承認されている場合、:attribute フィールドは必須です。',
    'required_if_declined' => ':other が拒否されている場合、:attribute フィールドは必須です。',
    'required_unless'      => ':other が :values に含まれていない限り、:attribute フィールドは必須です。',
    'required_with'        => ':values が存在する場合、:attribute フィールドは必須です。',
    'required_with_all'    => ':values が存在する場合、:attribute フィールドは必須です。',
    'required_without'     => ':values が存在しない場合、:attribute フィールドは必須です。',
    'required_without_all' => ':values が一つも存在しない場合、:attribute フィールドは必須です。',
    'same'                 => ':attribute フィールドと :other は一致していなければなりません。',
    'size'                 => [
        'array'   => ':attribute フィールドには :size 項目が含まれている必要があります。',
        'file'    => ':attribute フィールドは :size キロバイトでなければなりません。',
        'numeric' => ':attribute フィールドは :size でなければなりません。',
        'string'  => ':attribute フィールドは :size 文字でなければなりません。',
    ],
    'starts_with'          => ':attribute フィールドは次のいずれかで始まらなければなりません: :values。',
    'string'               => ':attribute フィールドは文字列でなければなりません。',
    'timezone'             => ':attribute フィールドは有効なタイムゾーンでなければなりません。',
    'unique'               => ':attribute は既に使用されています。',
    'uploaded'             => ':attribute のアップロードに失敗しました。',
    'uppercase'            => ':attribute フィールドは大文字でなければなりません。',
    'url'                  => ':attribute フィールドは有効なURLでなければなりません。',
    'ulid'                 => ':attribute フィールドは有効なULIDでなければなりません。',
    'uuid'                 => ':attribute フィールドは有効なUUIDでなければなりません。',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
     */

    'custom'               => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
     */

    'attributes'           => [],
];

<?php

return [
    // Dashboard
    'dashboard_title'                               => 'ログダッシュボード',
    'dashboard_description'                         => 'アプリケーションログを表示および管理',
    'log_file_not_found'                            => 'ログファイルが見つかりません。',
    'log_file_cleared'                              => 'ログファイルが正常にクリアされました。',

    // Controllers - BaseAdminController
    'controller.access_list'                        => 'リストにアクセス中',
    'controller.access_detail'                      => '詳細を表示中',
    'controller.error_view_detail'                  => '詳細表示エラー',
    'controller.access_create_form'                 => '作成フォームにアクセス中',
    'controller.access_edit_form'                   => '編集フォームにアクセス中',
    'controller.error_access_edit_form'             => '編集フォームアクセスエラー',
    'controller.create_success'                     => '作成に成功しました',
    'controller.create_failed'                      => '作成に失敗しました',
    'controller.update_success'                     => '更新に成功しました',
    'controller.update_failed'                      => '更新に失敗しました',
    'controller.delete_success'                     => '削除に成功しました',
    'controller.delete_failed'                      => '削除に失敗しました',
    'controller.permission_denied'                  => 'アクセス拒否 - 権限がありません',

    // Controllers - RoleController
    'controller.role.permission_denied_super_admin' => 'アクセス拒否 - スーパー管理者の権限を編集できません',
    'controller.role.access_permission_page'        => '権限ページにアクセス中',
    'controller.role.update_permission_denied'      => '権限更新拒否 - スーパー管理者の権限を編集できません',
    'controller.role.update_permission_success'     => '権限の更新に成功しました',
    'controller.role.update_permission_failed'      => '権限の更新に失敗しました',

    // Controllers - UserController
    'controller.user.view_denied_super_admin'       => 'アクセス拒否 - スーパー管理者の詳細を表示できません',
    'controller.user.edit_denied_super_admin'       => 'アクセス拒否 - スーパー管理者を編集できません',

    // Controllers - ChangelogController
    'controller.changelog.access_page'              => '変更ログページにアクセス中',
    'controller.changelog.version_not_found'        => '変更ログのバージョンが見つかりません',
    'controller.changelog.get_content_success'      => '変更ログのコンテンツを正常に取得しました',

    // Models - User
    'model.user.creating'                           => '新しいユーザーを作成中',
    'model.user.created'                            => 'ユーザーの作成に成功しました',
    'model.user.updating'                           => 'ユーザーを更新中',
    'model.user.updated'                            => 'ユーザーの更新に成功しました',
    'model.user.logout_important_change'            => '重要な情報変更によりユーザーがログアウトしました',
    'model.user.deleting'                           => 'ユーザーを削除中',
    'model.user.deleted'                            => 'ユーザーの削除に成功しました',

    // Models - Role
    'model.role.creating'                           => '新しいロールを作成中',
    'model.role.created'                            => 'ロールの作成に成功しました',
    'model.role.updating'                           => 'ロールを更新中',
    'model.role.updated'                            => 'ロールの更新に成功しました',
    'model.role.deleting'                           => 'ロールを削除中',
    'model.role.delete_has_relationship'            => 'ロールを削除できません - 関連があります',
    'model.role.deleted'                            => 'ロールの削除に成功しました',

    // Models - Permission
    'model.permission.creating'                     => '新しい権限を作成中',
    'model.permission.created'                      => '権限の作成に成功しました',
    'model.permission.updating'                     => '権限を更新中',
    'model.permission.updated'                      => '権限の更新に成功しました',
    'model.permission.deleting'                     => '権限を削除中',
    'model.permission.delete_has_relationship'      => '権限を削除できません - 関連があります',
    'model.permission.deleted'                      => '権限の削除に成功しました',

    // Services - CacheService
    'service.cache.remember_success'                => 'キャッシュの記憶に成功しました',
    'service.cache.remember_failed'                 => 'キャッシュの記憶に失敗しました',
    'service.cache.put_success'                     => 'キャッシュの保存に成功しました',
    'service.cache.put_failed'                      => 'キャッシュの保存に失敗しました',
    'service.cache.hit'                             => 'キャッシュヒット',
    'service.cache.miss'                            => 'キャッシュミス',
    'service.cache.get_failed'                      => 'キャッシュの取得に失敗しました',
    'service.cache.forget_success'                  => 'キャッシュの削除に成功しました',
    'service.cache.forget_failed'                   => 'キャッシュの削除に失敗しました',
    'service.cache.pattern_forget_success'          => 'キャッシュパターンの削除に成功しました',
    'service.cache.pattern_forget_failed'           => 'キャッシュパターンの削除に失敗しました',

    // Services - ErrorHandlingService
    'service.error.datatables_error'                => 'DataTablesエラー: :message',

    // Services - QueryBuilderService
    'service.query.cannot_check_appended'           => ':model_classの追加属性を確認できません',

    // Services - PaginationService
    'service.pagination.cannot_check_appended'      => ':model_classの追加属性を確認できません',
    'service.pagination.sort_failed'                => 'コレクションの並べ替えに失敗しました',
    'service.pagination.paginate_failed'            => 'コレクションのページネーションに失敗しました',

    // Services - OptionLoaderService
    'service.option.load_success'                   => 'オプションの読み込みに成功しました',
    'service.option.load_failed'                    => 'データベースからのオプション読み込みエラー',
    'service.option.load_custom_failed'             => 'カスタムクエリでのオプション読み込みエラー',
    'service.option.preload_failed'                 => ':keyのオプションのプリロードエラー',

    // Middleware
    'middleware.route_permission_denied'            => 'アクセス拒否 - ルートへのアクセス権限がありません',

    // Commands
    'command.clearall.start'                        => 'キャッシュと設定のクリアを開始',
    'command.clearall.success'                      => 'コマンドの実行に成功しました',
    'command.clearall.error'                        => 'コマンドの実行エラー',
    'command.clearall.complete'                     => 'キャッシュと設定のクリアが完了しました',

    // Utils - SqlHandler
    'utils.sql.transaction_failed'                  => 'トランザクションに失敗しました',

    // Utils - AjaxHandle
    'utils.ajax.success'                            => 'Ajax成功レスポンス',
    'utils.ajax.error'                              => 'Ajaxエラーレスポンス: :message',
    'utils.ajax.error_simple'                       => 'Ajaxエラーレスポンス',
    'utils.ajax.validation_error'                   => 'Ajaxバリデーションエラーレスポンス',
];

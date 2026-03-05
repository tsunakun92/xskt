<?php

return [
    // Dashboard
    'dashboard_title'                               => 'Logging Dashboard',
    'dashboard_description'                         => 'View and manage application logs',
    'log_file_not_found'                            => 'Log file not found.',
    'log_file_cleared'                              => 'Log file cleared successfully.',

    // Controllers - BaseAdminController
    'controller.access_list'                        => 'Accessing list',
    'controller.access_detail'                      => 'Viewing details',
    'controller.error_view_detail'                  => 'Error viewing details',
    'controller.access_create_form'                 => 'Accessing create form',
    'controller.access_edit_form'                   => 'Accessing edit form',
    'controller.error_access_edit_form'             => 'Error accessing edit form',
    'controller.create_success'                     => 'Created successfully',
    'controller.create_failed'                      => 'Create failed',
    'controller.update_success'                     => 'Updated successfully',
    'controller.update_failed'                      => 'Update failed',
    'controller.delete_success'                     => 'Deleted successfully',
    'controller.delete_failed'                      => 'Delete failed',
    'controller.permission_denied'                  => 'Access denied - no permission',

    // Controllers - RoleController
    'controller.role.permission_denied_super_admin' => 'Access denied - cannot edit super admin permissions',
    'controller.role.access_permission_page'        => 'Accessing permission page',
    'controller.role.update_permission_denied'      => 'Permission update denied - cannot edit super admin permissions',
    'controller.role.update_permission_success'     => 'Permission updated successfully',
    'controller.role.update_permission_failed'      => 'Permission update failed',

    // Controllers - UserController
    'controller.user.view_denied_super_admin'       => 'Access denied - cannot view super admin details',
    'controller.user.edit_denied_super_admin'       => 'Access denied - cannot edit super admin',

    // Controllers - ChangelogController
    'controller.changelog.access_page'              => 'Accessing changelog page',
    'controller.changelog.version_not_found'        => 'Changelog version not found',
    'controller.changelog.get_content_success'      => 'Successfully retrieved changelog content',

    // Models - User
    'model.user.creating'                           => 'Creating new user',
    'model.user.created'                            => 'User created successfully',
    'model.user.updating'                           => 'Updating user',
    'model.user.updated'                            => 'User updated successfully',
    'model.user.logout_important_change'            => 'User logged out due to important information change',
    'model.user.deleting'                           => 'Deleting user',
    'model.user.deleted'                            => 'User deleted successfully',

    // Models - Role
    'model.role.creating'                           => 'Creating new role',
    'model.role.created'                            => 'Role created successfully',
    'model.role.updating'                           => 'Updating role',
    'model.role.updated'                            => 'Role updated successfully',
    'model.role.deleting'                           => 'Deleting role',
    'model.role.delete_has_relationship'            => 'Cannot delete role - has relationships',
    'model.role.deleted'                            => 'Role deleted successfully',

    // Models - Permission
    'model.permission.creating'                     => 'Creating new permission',
    'model.permission.created'                      => 'Permission created successfully',
    'model.permission.updating'                     => 'Updating permission',
    'model.permission.updated'                      => 'Permission updated successfully',
    'model.permission.deleting'                     => 'Deleting permission',
    'model.permission.delete_has_relationship'      => 'Cannot delete permission - has relationships',
    'model.permission.deleted'                      => 'Permission deleted successfully',

    // Services - CacheService
    'service.cache.remember_success'                => 'Cache remember successful',
    'service.cache.remember_failed'                 => 'Cache remember failed',
    'service.cache.put_success'                     => 'Cache put successful',
    'service.cache.put_failed'                      => 'Cache put failed',
    'service.cache.hit'                             => 'Cache hit',
    'service.cache.miss'                            => 'Cache miss',
    'service.cache.get_failed'                      => 'Cache get failed',
    'service.cache.forget_success'                  => 'Cache forget successful',
    'service.cache.forget_failed'                   => 'Cache forget failed',
    'service.cache.pattern_forget_success'          => 'Cache pattern forget successful',
    'service.cache.pattern_forget_failed'           => 'Cache pattern forget failed',

    // Services - ErrorHandlingService
    'service.error.datatables_error'                => 'DataTables error: :message',

    // Services - QueryBuilderService
    'service.query.cannot_check_appended'           => 'Cannot check appended attributes for :model_class',

    // Services - PaginationService
    'service.pagination.cannot_check_appended'      => 'Cannot check appended attributes for :model_class',
    'service.pagination.sort_failed'                => 'Collection sorting failed',
    'service.pagination.paginate_failed'            => 'Collection pagination failed',

    // Services - OptionLoaderService
    'service.option.load_success'                   => 'Options loaded successfully',
    'service.option.load_failed'                    => 'Error loading options from database',
    'service.option.load_custom_failed'             => 'Error loading options with custom query',
    'service.option.preload_failed'                 => 'Error preloading options for :key',

    // Middleware
    'middleware.route_permission_denied'            => 'Access denied - no permission to access route',

    // Commands
    'command.clearall.start'                        => 'Starting to clear cache and config',
    'command.clearall.success'                      => 'Command executed successfully',
    'command.clearall.error'                        => 'Error executing command',
    'command.clearall.complete'                     => 'Completed clearing cache and config',

    // Utils - SqlHandler
    'utils.sql.transaction_failed'                  => 'Transaction failed',

    // Utils - AjaxHandle
    'utils.ajax.success'                            => 'Ajax success response',
    'utils.ajax.error'                              => 'Ajax error response: :message',
    'utils.ajax.error_simple'                       => 'Ajax error response',
    'utils.ajax.validation_error'                   => 'Ajax validation error response',
];

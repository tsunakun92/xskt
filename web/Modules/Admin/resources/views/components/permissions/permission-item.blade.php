@php
    $permissionKey = is_array($permission) ? $permission['key'] : $permission->key;
    $permissionDbName = is_array($permission) ? ($permission['name'] ?? null) : ($permission->name ?? null);
    $permissionId = is_array($permission) ? ($permission['id'] ?? null) : $permission->id;
    $permissionModule = is_array($permission) ? ($permission['module'] ?? null) : ($permission->module ?? null);
    $isChecked = false;

    if (is_array($currentPermissions)) {
        $isChecked = in_array($permissionKey, array_column($currentPermissions, 'key')) || 
                    in_array($permissionId, array_column($currentPermissions, 'id'));
    }

    $cleanPermissionId = str_replace(['.', '-', ':', ' '], '_', $permissionKey);
    $permissionName = $permissionDbName ?: get_permission_label($permissionKey);
    $actualModuleKey = $moduleKey ?? ($permissionModule ?? 'all');
    $checkboxId = "permission_{$actualModuleKey}_{$cleanPermissionId}";
@endphp

<div class="permission-card flex items-start p-3 border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-indigo-50 dark:hover:bg-indigo-900/20 hover:border-indigo-300 dark:hover:border-indigo-600 transition-all duration-200 cursor-pointer group"
    onclick="toggleCheckbox('{{ $checkboxId }}')">
    <input type="checkbox" 
        name="permissions[]" 
        value="{{ $permissionKey }}" 
        id="{{ $checkboxId }}"
        class="permission-checkbox w-4 h-4 text-indigo-600 bg-gray-100 border-gray-300 rounded focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600 mt-0.5 flex-shrink-0"
        data-module="{{ $actualModuleKey }}"
        data-group-index="{{ $index }}"
        {{ $isChecked ? 'checked' : '' }}>
    <label for="{{ $checkboxId }}" class="ml-3 text-xs cursor-pointer flex-1">
        <span class="font-medium text-gray-900 dark:text-white block leading-tight group-hover:text-indigo-700 dark:group-hover:text-indigo-300 transition-colors">
            {{ $permissionName }}
        </span>
        <span class="text-gray-500 dark:text-gray-400 text-xs leading-tight mt-1 block">
            {{ $permissionKey }}
        </span>
    </label>
</div>

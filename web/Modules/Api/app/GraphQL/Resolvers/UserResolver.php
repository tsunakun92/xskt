<?php

namespace Modules\Api\GraphQL\Resolvers;

use Exception;
use Illuminate\Support\Facades\Validator;

use App\Entities\Sanctum\PersonalAccessToken;
use App\Models\BaseModel;
use App\Utils\CommonProcess;
use App\Utils\SqlHandler;
use Modules\Admin\Models\Role;
use Modules\Admin\Models\User;
use Modules\Admin\Services\MobilePermissionService;
use Modules\Crm\Models\CrmCustomer;
use Modules\Hr\Models\HrProfile;
use Modules\Logging\Utils\LogHandler;

class UserResolver {
    public function viewUser($root, array $args) {
        try {
            // Validate input
            $validator = Validator::make($args, [
                'user_id'  => 'required|integer',
                'version'  => 'required|string|max:255',
                'platform' => PersonalAccessToken::getPlatformValidationRules(),
            ]);

            if ($validator->fails()) {
                LogHandler::warning('User view validation failed', [
                    'errors' => $validator->errors()->toArray(),
                ], LogHandler::CHANNEL_API);

                return apiResponseError('Invalid input data: ' . getValidationErrorMessage($validator));
            }

            // Retrieve user by ID
            $user = User::with(['rRole', 'rProfile'])->find($args['user_id']);

            if (!$user) {
                LogHandler::warning('User not found via API', [
                    'user_id' => $args['user_id'] ?? null,
                ], LogHandler::CHANNEL_API);

                return apiResponseError('User not found');
            }

            // Return success response with user data
            LogHandler::info('User viewed via API', ['user_id' => $user->id], LogHandler::CHANNEL_API);

            return apiResponseSuccess('User retrieved successfully', [
                'data' => $user->load(['rRole', 'rProfile']),
            ]);
        } catch (Exception $e) {
            LogHandler::error('Failed to view user via API', [
                'error'   => $e->getMessage(),
                'user_id' => $args['user_id'] ?? null,
            ], LogHandler::CHANNEL_API);

            return apiResponseError('Failed to retrieve user');
        }
    }

    public function getUserPermissions($root, array $args) {
        // Validate input
        $validator = Validator::make($args, [
            'user_id'  => 'required|integer',
            'version'  => 'required|string|max:255',
            'platform' => PersonalAccessToken::getPlatformValidationRules(),
        ]);

        if ($validator->fails()) {
            LogHandler::warning('User API validation failed', [
                'method' => 'getUserPermissions',
                'errors' => $validator->errors()->toArray(),
            ], LogHandler::CHANNEL_API);

            return apiResponseError('Invalid input data: ' . getValidationErrorMessage($validator));
        }

        // Retrieve user by ID
        $user = User::find($args['user_id']);

        if (!$user) {
            LogHandler::warning('User not found via API', [
                'user_id' => $args['user_id'] ?? null,
            ], LogHandler::CHANNEL_API);

            return apiResponseError('User not found');
        }

        /** @var MobilePermissionService $permissionService */
        $permissionService = app(MobilePermissionService::class);

        // Return success response with user data
        LogHandler::info('User permissions retrieved via API', ['user_id' => $user->id], LogHandler::CHANNEL_API);

        return apiResponseSuccess('Permissions retrieved successfully', [
            'data' => $permissionService->getMobilePermissionsGrouped($user),
        ]);
    }

    public function getUserData($root, array $args) {
        // Validate input
        $validator = Validator::make($args, [
            'version'  => 'required|string|max:255',
            'platform' => PersonalAccessToken::getPlatformValidationRules(),
        ]);

        if ($validator->fails()) {
            LogHandler::warning('User API validation failed', [
                'method' => 'getUserData',
                'errors' => $validator->errors()->toArray(),
            ], LogHandler::CHANNEL_API);

            return apiResponseError('Invalid input data: ' . getValidationErrorMessage($validator));
        }

        // Retrieve user authenticated
        $user = auth('sanctum')->user();

        if (!$user) {
            LogHandler::warning('User not authenticated via API', [
                'method' => 'getUserData',
            ], LogHandler::CHANNEL_API);

            return apiResponseError('User not found');
        }

        /** @var MobilePermissionService $permissionService */
        $permissionService = app(MobilePermissionService::class);

        // Get user details, configs, and permissions
        $userData = [
            'user'        => $user,
            'configs'     => $user->apiGetUserSettings(),
            'permissions' => $permissionService->getMobilePermissionsGrouped($user),
        ];

        // Return success response with user data
        LogHandler::info('User data retrieved via API', ['user_id' => $user->id], LogHandler::CHANNEL_API);

        return apiResponseSuccess('User data retrieved successfully', [
            'data' => $userData,
        ]);
    }

    public function createUser($root, array $args) {
        try {
            // Validate input
            $validator = Validator::make($args, [
                'username'   => 'required|string|max:255|unique:users,username',
                'email'      => 'required|string|email|max:255|unique:users,email',
                'password'   => 'required|string|min:8',
                'role_id'    => 'required|integer|exists:roles,id',
                'fullname'   => 'required|string|max:255',
                'birthday'   => 'nullable|date|date_format:Y-m-d',
                'address'    => 'nullable|string|max:255',
                'gender'     => 'nullable|integer|in:1,2',
                'company_id' => 'nullable|integer|exists:hr_companies,id',
                'version'    => 'required|string|max:255',
                'platform'   => PersonalAccessToken::getPlatformValidationRules(),
            ]);

            if ($validator->fails()) {
                LogHandler::warning('User creation validation failed', [
                    'errors' => $validator->errors()->toArray(),
                ]);

                return apiResponseError('Invalid input data: ' . getValidationErrorMessage($validator));
            }
        } catch (Exception $e) {
            // Handle validation exceptions (e.g., database table doesn't exist)
            $errorMessage = 'Invalid input data';
            if (str_contains($e->getMessage(), 'doesn\'t exist')) {
                if (str_contains($e->getMessage(), 'roles')) {
                    $errorMessage = 'Invalid input data: The selected role id is invalid.';
                } elseif (str_contains($e->getMessage(), 'hr_companies')) {
                    $errorMessage = 'Invalid input data: The selected company id is invalid.';
                }
            } elseif (isset($validator) && $validator->fails()) {
                $errorMessage = 'Invalid input data: ' . getValidationErrorMessage($validator);
            }
            LogHandler::error('User creation validation exception', [
                'error' => $e->getMessage(),
            ]);

            return apiResponseError($errorMessage);
        }

        $user    = null;
        $success = SqlHandler::handleTransaction(function () use (&$user, $args) {
            $username = CommonProcess::getValue($args, 'username');
            $fullname = CommonProcess::getValue($args, 'fullname');

            // Create user
            $user = User::create([
                'username' => $username,
                'email'    => CommonProcess::getValue($args, 'email'),
                'password' => CommonProcess::getValue($args, 'password'),
                'name'     => $fullname ?? $username,
                'role_id'  => CommonProcess::getValue($args, 'role_id'),
                'status'   => BaseModel::STATUS_ACTIVE,
            ]);

            if (!$user) {
                return false;
            }

            // Create profile if profile data is provided
            $birthday  = CommonProcess::getValue($args, 'birthday');
            $address   = CommonProcess::getValue($args, 'address');
            $gender    = CommonProcess::getValue($args, 'gender');
            $companyId = CommonProcess::getValue($args, 'company_id');

            if ($fullname || $birthday || $address || $gender !== null || $companyId !== null) {
                $profileData = [
                    'user_id' => $user->id,
                    'status'  => HrProfile::STATUS_ACTIVE,
                ];

                if ($fullname !== null) {
                    // Map legacy fullname into first_name/last_name for new HrProfile schema
                    $parts = preg_split('/\s+/u', trim($fullname));
                    if (!empty($parts)) {
                        $firstName                 = array_pop($parts);
                        $lastName                  = implode(' ', $parts);
                        $profileData['first_name'] = $firstName;
                        if ($lastName !== '') {
                            $profileData['last_name'] = $lastName;
                        }
                    }
                }
                if ($birthday !== null) {
                    $profileData['birthday'] = $birthday;
                }
                if ($address !== null) {
                    $profileData['address'] = $address;
                }
                if ($gender !== null) {
                    $profileData['gender'] = $gender;
                }
                if ($companyId !== null) {
                    $profileData['company_id'] = $companyId;
                }

                if (!isset($profileData['code'])) {
                    $profileData['code'] = CommonProcess::generateUniqId(13);
                }

                HrProfile::create($profileData);
            }

            return true;
        });

        if (!$success || !$user) {
            LogHandler::error('Failed to create user via API', [
                'args' => CommonProcess::getValue($args, 'username'),
            ], LogHandler::CHANNEL_API);

            return apiResponseError('Failed to create user');
        }

        LogHandler::info('User created via API', [
            'user_id'  => $user->id,
            'username' => $user->username,
            'email'    => $user->email,
        ], LogHandler::CHANNEL_API);

        return apiResponseSuccess('User created successfully', [
            'data' => $user,
        ]);
    }

    public function updateUser($root, array $args) {
        try {
            // Validate input
            $validator = Validator::make($args, [
                'user_id'    => 'required|integer|exists:users,id',
                'username'   => 'nullable|string|max:255|unique:users,username,' . CommonProcess::getValue($args, 'user_id'),
                'email'      => 'nullable|string|email|max:255|unique:users,email,' . CommonProcess::getValue($args, 'user_id'),
                'password'   => 'nullable|string|min:8',
                'role_id'    => 'nullable|integer|exists:roles,id',
                'fullname'   => 'nullable|string|max:255',
                'birthday'   => 'nullable|date|date_format:Y-m-d',
                'address'    => 'nullable|string|max:255',
                'gender'     => 'nullable|integer|in:1,2',
                'company_id' => 'nullable|integer|exists:hr_companies,id',
                'version'    => 'required|string|max:255',
                'platform'   => PersonalAccessToken::getPlatformValidationRules(),
            ]);

            if ($validator->fails()) {
                LogHandler::warning('User update validation failed', [
                    'user_id' => CommonProcess::getValue($args, 'user_id'),
                    'errors'  => $validator->errors()->toArray(),
                ]);

                return apiResponseError('Invalid input data: ' . getValidationErrorMessage($validator));
            }
        } catch (Exception $e) {
            // Handle validation exceptions (e.g., database table doesn't exist)
            $errorMessage = 'Invalid input data';
            if (str_contains($e->getMessage(), 'doesn\'t exist')) {
                $errorMessage = 'Invalid input data: The selected user id is invalid.';
            } elseif (isset($validator) && $validator->fails()) {
                $errorMessage = 'Invalid input data: ' . getValidationErrorMessage($validator);
            }
            LogHandler::error('User update validation exception', [
                'user_id' => CommonProcess::getValue($args, 'user_id'),
                'error'   => $e->getMessage(),
            ]);

            return apiResponseError($errorMessage);
        }

        $userId = (int) CommonProcess::getValue($args, 'user_id', 0);
        $user   = User::find($userId);

        if (!$user) {
            LogHandler::warning('User update failed - user not found', [
                'user_id' => $userId,
            ], LogHandler::CHANNEL_API);

            return apiResponseError('Failed to update user: User not found');
        }

        $success = SqlHandler::handleTransaction(function () use ($user, $args) {
            // Update user fields
            $userData = [];
            $username = CommonProcess::getValue($args, 'username', null);
            $email    = CommonProcess::getValue($args, 'email', null);
            $password = CommonProcess::getValue($args, 'password', null);
            $roleId   = CommonProcess::getValue($args, 'role_id', null);
            $fullname = CommonProcess::getValue($args, 'fullname', null);

            if ($username !== null) {
                $userData['username'] = $username;
            }
            if ($email !== null) {
                $userData['email'] = $email;
            }
            if ($password !== null) {
                $userData['password'] = $password;
            }
            if ($roleId !== null) {
                $userData['role_id'] = $roleId;
            }
            if ($fullname !== null) {
                $userData['name'] = $fullname;
            }

            if (!empty($userData)) {
                $user->update($userData);
            }

            // Update or create profile
            $profile     = $user->rProfile;
            $birthday    = CommonProcess::getValue($args, 'birthday', null);
            $address     = CommonProcess::getValue($args, 'address', null);
            $gender      = CommonProcess::getValue($args, 'gender', null);
            $companyId   = CommonProcess::getValue($args, 'company_id', null);
            $profileData = [];

            if ($fullname !== null) {
                // Map legacy fullname into first_name/last_name for new HrProfile schema
                $parts = preg_split('/\s+/u', trim($fullname));
                if (!empty($parts)) {
                    $firstName                 = array_pop($parts);
                    $lastName                  = implode(' ', $parts);
                    $profileData['first_name'] = $firstName;
                    if ($lastName !== '') {
                        $profileData['last_name'] = $lastName;
                    }
                }
            }
            if ($birthday !== null) {
                $profileData['birthday'] = $birthday;
            }
            if ($address !== null) {
                $profileData['address'] = $address;
            }
            if ($gender !== null) {
                $profileData['gender'] = $gender;
            }
            if ($companyId !== null) {
                $profileData['company_id'] = $companyId;
            }

            if (!empty($profileData)) {
                if ($profile) {
                    $profile->update($profileData);
                } else {
                    $profileData['user_id'] = $user->id;
                    $profileData['status']  = HrProfile::STATUS_ACTIVE;
                    if (!isset($profileData['code'])) {
                        $profileData['code'] = CommonProcess::generateUniqId(13);
                    }
                    HrProfile::create($profileData);
                }
            }

            return true;
        });

        if (!$success) {
            LogHandler::error('Failed to update user via API', [
                'user_id' => $userId,
            ], LogHandler::CHANNEL_API);

            return apiResponseError('Failed to update user');
        }

        LogHandler::info('User updated via API', [
            'user_id'  => $user->id,
            'username' => $user->username,
        ], LogHandler::CHANNEL_API);

        return apiResponseSuccess('User updated successfully', [
            'data' => $user->load(['rRole', 'rProfile']),
        ]);
    }

    public function deleteUser($root, array $args) {
        try {
            // Validate input
            $validator = Validator::make($args, [
                'user_id'  => 'required|integer|exists:users,id',
                'version'  => 'required|string|max:255',
                'platform' => PersonalAccessToken::getPlatformValidationRules(),
            ]);

            if ($validator->fails()) {
                LogHandler::warning('User delete validation failed', [
                    'user_id' => CommonProcess::getValue($args, 'user_id'),
                    'errors'  => $validator->errors()->toArray(),
                ]);

                return apiResponseError('Invalid input data: ' . getValidationErrorMessage($validator));
            }
        } catch (Exception $e) {
            // Handle validation exceptions (e.g., database table doesn't exist)
            $errorMessage = 'Invalid input data';
            if (str_contains($e->getMessage(), 'doesn\'t exist')) {
                $errorMessage = 'Invalid input data: The selected user id is invalid.';
            } elseif (isset($validator) && $validator->fails()) {
                $errorMessage = 'Invalid input data: ' . getValidationErrorMessage($validator);
            }
            LogHandler::error('User delete validation exception', [
                'user_id' => CommonProcess::getValue($args, 'user_id'),
                'error'   => $e->getMessage(),
            ]);

            return apiResponseError($errorMessage);
        }

        try {
            // Find the user by ID
            $user = User::find(CommonProcess::getValue($args, 'user_id'));

            if (!$user) {
                LogHandler::warning('User delete failed - user not found', [
                    'user_id' => CommonProcess::getValue($args, 'user_id'),
                ]);

                return apiResponseError('Failed to delete user: User not found');
            }

            // Delete the user
            $userId = $user->id;
            $user->delete();

            LogHandler::info('User deleted via API', ['user_id' => $userId]);

            // Return success response
            return apiResponseSuccess('User deleted successfully');
        } catch (Exception $e) {
            LogHandler::error('Failed to delete user via API', [
                'user_id' => CommonProcess::getValue($args, 'user_id'),
                'error'   => $e->getMessage(),
            ]);

            return apiResponseError('Failed to delete user');
        }
    }

    public function listUsers($root, array $args) {
        try {
            // Validate input arguments
            $validator = Validator::make($args, [
                'page'     => 'nullable|integer|min:1',
                'limit'    => 'nullable|integer|min:1',
                'filter'   => 'nullable|string|max:255',
                'sort_by'  => 'nullable|string|max:255',
                'order'    => 'nullable|string|in:asc,desc',
                'version'  => 'required|string|max:255',
                'platform' => PersonalAccessToken::getPlatformValidationRules(),
            ]);

            if ($validator->fails()) {
                LogHandler::warning('User list validation failed', [
                    'errors' => $validator->errors()->toArray(),
                ]);

                return apiResponseError('Invalid input data: ' . getValidationErrorMessage($validator));
            }

            // Set default values for pagination and sorting
            $page   = CommonProcess::getValue($args, 'page', 1);
            $limit  = CommonProcess::getValue($args, 'limit', 10);
            $filter = CommonProcess::getValue($args, 'filter', null);
            $sortBy = CommonProcess::getValue($args, 'sort_by', 'created_at');
            $order  = CommonProcess::getValue($args, 'order', 'desc');
            // Create base query for users with related data
            $query = User::with(['rRole', 'rProfile']);

            // Apply filters if any
            if ($filter) {
                $query->where(function ($q) use ($filter) {
                    $q->where('name', 'like', "%$filter%")
                        ->orWhere('username', 'like', "%$filter%")
                        ->orWhere('email', 'like', "%$filter%");
                });
            }

            // Apply sorting and pagination
            $query->orderBy($sortBy, $order);
            $total = $query->count();
            $users = $query->skip(($page - 1) * $limit)->take($limit)->get();

            // Load relations for all users
            $users->load(['rRole', 'rProfile']);

            $paginatorInfo = [
                'total'       => $total,
                'currentPage' => $page,
                'lastPage'    => ceil($total / $limit),
                'perPage'     => $limit,
            ];

            LogHandler::info('Users list retrieved via API', [
                'total' => $total,
                'page'  => $page,
            ]);

            // Return a success response with user data
            return apiResponseSuccess('Users retrieved successfully', [
                'data'          => $users,
                'paginatorInfo' => $paginatorInfo,
            ]);
        } catch (Exception $e) {
            LogHandler::error('Failed to retrieve users via API', [
                'error' => $e->getMessage(),
            ]);

            return apiResponseError('Failed to retrieve users');
        }
    }

    /**
     * Get list of roles for a user (for GraphQL field resolver)
     * Ensures the main role (users.role_id) is always included in the list
     *
     * @param  User  $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function listRoles(User $user) {
        $assignedRoles = $user->rAssignedRoles()->get();

        // Load main role if not already loaded
        $mainRole = $user->rRole;
        if (!$mainRole && $user->role_id) {
            $mainRole = $user->rRole()->first();
        }

        // Ensure main role is always included in the list
        if ($mainRole) {
            $assignedRoleIds = $assignedRoles->pluck('id')->toArray();
            if (!in_array($mainRole->id, $assignedRoleIds, true)) {
                $assignedRoles->push($mainRole);
            }
        }

        return $assignedRoles;
    }

    /**
     * Get list of sections for a user (for GraphQL field resolver)
     *
     * @param  User  $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function listSections(User $user) {
        return $user->rAssignedSections()->get();
    }

    /**
     * Get profile for a user based on their role (for GraphQL field resolver)
     * Returns CrmCustomer for customers, HrProfile for staff
     * Note: Profile GraphQL type will need field resolvers to handle CrmCustomer fields
     *
     * @param  User  $user
     * @return HrProfile|CrmCustomer|null
     */
    public function getProfile(User $user) {
        return $user->getUserProfile();
    }
}

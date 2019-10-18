<?php

declare(strict_types=1);

/*
 * This file is part of eelly package.
 *
 * (c) eelly.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shadon\Acl\Adapter;

use Phalcon\Acl;
use Phalcon\Acl\Adapter;
use Phalcon\Acl\Role;
use Phalcon\Acl\RoleInterface;
use Phalcon\Db;

class Database extends Adapter
{
    public $db;

    public $cache;

    /**
     * acl表.
     *
     * @var array
     */
    protected $tables = [
        'client'              => 'oauth_client',
        'module'              => 'oauth_module',
        'moduleService'       => 'oauth_module_service',
        'permission'          => 'oauth_permission',
        'permissionRequest'   => 'oauth_permission_request',
        'permissionReturn'    => 'oauth_permission_return',
        'role'                => 'oauth_role',
        'roleClient'          => 'oauth_role_client',
        'rolePermission'      => 'oauth_role_permission',
        'permissionParameter' => 'oauth_permission_parameter',
    ];

    /**
     * Default action for no arguments is allow.
     *
     * @var int
     */
    protected $noArgumentsDefaultAction = Acl::ALLOW;

    /**
     * Sets the default access level (Phalcon\Acl::ALLOW or Phalcon\Acl::DENY)
     * for no arguments provided in isAllowed action if there exists func for accessKey.
     *
     * @param int $defaultAccess
     */
    public function setNoArgumentsDefaultAction($defaultAccess): void
    {
        $this->noArgumentsDefaultAction = (int) $defaultAccess;
    }

    /**
     * Returns the default ACL access level for no arguments provided in
     * isAllowed action if there exists func for accessKey.
     *
     * @return int
     */
    public function getNoArgumentsDefaultAction(): int
    {
        return $this->noArgumentsDefaultAction;
    }

    /**
     * Adds a role to the ACL list. Second parameter lets to inherit access data from other existing role
     * $this->addRole(1, [1,2,3]);.
     *
     * @param \Phalcon\Acl\Role|string $role
     * @param mixed                    $accessInherits
     *
     * @return bool|number
     */
    public function addRole($role, $accessInherits = null, string $defaultPermission = '')
    {
        \is_string($role) && $role = new Role($role, ucwords($role).' Role');

        if (!$role instanceof RoleInterface) {
            throw new Exception('Role must be either an string or implement RoleInterface');
        }

        $roleId = null;
        if (!$this->checkExists($this->tables['role'], ['role_name' => $role->getName()])) {
            $roleId = $this->commonInsert($this->tables['role'], ['role_name' => $role->getName(), 'created_time' => time(), 'default_permission' => $defaultPermission]);
        }

        if (!empty($accessInherits) && \is_array($accessInherits)) {
            !isset($roleId) && $roleId = $this->getRoleId($role->getName());
            $data = [];
            foreach ($accessInherits as $permId) {
                if (!$this->checkExists($this->tables['rolePermission'], ['role_id' => $roleId, 'permission_id' => $permId])) {
                    $data[] = [
                        'role_id'       => $roleId,
                        'permission_id' => $permId,
                        'created_time'  => time(),
                    ];
                }
            }

            return $this->commonBatchInsert($this->tables['rolePermission'], $data);
        } elseif (isset($roleId)) {
            return $roleId;
        }

        return true;
    }

    /**
     * 添加模块.
     *
     * @param string $moduleName
     *
     * @return bool|number
     */
    public function addModule(string $moduleName)
    {
        if (empty($moduleName)) {
            return false;
        }

        if (!$this->checkExists($this->tables['module'], ['module_name' => $moduleName])) {
            return $this->commonInsert($this->tables['module'], [
                'module_name'  => $moduleName,
                'status'       => 1,
                'created_time' => time(),
            ]);
        }

        return true;
    }

    /**
     * 添加模块客户端.
     *
     * @param string $moduleName
     *
     * @return bool|number
     */
    public function addModuleClient(string $moduleName, int $roleId = 0)
    {
        if (empty($moduleName)) {
            return false;
        }

        $clientKey = $moduleName.'module';

        $clientId = null;
        if (!$this->checkExists($this->tables['client'], ['client_key' => $clientKey])) {
            $clientId = $this->commonInsert($this->tables['client'], [
                'client_key'    => $clientKey,
                'client_secret' => password_hash($moduleName.'abc123', PASSWORD_BCRYPT),
                'is_encrypt'    => 1,
                'org_name'      => 'eellyapi',
                'app_name'      => $moduleName,
                'auth_type'     => 4,
                'created_time'  => time(),
            ]);
        }

        if (!empty($roleId)) {
            !isset($clientId) && $clientId = $this->getClientId($clientKey);
            if (!$this->checkExists($this->tables['roleClient'], ['client_id' => $clientId, 'role_id' => $roleId])) {
                return $this->commonInsert($this->tables['roleClient'], [
                    'client_id'    => $clientId,
                    'role_id'      => $roleId,
                    'created_time' => time(),
                ]);
            }
        } elseif (isset($clientId)) {
            return $clientId;
        }

        return true;
    }

    /**
     * 添加模块服务
     *
     * @param string $serviceName
     * @param string $moduleName
     *
     * @return bool|bool|number
     */
    public function addModuleService(string $serviceName, string $moduleName)
    {
        if (empty($serviceName) || empty($moduleName) || empty($moduleId = $this->getModuleId($moduleName))) {
            return false;
        }

        if (!$this->checkExists($this->tables['moduleService'], ['service_name' => $serviceName, 'module_id' => $moduleId])) {
            return $this->commonInsert($this->tables['moduleService'], [
                'service_name' => $serviceName,
                'module_id'    => $moduleId,
                'created_time' => time(),
            ]);
        }

        return true;
    }

    /**
     * 添加接口.
     *
     * @param string $hashName
     * @param string $serviceName
     * @param array  $data
     *
     * @return bool
     */
    public function addPermission(string $hashName, string $serviceName, array $data)
    {
        if (empty($data) || empty($serviceName) || empty($serviceId = $this->getServiceId($serviceName))) {
            return false;
        }

        if ($this->checkExists($this->tables['permission'], ['hash_name' => $hashName])) {
            $permId = $this->getPermId($hashName);
            $this->commonDelete($this->tables['permissionRequest'], ['permission_id' => $permId]);
            $this->commonDelete($this->tables['permissionReturn'], ['permission_id' => $permId]);
            $this->commonDelete($this->tables['permission'], ['hash_name' => $hashName]);
        }

        return $this->commonInsert($this->tables['permission'], [
            'service_id'      => $serviceId,
            'hash_name'       => $hashName,
            'perm_name'       => $data['methodName'],
            'request_example' => $data['requestExample'],
            'remark'          => $data['methodDescribe'],
            'created_time'    => $data['created_time'],
            'is_login'        => $data['isLogin'],
        ]);
    }

    /**
     * 添加接口请求参数.
     *
     * @param array  $data
     * @param string $hashName
     *
     * @return bool
     */
    public function addPermissionRequest(array $data, string $hashName)
    {
        if (empty($data) || empty($hashName) || empty($permId = $this->getPermId($hashName))) {
            return false;
        }

        if ($this->checkExists($this->tables['permissionRequest'], ['permission_id' => $permId])) {
            $this->commonDelete($this->tables['permissionRequest'], ['permission_id' => $permId]);
        }

        foreach ($data as &$val) {
            $val['permission_id'] = $permId;
        }

        return $this->commonBatchInsert($this->tables['permissionRequest'], $data);
    }

    /**
     * 添加接口返回值
     *
     * @param string $dtoName
     * @param string $example
     * @param int    $permId
     *
     * @return bool|number
     */
    public function addPermissionReturn(array $data, string $hashName)
    {
        if (empty($data) && empty($hashName) || empty($permId = $this->getPermId($hashName))) {
            return false;
        }

        if ($this->checkExists($this->tables['permissionReturn'], ['permission_id' => $permId])) {
            $this->commonDelete($this->tables['permissionReturn'], ['permission_id' => $permId]);
        }

        foreach ($data as &$val) {
            $val['permission_id'] = $permId;
        }

        return $this->commonBatchInsert($this->tables['permissionReturn'], $data);
    }

    public function addRoleClient(string $roleName, string $clientKey)
    {
        if (empty($roleName) || empty($clientKey) || empty($roleId = $this->getRoleId($roleName)) || empty($clientId = $this->getClientId($clientKey))) {
            return false;
        }

        if (!$this->checkExists($this->tables['roleClient'], ['client_id' => $clientId, 'role_id' => $roleId])) {
            return $this->commonInsert($this->tables['roleClient'], [
                'client_id'    => $clientId,
                'role_id'      => $roleId,
                'created_time' => time(),
            ]);
        }

        return true;
    }

    /**
     * Adds a resource to the ACL list.
     *
     * Access names can be a particular action, by example
     * search, update, delete, etc or a list of them
     *
     * @param mixed $resourceObject
     * @param mixed $accessList
     *
     * @return bool
     */
    public function addResource($resourceObject, $accessList)
    {
    }

    /**
     * Adds access to resources.
     *
     * @param string $resourceName
     * @param mixed  $accessList
     */
    public function addResourceAccess($resourceName, $accessList): void
    {
    }

    /**
     * Removes an access from a resource.
     *
     * @param string $resourceName
     * @param mixed  $accessList
     */
    public function dropResourceAccess($resourceName, $accessList): void
    {
    }

    /**
     * Allow access to a role on a resource.
     *
     * @param string $roleName
     * @param string $resourceName
     * @param mixed  $access
     * @param mixed  $func
     */
    public function allow($roleName, $resourceName, $access, $func = null)
    {
        if (empty($roleName) || empty($resourceName) || empty($access)) {
            return false;
        }

        if (!$this->isAllowed($roleName, $resourceName, $access)) {
            $roleId = $this->getRoleId($roleName);
            $permId = $this->getPermId($resourceName.'\\'.$access);

            return $this->commonInsert($this->tables['rolePermission'], [
                'role_id'       => $roleId,
                'permission_id' => $permId,
                'created_time'  => time(),
            ]);
        }

        return true;
    }

    /**
     * Deny access to a role on a resource.
     *
     * @param string $roleName
     * @param string $resourceName
     * @param mixed  $access
     * @param mixed  $func
     */
    public function deny($roleName, $resourceName, $access, $func = null)
    {
        if (empty($roleName) || empty($resourceName) || empty($access)) {
            return false;
        }

        if ($this->isAllowed($roleName, $resourceName, $access)) {
            $roleId = $this->getRoleId($roleName);
            $permId = $this->getPermId($resourceName.'/'.$access);

            return $this->commonDelete($this->tables['rolePermission'], [
                'role_id'       => $roleId,
                'permission_id' => $permId,
            ]);
        }

        return true;
    }

    /**
     * Check whether a role is allowed to access an action from a resource.
     *
     * @param mixed $roleName
     * @param mixed $resourceName
     * @param mixed $access
     * @param array $parameters
     *
     * @return bool
     */
    public function isAllowed($roleName, $resourceName, $access, array $parameters = null)
    {
        if (empty($roleName) || empty($resourceName) || empty($access)) {
            return false;
        }

        $roleId = $this->getRoleId($roleName);
        $permId = $this->getPermId($resourceName.'/'.$access);

        return $this->checkExists($this->tables['rolePermission'], ['role_id' => $roleId, 'permission_id' => $permId]);
    }

    /**
     * 客户端权限校验.
     *
     * example:
     * $this->eellyAcl->isAllow('clientKey', 'user/index/cacheTime')
     *
     * @param string $clientKey
     * @param string $scope
     *
     * @return bool
     */
    public function isAllow(string $clientKey, string $scope): bool
    {
        if (empty($clientKey) || empty($scope) || empty($scopeList = explode('/', $scope)) || 3 != \count($scopeList)) {
            return false;
        }

        $userAccess = [];
        $cacheKey = 'clientPermissionInfo:'.$clientKey;
        $this->cache instanceof \Phalcon\Cache\BackendInterface && $userAccess = $this->cache->get($cacheKey);
        if (empty($userAccess)) {
            $userAccess = $this->getPermissionByClientKey($clientKey);
            $this->cache instanceof \Phalcon\Cache\BackendInterface && $this->cache->save($cacheKey, $userAccess, 3600);
        }
        // 1、默认 (*/*/*)拥有所有权限 2、(user/*/*)拥有user模块所有权限 3、(user/index/*) 拥有user模块index下的所有权限
        if (isset($userAccess['default_permission']) && !empty($userAccess['default_permission'])) {
            foreach ($userAccess['default_permission'] as $default) {
                $defaultPermission = explode('/', $default);
                if (empty($defaultPermission) || 3 !== \count($defaultPermission)) {
                    continue;
                }

                if ('*' === $defaultPermission[0]) {
                    // 拥有所有权限
                    return true;
                } elseif ($scopeList[0] === $defaultPermission[0] && '*' === $defaultPermission[1]) {
                    // 拥有模块下所有权限
                    return true;
                } elseif ($scopeList[0] === $defaultPermission[0] && $scopeList[1] === $defaultPermission[1] && '*' === $defaultPermission[2]) {
                    // 拥有模块下具体某个类的所有权限
                    return true;
                }
            }
        }

        return isset($userAccess['permission_list']) && \in_array($scope, $userAccess['permission_list'], true) ? true : false;
    }

    /**
     * 获取客户端拥有的权限.
     *
     * @param string $clientKey
     *
     * @return array
     */
    public function getPermissionByClientKey(string $clientKey): array
    {
        $userAccess = [];
        $roles = $this->getClientRole($clientKey);
        if (!empty($roles) && \is_array($roles)) {
            $defaultPermission = $this->getRoleDefaultPermission($roles);
            $condition = implode("','", array_map('addslashes', $roles));
            $sth = $this->db->query("SELECT DISTINCT p.hash_name FROM {$this->tables['permission']} p INNER JOIN {$this->tables['rolePermission']} rp ON p.permission_id = rp.permission_id WHERE rp.role_id IN ('{$condition}')");
            $result = $sth->fetchAll(Db::FETCH_ASSOC);
            $permissionList = array_column($result, 'hash_name');
            $userAccess = [
                'default_permission' => $defaultPermission,
                'permission_list'    => $permissionList,
            ];
        }

        return $userAccess;
    }

    /**
     * 获取角色的默认权限.
     *
     * @param array $roles
     *
     * @return array
     */
    public function getRoleDefaultPermission(array $roles): array
    {
        $defaultPermission = [];
        if (!empty($roles)) {
            $condition = implode("','", array_map('addslashes', $roles));
            $sth = $this->db->query("SELECT DISTINCT r.default_permission FROM {$this->tables['role']} r WHERE r.role_id IN ('{$condition}')");
            $result = $sth->fetchAll(Db::FETCH_ASSOC);
            $defaultPermission = array_column($result, 'default_permission');
        }

        return $defaultPermission;
    }

    /**
     * 获取客户端的角色.
     *
     * @param string $clientKey
     *
     * @return array
     */
    public function getClientRole(string $clientKey): array
    {
        $roles = [];
        if ($this->isClient($clientKey)) {
            $clientId = $this->getClientId($clientKey);
            $sth = $this->db->prepare("SELECT r.role_id FROM {$this->tables['role']} r INNER JOIN {$this->tables['roleClient']} rc ON r.role_id = rc.role_id
WHERE client_id = :client_id");
            $sth->execute([':client_id' => $clientId]);
            $result = $sth->fetchAll(Db::FETCH_ASSOC);
            $roles = array_column($result, 'role_id');
        }

        return $roles;
    }

    /**
     * Do a role inherit from another existing role.
     *
     * @param string $roleName
     * @param mixed  $roleToInherit
     *
     * @return bool
     */
    public function addInherit($roleName, $roleToInherit)
    {
    }

    /**
     * Check whether role exist in the roles list.
     *
     * @param string $roleName
     *
     * @return bool
     */
    public function isRole($roleName)
    {
        return $this->checkExists($this->tables['role'], ['role_name' => $roleName]);
    }

    /**
     * Check whether resource exist in the resources list.
     *
     * @param string $resourceName
     *
     * @return bool
     */
    public function isResource($resourceName)
    {
    }

    public function isClient(string $clientKey): bool
    {
        return $this->checkExists($this->tables['client'], ['client_key' => $clientKey]);
    }

    public function isModule(string $moduleName): bool
    {
        return $this->checkExists($this->tables['module'], ['module_name' => $moduleName]);
    }

    public function isService(string $serviceName): bool
    {
        return $this->checkExists($this->tables['moduleService'], ['service_name' => $serviceName]);
    }

    public function isPermission(string $hashName): bool
    {
        return $this->checkExists($this->tables['permission'], ['hash_name' => $hashName]);
    }

    /**
     * Role which the list is checking if it's allowed to certain resource/access.
     *
     * @return mixed
     */
    public function getActiveRole()
    {
    }

    /**
     * Resource which the list is checking if some role can access it.
     *
     * @return mixed
     */
    public function getActiveResource()
    {
    }

    /**
     * Active access which the list is checking if some role can access it.
     *
     * @return mixed
     */
    public function getActiveAccess()
    {
    }

    /**
     * Return an array with every role registered in the list.
     *
     * @return RoleInterface[]
     */
    public function getRoles()
    {
    }

    /**
     * Return an array with every resource registered in the list.
     *
     * @return ResourceInterface[]
     */
    public function getResources()
    {
    }

    public function getClientKeyByModuleName(string $moduleName): string
    {
        return $moduleName.'module';
    }

    /**
     * 添加接口请求子参数.
     *
     * @param array  $data
     * @param string $hashName
     *
     * @return bool
     */
    public function addPermissionRequestSubParam(array $data, string $hashName)
    {
        if (empty($data) || empty($hashName) || empty($permId = (int) $this->getPermId($hashName))) {
            return false;
        }

        $addData = [];
        foreach ($data as $parentName => $paramData) {
            $parentId = $this->getParentId($parentName, $permId);
            foreach ($paramData as $param) {
                $param['parent_id'] = $parentId;
                $param['permission_id'] = $permId;
                $addData[] = $param;
            }
        }

        return $this->commonBatchInsert($this->tables['permissionRequest'], $addData);
    }

    /**
     * 验证是否存在.
     *
     * @param string $tableName
     * @param array  $condition
     *
     * @return bool
     */
    private function checkExists(string $tableName, array $condition): bool
    {
        $conditionArr = null;
        $conditionStr = '';
        if (!empty($condition)) {
            $conditionStr = ' WHERE ';
            foreach ($condition as $key => $val) {
                $conditionStr .= $key.'= :'.$key.' AND ';
                $conditionArr[':'.$key] = $val;
            }
        }
        $conditionStr = rtrim($conditionStr, ' AND ');
        $sth = $this->db->prepare("SELECT COUNT(*) AS count FROM {$tableName}{$conditionStr}");
        $sth->execute($conditionArr);
        $exists = $sth->fetch(Db::FETCH_ASSOC);

        return 0 == $exists['count'] ? false : true;
    }

    /**
     * 通用的插入.
     *
     * @param string $tableName
     * @param array  $data
     *
     * @return bool|int
     */
    private function commonInsert(string $tableName, array $data)
    {
        if (empty($data)) {
            return false;
        }

        $fields = implode(',', array_keys($data));
        $values = implode("','", array_map('addslashes', array_values($data)));
        $sql = "INSERT INTO {$tableName}(".$fields.") VALUES('".$values."')";

        return $this->db->execute($sql) ? $this->db->lastInsertId() : false;
    }

    /**
     * 通用的批量插入.
     *
     * @param string $tableName
     * @param array  $data
     *
     * @return bool
     */
    private function commonBatchInsert(string $tableName, array $data)
    {
        if (empty($data)) {
            return false;
        }

        $fields = implode(',', array_keys($data[0]));
        $values = ' VALUES';
        foreach ($data as $val) {
            $values .= "('".implode("','", array_map('addslashes', array_values($val)))."'),";
        }
        $values = rtrim($values, ',');
        $sql = "INSERT INTO {$tableName}(".$fields."){$values}";

        return $this->db->execute($sql) ? $this->db->lastInsertId() : false;
    }

    /**
     * 通用的删除.
     *
     * @param string $tableName
     * @param array  $condition
     *
     * @return bool|unknown
     */
    private function commonDelete(string $tableName, array $condition)
    {
        if (empty($tableName) || empty($condition)) {
            return false;
        }

        $conditionArr = null;
        $conditionStr = '';

        $conditionStr = ' WHERE ';
        foreach ($condition as $key => $val) {
            $conditionStr .= $key.' = :'.$key.' AND ';
            $conditionArr[':'.$key] = $val;
        }
        $conditionStr = rtrim($conditionStr, ' AND ');
        $sth = $this->db->prepare("DELETE FROM {$tableName}{$conditionStr}");

        return $sth->execute($conditionArr);
    }

    /**
     * 获取角色id.
     *
     * @param string $roleName
     *
     * @return bool|bool|unknown
     */
    private function getRoleId(string $roleName)
    {
        if (empty($roleName) || !$this->isRole($roleName)) {
            return false;
        }

        $sth = $this->db->prepare("SELECT role_id FROM {$this->tables['role']} WHERE role_name = :role_name LIMIT 1");
        $sth->execute([':role_name' => $roleName]);
        $result = $sth->fetch(Db::FETCH_ASSOC);

        return $result['role_id'] ?? false;
    }

    /**
     * 获取客户端id.
     *
     * @param string $clientKey
     *
     * @return bool|bool|unknown
     */
    private function getClientId(string $clientKey)
    {
        if (empty($clientKey) || !$this->isClient($clientKey)) {
            return false;
        }

        $sth = $this->db->prepare("SELECT client_id FROM {$this->tables['client']} WHERE client_key = :client_key LIMIT 1");
        $sth->execute([':client_key' => $clientKey]);
        $result = $sth->fetch(Db::FETCH_ASSOC);

        return $result['client_id'] ?? false;
    }

    /**
     * 获取权限id.
     *
     * @param string $hashName
     *
     * @return bool|bool|unknown
     */
    private function getPermId(string $hashName)
    {
        if (empty($hashName) || !$this->isPermission($hashName)) {
            return false;
        }

        $sth = $this->db->prepare("SELECT permission_id FROM {$this->tables['permission']} WHERE hash_name = :hash_name LIMIT 1");
        $sth->execute([':hash_name' => $hashName]);
        $result = $sth->fetch(Db::FETCH_ASSOC);

        return $result['permission_id'] ?? false;
    }

    /**
     * 获取模块id.
     *
     * @param string $moduleName
     *
     * @return bool|bool|unknown
     */
    private function getModuleId(string $moduleName)
    {
        if (empty($moduleName) || !$this->isModule($moduleName)) {
            return false;
        }

        $sth = $this->db->prepare("SELECT module_id FROM {$this->tables['module']} WHERE module_name = :module_name LIMIT 1");
        $sth->execute([':module_name' => $moduleName]);
        $result = $sth->fetch(Db::FETCH_ASSOC);

        return $result['module_id'] ?? false;
    }

    /**
     * 获取服务id.
     *
     * @param string $serviceName
     *
     * @return bool|bool|unknown
     */
    private function getServiceId(string $serviceName)
    {
        if (empty($serviceName) || !$this->isService($serviceName)) {
            return false;
        }

        $sth = $this->db->prepare("SELECT service_id FROM {$this->tables['moduleService']} WHERE service_name = :service_name LIMIT 1");
        $sth->execute([':service_name' => $serviceName]);
        $result = $sth->fetch(Db::FETCH_ASSOC);

        return $result['service_id'] ?? false;
    }

    /**
     * 获取父参数id.
     *
     * @param string $parentName
     * @param int    $permId
     *
     * @return bool|bool|unknown
     */
    private function getParentId(string $parentName, int $permId)
    {
        if (empty($parentName) || empty($permId)) {
            return false;
        }

        $sth = $this->db->prepare("SELECT preq_id FROM {$this->tables['permissionRequest']} WHERE param_name = :param_name AND permission_id = :permId AND parent_id = 0 LIMIT 1");
        $sth->execute([
            ':param_name' => $parentName,
            ':permId'     => $permId,
        ]);
        $result = $sth->fetch(Db::FETCH_ASSOC);

        return $result['preq_id'] ?? false;
    }
}

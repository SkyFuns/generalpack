<?php
/**
 * 单数据表操作类
 * 基本的增删改查
 * 必须传递实例化的model
 * 如果要使用表单中的字段的原来值进行更新sql操作，只能通过原生sql组装进行操作
 * 示例 data['last_user_attach'] = "`user_attach`";
 *      data['num'] = "`num + 1`";
 * 其中where和permissionWhere的格式
 * 格式1: [
 *            ['field'=>'batch','condition'=>'=','value'=>'B','link'=>'or'],
 *            ['field'=>'batch','condition'=>'=','value'=>'A','link'=>'']
 *        ];
 * sql ： where ((batch = 'B'  or batch = 'A' ))
 * 格式2：[
 *            ['field'=>'batch','condition'=>'=','value'=>'B','link'=>'or'],
 *            ['field'=>'batch','condition'=>'=','value'=>'A','link'=>''],
 *            ['link'=>'and','where'=>[
 *                                     ['field'=>'owner','condition'=>'=','value'=>'a','link'=>'or'],
 *                                     ['field'=>'owner','condition'=>'=','value'=>'b','link'=>'']
 *                                 ]
 *            ]
 *        ]
 *  sql：where ((owner = 'a'  or owner = 'b' ) and (batch = 'B'  or batch = 'A' ))
 * 格式3：[
 *            ['link'=>'and','where'=>[
 *                                     ['field'=>'batch','condition'=>'=','value'=>'B','link'=>'or'],
 *                                     ['field'=>'batch','condition'=>'=','value'=>'A','link'=>''],
 *                                 ]
 *            ],
 *            ['link'=>'and','where'=>[
 *                                     ['field'=>'owner','condition'=>'=','value'=>'a','link'=>'or'],
 *                                     ['field'=>'owner','condition'=>'=','value'=>'b','link'=>'']
 *                                 ]
 *            ]
 *        ]
 *  sql:  where ((batch = 'B' or batch = 'A' ) and (owner = 'a'  or owner = 'b' ))
 */
namespace Generalpack\Pack\DB\SingleQuery;

use Illuminate\Pagination\Paginator;
use Generalpack\Pack\Client\Jwt\ObjectId;
use DB;

class SingleQuery
{

    /**
     * [$model 实例化的model类]
     * @var [object]
     */
    protected $model;
    /**
     * [$error 错误信息]
     * @var [type]
     */
    private $errorMsg = '';
    /**
     * [$code 返回码]
     * @var [type]
     */
    private $code = 200;

    public function __construct($model)
    {
        $this->model = $model;
    }

    /**
     * [getRowById 通过id获取单条数据]
     * @param  string $id
     * @param  array  $permissionWhere 权限条件
     * @param  array $fields           查询字段
     * @return mix
     */
    public function getRowById($id, $permissionWhere = [], $fields = '*')
    { 
        //$permissionWhere =[['field'=>'batch','condition'=>'=','value'=>'B','link'=>'']];
        if ($this->checkSearchCondition($permissionWhere) == false) {
            return false;
        }
        $permissionWhere = $this->makeWhere($permissionWhere);

        $result = $this->model::select($fields)->where('id', $id)
            ->where(function ($query) use ($permissionWhere) {
                return $this->setWhere($query, $permissionWhere);
            })
            ->first();
        if (empty($result)) {
            $this->setError('未查询到数据', 404);
            return false;
        }
        $result = $this->replaceNullToEmpty($result->toarray());
        $this->code = 200;
        return $result;
    }

    /**
     * [getOneField 获取某一个字段]
     * @param  [type] $where           [搜索条件]
     * @param  [type] $permissionWhere [权限条件]
     * @param  [type] $field           [单个字段]
     * @return [array]                  [字段数组]
     */
    public function getOneField($where, $permissionWhere, $field, $start = 0, $limit = 10, $orderBy = null, $order = null)
    {
        if ($this->checkSearchCondition($where) == false || $this->checkSearchCondition($permissionWhere) == false) {
            return false;
        }
        $where = $this->makeWhere($where);
        $permissionWhere = $this->makeWhere($permissionWhere);

        $result = $this->model::where(function ($query) use ($where) {
            return $this->setWhere($query, $where);
        })
            ->where(function ($query) use ($permissionWhere) {
                return $this->setWhere($query, $permissionWhere);
            })
             ->when($orderBy, function ($query) use ($orderBy, $order) {
                return $query->orderBy($orderBy, $order);
            })
            ->when($start, function ($query) use ($start) {
                return $query->offset($start);
            })
             ->when($limit, function ($query) use ($limit) {
                return $query->limit($limit);
            })
            ->pluck($field)
            ->toarray();
        $this->code = 200;
        return $result;   
    }
    /**
     * [getCount 获取总条数]
     * @param  array  $where           [搜索条件]
     * @param  array  $permissionWhere [权限条件]
     * @return integer                  [总数]
     */
    public function getCount($where = [], $permissionWhere = [], $groupBy = null)
    { 
        if ($this->checkSearchCondition($where) == false || $this->checkSearchCondition($permissionWhere) == false) {
            return false;
        }
        $where = $this->makeWhere($where);
        $permissionWhere = $this->makeWhere($permissionWhere);

        $result = $this->model::where(function ($query) use ($where) {
            return $this->setWhere($query, $where);
        })
            ->where(function ($query) use ($permissionWhere) {
                return $this->setWhere($query, $permissionWhere);
            })
            ->when($groupBy, function ($query) use ($groupBy) {
                return $query->groupBy($groupBy);
            })
            ->count();
        return $result;
    }

    /**
     * [getRows 获取多条数据]
     * @param  array   $where           [description]
     * @param  array   $permissionWhere [description]
     * @param  string  $fields          [description]
     * @param  integer $start           [description]
     * @param  integer $limit           [description]
     * @param  [type]  $orderBy         [description]
     * @param  [type]  $order           [description]
     * @param  [type]  $groupBy         [description]
     * @return [array]                   [查询的数据]
     */
    public function getRows($where = [], $permissionWhere = [], $fields = '*', $start = 0, $limit = 10, $orderBy = null, $order = null, $groupBy = null)
    {
        $order = $this->setOrder($order);
        if ($this->checkSearchCondition($where, $orderBy, $order) == false || $this->checkSearchCondition($permissionWhere) == false) {
            return false;
        }
        $where = $this->makeWhere($where);
        $permissionWhere = $this->makeWhere($permissionWhere);

        $result = $this->model::select($fields)
            ->where(function ($query) use ($where) {
                return $this->setWhere($query, $where);
            })
            ->where(function ($query) use ($permissionWhere) {
                return $this->setWhere($query, $permissionWhere);
            })
            ->when($orderBy, function ($query) use ($orderBy, $order) {
                return $query->orderBy($orderBy, $order);
            })
            ->when($groupBy, function ($query) use ($groupBy) {
                return $query->groupBy($groupBy);
            })
            ->when($start, function ($query) use ($start) {
                return $query->offset($start);
            })
             ->when($limit, function ($query) use ($limit) {
                return $query->limit($limit);
            })
            ->get()
            ->toarray();
       /* if (empty($result) && !is_numeric($result)) {
            $this->setError('', 404);
            return false;
        }*/
        $result = $this->replaceNullToEmpty($result);
        $this->code = 200;
        return $result;
    }

    /**
     * [getRowsAndPages 获取分页数据]
     * @param  array   $where           查询条件
     * @param  array   $permissionWhere 权限条件
     * @param  array   $fields          查询字段
     * @param  integer $limit           查询数量
     * @param  string  $orderBy         排序字段
     * @param  string  $order           desc|asc
     * @param  string  $groupBy         分组
     * @return mix
     */
    public function getRowsAndPages($where = [], $permissionWhere = [], $fields = '*', $limit = 10, $orderBy = null, $order = null, $groupBy = null)
    {
        //$permissionWhere =[['field'=>'batch','condition'=>'=','value'=>'A','link'=>'or'],['field'=>'batch','condition'=>'=','value'=>'B','link'=>'']];  
        $order = $this->setOrder($order);
        if ($this->checkSearchCondition($where, $orderBy, $order) == false || $this->checkSearchCondition($permissionWhere) == false) {
            return false;
        }
        $where = $this->makeWhere($where);
        $permissionWhere = $this->makeWhere($permissionWhere);

        $result = $this->model::select($fields)
            ->where(function ($query) use ($where) {
                return $this->setWhere($query, $where);
            })
            ->where(function ($query) use ($permissionWhere) {
                return $this->setWhere($query, $permissionWhere);
            })
            ->when($orderBy, function ($query) use ($orderBy, $order) {
                return $query->orderBy($orderBy, $order);
            })
            ->when($groupBy, function ($query) use ($groupBy) {
                return $query->groupBy($groupBy);
            })
            ->paginate($limit)
            ->toarray();
        if (empty($result['data'])) {
            $this->setError('未查询到数据', 404);
            return false;
        } 
        $result = $this->replaceNullToEmpty($result); 
        $this->code = 200;
        return $result;
    }

    /**
     * [updateRowById 通过id更新数据]
     * @param  string $id
     * @param  array  $data             数据
     * @param  array  $permissionWhere  权限条件
     * @return mix
     */
    public function updateRowById($id, $data, $permissionWhere = [])
    {
        if ($this->checkSearchCondition($permissionWhere) == false) {
            return false;
        }
        $permissionWhere = $this->makeWhere($permissionWhere);

        $result = $this->model::where('id', $id)
            ->where(function ($query) use ($permissionWhere) {
                return $this->setWhere($query, $permissionWhere);
            })
            ->update($data);
     
        if ($result !== 1) {
            $this->setError('修改失败', 400); 
            return false;
        }
        $this->code = 200;
        return ['id' => $id];
    }

    /**
     * [sqlUpDateRowById 通过id进行更新，原生sql组装。]
     * @param  [type] $id              [description]
     * @param  [type] $data            [description]
     * @param  array  $permissionWhere [description]
     * @return [type]                  [description]
     */
    public function sqlUpDateRowById($id, $data, $permissionWhere = [])
    { 
        if ($this->checkSearchCondition($permissionWhere) == false) {
            return false;
        }
       
        $sqlData = $this->makeSqlData($data);
        $sqlPermissionWhere = $this->makeSqlwhere($permissionWhere);
        $sqlPermissionWhere  = empty($sqlPermissionWhere) ? '' : " and " . $sqlPermissionWhere;
        $sqlIdWhere = $this->makeSqlWhere([['field' => 'id','condition' => '=','value' => $id, 'link' => '']]);
        $sqlWhere = $sqlIdWhere . $sqlPermissionWhere;
        //echo "update {$this->model->table} set {$sqlData} where {$sqlWhere}";exit;
        $result = DB::update("update {$this->model->table} set {$sqlData} where {$sqlWhere}");
        if ($result !== 1) {
            $this->setError('修改失败', 400); 
            return false;
        }
        $this->code = 200;
        return ['id' => $id];

    }
    
    /**
     * [updateRows 通过条件更新多条数据，后面三个主要用于批量操作]
     * @param  array   $data            数据
     * @param  array   $where
     * @param  array   $permissionWhere 权限条件
     * @param  string  $operation       更新方式:当前搜索search|当前选择select|全部all
     * @param  array|string   $id_list         id数组|用英文逗号分割的字符串也行，当前选择的id
     * @param  boolean $allRecord       是否全部   
     * @return mix
     */
    public function updateRows($data, $where = [], $permissionWhere = [], $start = null, $limit = null, $operation = 'search', $id_list = [], $allRecord = true)
    {       
        //处理选择全部时,完善搜索条件
        if ($allRecord === true || $allRecord == 'true') { 
            $start = '';
            $limit = '';
        } 

        //处理选择部分数据,完善搜索条件
        if($id_list) { 
            $id_list = $this->makeIdList($id_list, $limit); 
            $addWhere = ['field' => 'id', 'condition' => 'in', 'value' => $id_list, 'link' => 'and'];
            $where = [];//根据id修改。去掉其他where搜索条件
            array_unshift($where, $addWhere);  
        }
      
        if ($this->checkSearchCondition($where) == false || $this->checkSearchCondition($permissionWhere) == false) {
            return false;
        }
        if( $this->checkBatch($where, $operation) == false) { 
            return false;
        }
        $where = $this->makeWhere($where);
        $permissionWhere = $this->makeWhere($permissionWhere);

        $result = $this->model::where(function ($query) use ($where) {
                return $this->setWhere($query, $where);
            })
            ->where(function ($query) use ($permissionWhere) {
                return $this->setWhere($query, $permissionWhere);
            })
            ->when($start, function ($query) use ($start) {
                return $query->offset($start);
            })
             ->when($limit, function ($query) use ($limit) {
                return $query->limit($limit);
            })
            ->update($data);

        /*if ($result === 0) {
            $this->setError('修改失败', 400);
            return false;
        }*/
        $this->code = 200;
        return ['total' => $result];
    }

    /**
     * [sqlUpdateRows 通过条件更新多条数据，后面三个主要用于批量操作]
     * @param  array   $data            数据
     * @param  array   $where
     * @param  array   $permissionWhere 权限条件
     * @param  string  $operation       更新方式:当前搜索search|当前选择select|全部all
     * @param  array|string   $id_list         id数组|用英文逗号分割的字符串也行，当前选择的id
     * @param  boolean $allRecord       是否全部   
     * @return mix                      
     */
    public function sqlUpdateRows($data, $where = [], $permissionWhere = [], $start = null, $limit = null, $operation = 'search', $id_list = [], $allRecord = true)
    { 
        //处理选择全部时,完善搜索条件
        if ($allRecord === true || $allRecord =='true') {
            $start = '';
            $limit = '';
        } 

        //处理选择部分数据,完善搜索条件
        if($id_list) { 
            $id_list = $this->makeIdList($id_list, $limit); 
            $addWhere = ['field' => 'id', 'condition' => 'in', 'value' => $id_list, 'link' => 'and'];
            $where = [];//根据id修改。去掉其他where搜索条件
            array_unshift($where, $addWhere);  
        }
      
        if ($this->checkSearchCondition($where) == false || $this->checkSearchCondition($permissionWhere) == false) {
            return false;
        }
        if( $this->checkBatch($where, $operation) == false) { 
            return false;
        }
        $sqlData = $this->makeSqlData($data);
        $sqlWhere = '';
        $where = $this->makeSqlWhere($where);
        $sqlPermissionWhere = $this->makeSqlWhere($permissionWhere);
        if (empty($where) && empty($sqlPermissionWhere)) {//都是空
            $sqlWhere = '';
        } elseif (empty($where) || empty($sqlPermissionWhere)) { //只有一个空
            $sqlWhere = "where {$where} {$sqlPermissionWhere}";
        } elseif(!empty($where) && !empty($sqlPermissionWhere)) {//都不空
            $sqlWhere = "where {$where} and {$sqlPermissionWhere}";
        }

        $sqlLimit = '';
        if( is_numeric($limit) && $limit >= 0) {
            $sqlLimit = "limit $limit";
        }
    
        $result = DB::update("update {$this->model->table} set {$sqlData} {$sqlWhere} {$sqlLimit}");

        $this->code = 200;
        return ['total' => $result];
    }

    /**
     * [addRow 新增数据]
     * @param  array $data
     * @return mix
     */
    public function addRow($data)
    {
       /* if (!array_get($data, 'id')) {
            $id = $this->model['id'] = $this->getId();
        }*/
        $id = $this->model['id'] = array_get($data, 'id', $this->getId()); 
        foreach ($data as $k => $v) {
            $this->model[$k] = $v;
        }
        $result = $this->model->save();
        if ($result !== true) {
            $this->setError('添加失败', 400);
            return false;
        }
        $this->code = 201;
        return ['id' => $id];

    }

    /**
     * [deleteRowById 通过id删除]
     * @param  string $id
     * @param  array  $permissionWhere 权限条件
     * @return mix
     */
    public function deleteRowById($id, $permissionWhere = [])
    {
        if ($this->checkSearchCondition($permissionWhere) == false) {
            return false;
        }
        $permissionWhere = $this->makeWhere($permissionWhere);

        $result = $this->model->where('id', $id)
            ->where(function ($query) use ($permissionWhere) {
                return $this->setWhere($query, $permissionWhere);
            })
            ->delete();
        if ($result === 0) {
            $this->setError('删除失败', 400);
            return false;
        }
        $this->code  = 204;
        return ['id' => $id];
    }

    /**
     * [deleteRows 通过条件删除多条数据]
     * @param  array   $where           [description]
     * @param  array   $permissionWhere [description]
     * @param  [type]  $start           [description]
     * @param  [type]  $limit           [description]
     * @param  string  $operation       删除方式|当前搜索search，当前选择select|全部all
     * @param  array   $id_list      id数组|用英文逗号分割的字符串也行，当前选择的id
     * @param  boolean $allRecord       是否全部
     */
    public function deleteRows($where = [], $permissionWhere = [], $start = null, $limit = null, $operation = 'search', $id_list = [], $allRecord = true)
    {
         //处理选择全部时,完善搜索条件
        if ($allRecord === true || $allRecord =='true') { 
            $start = '';
            $limit = '';
        } 
        //处理选择部分数据,完善搜索条件
        if($id_list) {
            $id_list = $this->makeIdList($id_list, $limit);
            $addWhere = ['field' => 'id', 'condition' => 'in', 'value' => $id_list, 'link' => 'and'];
            $where = [];//根据id删除。去掉其他where搜索条件
            array_unshift($where, $addWhere);  
        }

        if ($this->checkSearchCondition($where) == false || $this->checkSearchCondition($permissionWhere) == false) {
            return false;
        }
        if ($this->checkBatch($where, $operation) == false) { 
            return false;
        }
        $where = $this->makeWhere($where);
        $permissionWhere = $this->makeWhere($permissionWhere);

        $result = $this->model
            ->where(function ($query) use ($where) {
                return $this->setWhere($query, $where);
            })
            ->where(function ($query) use ($permissionWhere) {
                return $this->setWhere($query, $permissionWhere);
            })
            ->when($start, function ($query) use ($start) {
                return $query->offset($start);
            })
            ->when($limit, function ($query) use ($limit) {
                return $query->limit($limit);
            })
            ->delete();
        /* if ($result === 0) {
        $this->setError('删除失败', 400);
        return false;
        }*/
        $this->code = 200;
        return  ['total' => $result];
    }

    /**
     * [next 下一条]
     * @param  [type]   $id              [description]
     * @param  [type]   $where           [description]
     * @param  [type]   $permissionWhere [description]
     * @param  string   $fields          [description]
     * @param  [type]   $orderBy         [description]
     * @param  [type]   $order           [description]
     * @param  [type]   $condition       [description]
     * @return function                  [description]
     */
    public function next($id, $where = [], $permissionWhere = [], $fields = '*', $orderBy = 'id', $order = null, $message = '没有下一条数据')
    {
        $orderBy = $orderBy ? $orderBy : 'id';
        $order = $this->setOrder($order);
        $order = $order ? $order : 'asc';

        if ($this->checkSearchCondition($where) == false || $this->checkSearchCondition($permissionWhere) == false) {
            return false;
        }
        $where = $this->makeWhere($where);
        $permissionWhere = $this->makeWhere($permissionWhere);

        $result = $this->getRowById($id, $permissionWhere, $fields);
        if ($result === false) {
            return false;
        }

        $addSearchFields = $orderBy;
 
        //相等条件
        $addEquelWhere = ['field' => $addSearchFields, 'condition' => '=', 'value' => $result[$addSearchFields], 'link' => 'and'];
        if ($order == 'desc') {
            $condition = '<=';
        } else {
            $condition = '>=';
        }
        //下一条条件
        $addWhere = ['field' => $addSearchFields, 'condition' => $condition, 'value' => $result[$addSearchFields], 'link' => 'and'];
     
        $where = $equelWhere = $where ? $where : [];
    
       /* array_unshift($equelWhere, $addEquelWhere);
        array_unshift($where, $addWhere);*/
        $equelWhere[] = ['link'=> 'and', 'where' => [$addEquelWhere]];
        $where[] = ['link'=> 'and', 'where' => [$addWhere]];
        //先查询和和排序相等的
        $equelCount =  $this->getCount($equelWhere, $permissionWhere);
        if ($equelCount < 1) {
            $this->setError('未查询到数据', 404);
            return false;
        } else {
            $limit = $equelCount + 1;
        }
        $result = $this->getRows($where, $permissionWhere, $fields, 0, $limit, $orderBy, $order);

        $count = count($result);
        $next_i = ''; //下一条的数据索引
        foreach ($result as $k => $v) {
            if ($k >= ($count-1)) {//原来数据已经是最后一条了
                break;
            }
            if ($v['id'] == $id  ) {
                $next_i = $k + 1;
                break;
            }
        } 
        if ($next_i == '' || $next_i > $count) {
            $this->setError($message, 404);
            return false;
        }
        $this->code = 200;
        return  $result[$next_i];
    }

    /**
     * [previous 上一条]
     * @param  [type] $id              [description]
     * @param  array  $where           [description]
     * @param  array  $permissionWhere [description]
     * @param  string $fields          [description]
     * @param  [type] $orderBy         [description]
     * @param  [type] $order           [description]
     * @return [type]                  [description]
     */
    public function previous($id, $where = [], $permissionWhere = [], $fields = '*', $orderBy = 'id', $order = null, $message = '没有上一条数据')
    {
        $orderBy = $orderBy ? $orderBy : 'id';
        $order = $this->setOrder($order); 
        $order = ($order == 'desc') ? 'asc' : 'desc';

        if ($this->checkSearchCondition($where) == false || $this->checkSearchCondition($permissionWhere) == false) {
            return false;
        }
        $where = $this->makeWhere($where);
        $permissionWhere = $this->makeWhere($permissionWhere);

        return $this->next($id, $where, $permissionWhere, $fields, $orderBy, $order, $message);
    }

    public function setError($error, $code)
    {
        $this->errorMsg = $error;
        $this->code = $code;
    }

    public function getErrorMsg()
    {
       // return ($key && array_key_exists($key, $this->error)) ? $this->error[$key] : $this->error;
       return $this->errorMsg;
    }

    public function getCode()
    {
        return $this->code;
    }
    
    /**
     * [makeSqlData 组装通过原生sql执行的数据sql语句]
     * @param  [array] $data [数据，(使用表原来的字段值记得加``;如last_user_attach = `user_attach`)]
     * @return [type]       [description]
     */
    public function makeSqlData($data)
    {
       
        $sqlData = '';
        if (empty($data)) {
            return '';
        }
        if (!is_array($data)) {
            $this->setError('操作的数据必须以数组传递', 400);
            return false;
        }
        foreach($data as $k => $v) {
            if(substr($v, 0, 1) == "`" && substr($v, -1) == "`") {
                $v = trim($v, "`");
                $sqlData .= "$k = $v ," ;
            } elseif ($v === null ) {
                $sqlData .= "$k = null ,";
            } else {
                $sqlData .= "$k = '" . $v ."' ,";
            }
        }
        $sqlData = trim($sqlData, ',');
        return $sqlData;
    }

    /**
     * [makeSqlwhere 组装通过原生sql执行的where sql语句]
     * @param  [type] $where [description]
     * @return [type]        [description]
     */
    public function makeSqlwhere($where)
    {   
        if (empty($where)) {
            return '';
        }
        $sqlWhere = '(';
        $where = $this->makeWhere($where);
        $link = '';
        foreach ($where as $k => $v) {
            $link = $k == 0 ? '' : $v['link'];
            $sqlWhere .= " " . $link . " ";
            $sqlWhere .= "(";
            foreach ($v['where'] as $k1 => $v1) {//对
                $count = count($v['where']) -1;
                
                $sqlWhere .= $this->setUnitWhere($v1['field'], $v1['condition'], $v1['value']);
                if($k1 < $count) {
                    $sqlWhere .= $v1['link'];
                }
            }
            $sqlWhere .= ")";    
        }
        $sqlWhere .= ')';
        return $sqlWhere;


    }

    public function setWhere($query, $whereCondition)
    {  
        if (!is_array($whereCondition)) {
            $whereCondition = [];
        }
        
        $len = count($whereCondition);
         for ($i = 0; $i < $len; $i++) {
            $whereRow = $whereCondition[$i];
            if (array_key_exists('where', $whereRow) && array_key_exists('link', $whereRow)) {//是否多层查询
                $where = $whereRow['where'];
                if ($whereRow['link'] == 'and') {
                    $query->where(function($query) use ($where) {
                        return $this->setWhere($query, $where);
                    });
                } elseif ($whereRow['link'] =='or') {
                    $query->orWhere(function($query) use ($where) {
                        return $this->setWhere($query, $where);
                    });
                }
                
            } else {
                $select_link = '';
                if ($i > 0 ) {
                    $select_link =  array_key_exists('where', $whereCondition[$i - 1])? 'and' : $whereCondition[$i - 1]['link'];
                }

                $unitWhere = $this->setUnitWhere($whereRow['field'], $whereRow['condition'], $whereRow['value']);

                if ($select_link == '' || $select_link == 'and') {
                    $query->whereRaw($unitWhere);
                } elseif ($select_link == 'or') {
                    $query->orWhereRaw($unitWhere);
                }
            }
        }

        return $query;
    }

    public function setOrder($order)
    {
        if ($order == 'descend') {
            $order = 'desc';
        }

        if ($order == 'ascend') {
            $order = 'asc';
        }

        return $order;
    }

    /**
     * [setUnitWhere 对传递的wherere单元进行组装]
     */
    public function setUnitWhere($field, $condition, $value)
    {
        /*$field = $whereRow['field'];
        $value = $whereRow['value'];
        $condition = $whereRow['condition'];*/
        switch ($condition) {
            case '=':
                $condition = '=';
                $value = "'$value'";
                break;
            case '!=':
                $condition = '<>';
                $value = "'$value'";
                break;
            case '<>':
                $condition = '<>';
                $value = "'$value'";
                break;
            case '>':
                $condition = '>';
                $value = "'$value'";
                break;
            case '<':
                $condition = '<';
                $value = "'$value'";
                break;
            case '>=':
                $condition = '>=';
                $value = "'$value'";
                break;
            case '<=':
                $condition = '<=';
                $value = "'$value'";
                break;
            case 'like':
                $condition = 'like';
                $value = "'%$value%'";
                break;
            case 'like_start':
                $condition = 'like';
                $value = "'%$value'";
                break;
            case 'like_end':
                $condition = 'like';
                $value = "'$value%'";
                break;
            case 'like_contain':
                $condition = 'like';
                $value = "'$value%'";
                break;
            case 'like_no_contain':
                $condition = 'not like';
                $value = "'%$value%'";
                break;
            case 'in':
                $condition = 'in';
                if(is_string($value)) {
                    $value = explode(",", $value);
                }
                if (is_array($value)) {
                    $value = implode("','", $value);
                    $value = "'" . $value . "'";
                }
                $value = "($value)";
                break;
            case 'is_null' :
                $condition = 'is';
                $value = "null";
                break;
            case 'is_not_null' :
                $condition = 'is not';
                $value = "null";
                break;
        }
        return " $field $condition $value ";
    }

    /**
     * [replaceNullToEmpty 把数组中的null转换成空字符串]
     * @param  [type] $array [description]
     * @return [type]        [description]
     */
    public function replaceNullToEmpty($array)
    {
        foreach ($array as $k => $v) {
            if (is_array($v)) {
                $array[$k] = $this->replaceNullToEmpty($v);
            } elseif ($v === null) {
                $array[$k] = '';
            }
        }
        return $array;
    }
    /**
     * [checkSearchCondition 验证由客户端提交的参数，避免查询语句错误]
     * @param  array  $where   [description]
     * @param  [type] $orderBy [description]
     * @param  [type] $order   [description]
     * @return [type]          [description]
     */
    public function checkSearchCondition($where = [], $orderBy = null, $order = null)
    { 
        $whereArr = [ //验证键,值是否在正确。对应键的值为空数组不验证值。
            'field' => [],
            'condition' => ['=', '<', '>', '<=', '>=', '<>', '!=', 'like', 'like_start', 'like_end', 'like_contain', 'like_no_contain', 'in', 'is_null', 'is_not_null'],
            'value' => [],
            'link' => ['', 'or', 'and'],
        ];
        $orderArr = ['', 'asc', 'desc', 'ascend', 'descend'];
        if (!empty($where)) {
             foreach ($where as  $row) {
                if (is_array($row) && array_key_exists('where', $row) && array_key_exists('link', $row)) {
                    return $this->checkSearchCondition($row['where'], $orderBy, $order);
                }
                //验证传递的where的4个key是否存在。
                foreach ($whereArr as $k1 => $v1) {
                    if (!array_key_exists($k1, $row)) {
                        $this->setError("搜索条件中未包含{$k1}键", 400);
                        return false;
                        
                    }
                    //验证传递的where值是否是正确的
                    if (!empty($v1)) {
                        if(!in_array($row[$k1], $v1)) {
                            $this->setError("搜索条件中{$k1}的值{$row[$k1]}错误", 400); 
                            return false;
                        }
                    }
                }
            }
        }
       
        //验证order的值是否是正确的
        if (!in_array($order, $orderArr)) {
            $this->setError("传递的order值{$order}错误", 400);
            return false;
        }
        return true;
    }

    /**
     * [checkBatch 核实批量操作传递的方式]
     * @param  [array] $where     [搜索条件]
     * @param  [string] $operation [操作方式]
     * @return boolean
     */
    public function checkBatch($where, $operation)
    {
        $operations = ['select', 'search', 'all'];
        if (!in_array($operation, $operations)) {
            $this->setError('传递了错误的操作方式'.$operation, 400);
            return false;
        }
        if ($operation == 'select') {
            $exist_condition_in = false;//判断是否存在in的搜索
            foreach ($where as $v) {
                if ($v['condition'] == 'in') {
                    $exist_condition_in = true;
                    break;
                }
            }
            if ($exist_condition_in == false) {
                $this->setError('未传递当前选择操作的数据', 400);
                return false;
            }
        }
        if ($operation == 'search') {
            if (empty($where)) {
                $this->setError('未传递搜索条件', 400);
                return false;
            } 
        }
        if ($operation == 'all') {
            if (!empty($where)) {
                $this->setError('传递了搜索条件，不能操作所有数据', 400);
                return false;
            }    
        }

        return true;
    }

    //把where条件组合成能够被setwhere()函数使用的形式
    public function makeWhere($where)
    {
        $where = $where ? $where : [] ;
        $setWhereArray = [];//按照层次组合条件数组（二维数组）
        $setWhereArrayOne = []; //一维数组
        foreach($where as $w) {

            if (array_key_exists('where', $w)) {
                $setWhereArray[] = $w;
            } else {
                  $setWhereArrayOne[] = $w;
            }
        }
        if (!empty($setWhereArrayOne)) {
            array_unshift($setWhereArray, ['link' => 'and','where' => $setWhereArrayOne]);
        }
        return $setWhereArray;
    }

    /**
     * [makeIdList 对批量修改选择id进行处理]
     * @param  [array] $idList [id数组]
     * @param  [type] $limit  [限制条数]
     * @return [type]         [需要修改的id数组]
     */
    public function makeIdList($idList, $limit)
    { 
        $needIdList = [];
        if (empty($idList)) {
            return $needIdList;
        }
        if (!is_array($idList)) {
           $idList = explode(',', $idList);
        }
        $count = count($idList);

        $i = 0;
        if($limit == '') {//选择全部时，限制条数就是所有
            $limit = $count;
        }

        while ($i < $limit && $i<$count) {
            $needIdList[] = $idList[$i];
            $i++;
        }
        return $needIdList;
    }

    public function getId()
    {
        $id = ObjectId::generateParticle();
        return $id;
    }

}

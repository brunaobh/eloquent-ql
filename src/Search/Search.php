<?php

namespace brunaobh\Search\Search;

use Illuminate\Http\Request;
use Schema;

class Search {

    public $fields = [];
    public $filters = [];
    public $namingConvention = 'lowercase';
    public $orderBy = '';
    public $relations = [];
    public $relationsFilters = [];
    public $sort = 'ASC';
    public $limit = null;

    /**
     * Constructor
     */
    public function __construct()
    {
    }

    /**
     * Negotiate method
     * @param  String $model Eloquent model name
     * @return Object        Eloquent model
     */
    public function negotiate($model)
    {
        $modelPrefix = '\App\\';
        $modelNameSpace = $modelPrefix.$model;
        $this->model = new $modelNameSpace;
        $this->modelObj = $this->model;
        $this->table = $this->model->getTable();

        $this->negotiateFields($this->table, $this->fields)
            ->negotiateRelations($this->relations)
            ->negotiateFilters($this->table, $this->filters)
            ->negotiateRelationsFilters($this->relationsFilters)
            ->negotiateOrder($this->table, $this->orderBy, $this->sort)
            ->negotiateLimit($this->limit);

        return $this->model;
    }

    /**
     * Handle request
     * @param  Request Object $request Request http_parse_params(param)
     */
    public function handleRequest(Request $request)
    {
        $this->request = $request;

        if ($this->request->exists('fields')) {
            $this->parseFields($this->request['fields']);
        }

        if ($this->request->exists('filters')) {
            $this->parseFilters($this->request['filters']);
        }

        if ($this->request->exists('orderBy')) {
            $this->orderBy = $this->request['orderBy'];
        }

        if ($this->request->exists('sort')) {
            $this->sort = $this->request['sort'];
        }

        if ($this->request->exists('limit')) {
            $this->limit = $this->request['limit'];
        }
        return $this;
    }

    /**
     * Get fields
     * @return Array Fields
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Get filters
     * @return Array filters
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * Get relations
     * @return Array relations
     */
    public function getRelations()
    {
        return $this->relations;
    }

    /**
     * Get relationsFilters
     * @return Array relationsFilters
     */
    public function getRelationsFilters()
    {
        return $this->relationsFilters;
    }

    /**
     * Negotiate query limit
     * @param  Integer $limit To specify the number of records to return.
     */
    private function negotiateLimit($limit)
    {
        if (!is_null($limit)) {
            $this->model->limit($limit);
        }
        return $this;
    }

    /**
     * Negotiate order
     * @param  String $table Table name
     * @param  String $order Column name
     * @param  String $sort  Sorting
     */
    private function negotiateOrder($table, $order = '', $sort = 'ASC')
    {
        if (!empty($order) && Schema::hasColumn($table, $order)) {
            $this->model->orderBy($order, $sort?:'ASC');
        }
        return $this;
    }

    /**
     * Negotiate eloquent relationships
     * @param  Array $fields Fields of model relations
     */
    private function negotiateRelations($fields)
    {
        $this->model->with($this->parseRelations($fields));
        return $this;
    }

    /**
     * Negotiate query relation filters/conditions
     * @param  Array $filters Query filters/conditions
     */
    private function negotiateRelationsFilters($filters)
    {
        foreach ($filters as $key => $value) {
            $this->model->whereHas($key, function ($query) use ($value) {
                if (strpos($value, '%') !== false) {
                    $temp = explode('%', $value);
                    $query->where($this->setAttribute($temp[0]), 'like', '%'.$temp[1].'%');
                } else {
                    $temp = explode('=', $value);
                    $query->where($this->setAttribute($temp[0]), $temp[1]);
                }
            });
        }
        return $this;
    }

    /**
     * Negotiate fields
     * @param  String $table  Table name
     * @param  Array $fields Array of model fields
     */
    private function negotiateFields($table, $fields)
    {
        if (count($fields) === 0) {
            return $this;
        } else {

            $field = head($fields);
            $fieldSearch = str_replace($table.'.', '', $field);
            if (empty($field)) {
                return $this;
            }

            $fields = array_splice($fields, 1);
            if (Schema::hasColumn($table, $fieldSearch)) {
                $this->model = $this->model->addSelect($this->setAttribute($field));
            } elseif (method_exists($this->modelObj, 'scope'.ucfirst(camel_case($field)))) {
                $scope = camel_case($field);
                $this->model = $this->model->$scope();
            }

            // continue the recursion
            return $this->negotiateFields($table, $fields);

        }
    }

    /**
     * Negotiate filters
     * @param  String $table   Table name
     * @param  Array $filters Filters/Conditions
     */
    private function negotiateFilters($table, $filters)
    {
        if (count($filters) === 0) {
            return $this;
        } else {

            $filter = head($filters);

            if (empty($filter)) {
                return $this;
            }

            $filters = array_splice($filters, 1);
            $condition = $this->str_array_pos($filter, ['!=', '>=', '<=', '=', '>', '<', '%']);
            
            if (Schema::hasColumn($table, $condition['attribute'])) {
                if (strtolower(substr($condition['attribute'], -strlen('_date'))) === '_date') {
                    $this->model = $this->model->whereRaw(
                        'DATE_FORMAT('.$this->setAttribute($condition['attribute']).', "%d/%m/%Y %H:%i") LIKE "%'.$condition['value'].'%"'
                    );
                } elseif (strpos($condition['operator'], '%') !== false) {
                    $this->model = $this->model->where(
                        $this->setAttribute($condition['attribute']),
                        'like',
                        '%'.$condition['value'].'%'
                    );
                } else {
                    $this->model = $this->model->where(
                        $this->setAttribute($condition['attribute']),
                        $condition['operator'],
                        $condition['value']
                    );
                }
            } elseif (method_exists($this->modelObj, 'scope'.ucfirst(camel_case($filter)))) {
                $scope = camel_case($filter);
                $this->model = $this->model->$scope();
            }

            // continue the recursion
            return $this->negotiateFilters($table, $filters);

        }
    }

    /**
     * Parse fields
     * @param  String $fields Fields passed on URL
     */
    private function parseFields($fields)
    {

        // (do the required processing...)
        $temp = explode(',' , $fields, 2);

        if (count($temp) === 0) {
            // end the recursion
            return;
        } else {
            if (str_contains($fields, '(') && (strpos($fields, ',') > strpos($fields, '(') || strpos($fields, ',') === false)) {
                $start = strpos($fields, '(');
                $end = strpos($fields, ')');
                $_relation = substr($fields, 0, $start);
                $_fields = substr($fields, $start+1, $end-$start-1);
                $this->relations[$_relation] = $_fields;
                // continue the recursion
                return $this->parseFields(substr($fields, $end+2).',');
            } else if (isset($temp[1])) {
                if (!empty($temp[0])) {
                    $this->fields[] = $temp[0];
                }
                // continue the recursion
                return $this->parseFields($temp[1]);
            } else {
                if (!empty($temp[0])) {
                    $this->fields[] = $temp[0];
                }
                // end the recursion
                return;
            }
        }
    }

    /**
     * Parse filters
     * @param  String $fields Filters passed on URL
     */
    private  function parseFilters($filters)
    {

        // (do the required processing...)
        $temp = explode(',' , $filters, 2);

        if (count($temp) === 0) {
            // end the recursion
            return;
        } else {
            if (str_contains($filters, '(') && (strpos($filters, ',') > strpos($filters, '(') || strpos($filters, ',') === false)) {
                $start = strpos($filters, '(');
                $end = strpos($filters, ')');
                $_relation = substr($filters, 0, $start);
                $_filters = substr($filters, $start+1, $end-$start-1);
                $this->relationsFilters[$_relation] = $_filters;
                // continue the recursion
                return $this->parseFilters(substr($filters, $end+2).',');
            } else if (isset($temp[1])) {
                if (!empty($temp[0])) {
                    $this->filters[] = $temp[0];
                }
                // continue the recursion
                return $this->parseFilters($temp[1]);
            } else {
                if (!empty($temp[0])) {
                    $this->filters[] = $temp[0];
                }
                // end the recursion
                return;
            }
        }
    }

    /**
     * Parse relations
     * @param  Array $fields    Array of fields by relation
     * @param  Array  $relations Relations
     */
    private function parseRelations($fields, $relations = [])
    {
        foreach ($fields as $key => $value) {
            $relations[] = $key.(empty($value)?'':':'.$this->setAttribute($value));
        }
        return $relations;
    }

    /**
     * Set attribute/column name
     * @param String $val Attribute name
     */
    private function setAttribute($val)
    {
        switch ($this->namingConvention) {
            case 'lowercase':
                return strtolower($val);
                break;

            case 'uppercase':
                return strtoupper($val);
                break;

            default:
                return $val;
                break;
        }
    }

    /**
     * Set naming convention
     */
    public function setUpperCaseConvention()
    {
        $this->namingConvention = 'uppercase';
        return $this;
    }

    /**
     * Method to return query condition
     * @param  String $string Condition
     * @param  Array $array  Allowed operators
     */
    public function str_array_pos($string, $array)
    {
        for ($i = 0, $n = count($array); $i < $n; $i++) {
            if (($pos = strpos($string, $array[$i])) !== false) {
                $temp = explode($array[$i], $string);
                return [
                    'attribute' => $temp[0],
                    'operator' => $array[$i],
                    'value' => $temp[1]
                ];
            }
        }
        return false;
    }
}

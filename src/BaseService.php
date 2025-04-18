<?php

namespace LaravelAux;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Builder;

abstract class BaseService
{
    /**
     * @var BaseRepository
     */
    protected $repository;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Builder|array
     */
    protected $result;

    /**
     * @var array
     */
    protected $return;

    /**
     * @var array
     */
    protected $filtersOrder = [
        'query',
        'whereNull',
        'whereNotNull',
        'with',
        'withNotEmpty',
        'withEmpty',
        'hasRelationChildren',
        'orderBy',
        'orderByAsc',
        'orderByDesc',
        'paginated',
        'groupBy',
        'whereInColumn',
        'encrypted'
    ];

    /**
     * Filter constructor.
     *
     * @param BaseRepository $repository
     */
    protected function __construct(BaseRepository $repository)
    {
        $this->repository = $repository;
        $this->filtersOrder = array_merge($this->repository->getGuarded(), $this->filtersOrder);
        $this->filtersOrder = array_merge($this->repository->getFillable(), $this->filtersOrder);
    }

    /**
     * Method to get all Model Objects
     *
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function all()
    {
        return $this->repository->all();
    }

    /**
     * Method to get all records based in Request Information
     *
     * @param $columns
     * @param Request $request
     * @param string $format
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function get($columns, Request $request, $format = 'array')
    {
        $this->request = $request;
        $this->result = $this->repository->select($columns);

        if (empty($this->request->get('paginated'))) {
            $this->request->merge(['paginated' => true]);
        }

        if (empty($this->request->get('limit'))) {
            $this->request->merge(['limit' => 15]);
        }
        $this->filtersOrder[] = 'created_at';
        foreach ($this->filtersOrder as $key => $value) {
            $filter = $this->request->get($value);

            if (method_exists($this, $value) && !empty($filter)) {
                $this->$value($filter);
            } else {
                if ($this->columnExists($value) && array_key_exists($value, $this->request->all())) {

                    $type = Schema::getColumnType($this->repository->getTable(), $value);

                    if (($type == 'datetime' || $type == 'date') && strpos($filter, ',') !== false) {
                        $this->whereBetweenDate($value, $filter);
                    } else {
                        if(is_array($filter)){
                            $this->result->whereIn($value, $filter);
                        } else {
                            $this->where($value, $filter);
                        }
                    }
                }
            }
        }

        $array = ($format === 'array') ? $this->result->toArray() : $this->result;
        $results = (isset($array['data'])) ? $array['data'] : $array;

        $this->return['data'] = (!empty($results['data'])) ? $results['data'] : $results;
        $this->return['count']  = $array['total'] ?? $this->result->count();
        $this->return['filter'] = $this->result->count();

        return $this->return;
    }


    /**
    * Method to get Model Objects by passed Condition
    *
    * @param $key
    * @param $value
    */
    private function whereBetweenDate($key, $value): void
    {
        $value = explode(',', $value);
        $this->result = $this->result->where($key, '>=', $value[0])->where($key, '<=', $value[1]);
    }


    /**
     * Method to find Model Object
     *
     * @param $id
     * @return mixed
     */
    public function find($id)
    {
        return $this->repository->find($id);
    }

    /**
     * Method to get Model Object information
     *
     * @param int $id
     * @return mixed
     */
    public function show($id)
    {
        return $this->repository->find($id);
    }

    /**
     * Method to get Model Object by passed relation of Key => Value
     *
     * @param array $data
     * @return \Illuminate\Database\Eloquent\Collection|static|static[]
     */
    public function findBy(array $data)
    {
        return $this->repository->findBy($data);
    }

    /**
     * Method to create Model Object
     *
     * @param array $data
     * @return array
     */
    public function create(array $data)
    {
        return $this->repository->create($data);
    }

    /**
     * Method to update Model Object
     *
     * @param array $data
     * @param int $id
     * @return bool
     */
    public function update(array $data, int $id)
    {
        $elem = $this->repository->find($id);
        if ($elem) {
            return $elem->update($data);
        }
        return false;
    }

    /**
     * Method to remove Model Object
     *
     * @param $id
     * @return array
     * @throws \Exception
     */
    public function delete($id)
    {
        return $this->repository->delete($id);
    }

    /**
     * Method to paginate rows
     *
     * @param $value
     */
    private function paginated($value): void
    {
        $value = json_decode($value);
        if ($value) {
            $this->result = $this->result->paginate($this->request->get('limit'));

            $this->return['page'] = $this->result->currentPage() - 1;
            $this->return['pages'] = $this->result->lastPage();
        } else {
            $this->result = $this->result->get();
        }
    }

    /**
     * Method to get rows with some associated Table Information
     *
     * @param $value
     * @throws \ReflectionException
     */
    private function with($value): void
    {
        $this->result = $this->repository->withRelationIfExists($this->result, $value);
    }

    /**
     * Method to get rows with some associated Table Information
     *
     * @param $value
     * @throws \ReflectionException
     */
    private function withNotEmpty($value): void
    {
        $this->result = $this->repository->withRelationIfNotEmpty($this->result, $value);
    }

    /**
     * Method to get rows who doesn't have relation (It's empty)
     *
     * @param $value
     * @throws \ReflectionException
     */
    private function withEmpty($value): void
    {
        $this->result = $this->repository->withRelationEmpty($this->result, $value);
    }

    public function orderBy($value)
    {
        if($this->request->get('ascending')){
            $this->result = $this->result->orderBy($value);
            return;
        }

        $this->result = $this->result->orderByDesc($value);
    }

    /**
     * Method to Order Rows by passed Columns
     *
     * @param array|string $value
     */
    private function orderByAsc($value): void
    {
        if (is_array($value)) {
            foreach ($value as $filter) {
                $this->result = $this->result->orderBy($filter);
            }
            return;
        }
        $this->result = $this->result->orderBy($value);
    }

    /**
     * Method to Order Rows by passed Columns
     *
     * @param array|string $value
     */
    private function orderByDesc($value): void
    {
        if (is_array($value)) {
            foreach ($value as $filter) {
                $this->result = $this->result->orderByDesc($filter);
            }
            return;
        }
        $this->result = $this->result->orderByDesc($value);
    }

    /**
     * Method to check if Column Exists in Eloquent Model
     *
     * @param $value
     * @return mixed
     */
    private function columnExists($value): bool
    {
        return Schema::hasColumn($this->repository->getTable(), $value);
    }

    /**
     * Method to get Model Objects by passed Condition
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    private function where(string $key, mixed $value): void
    {
        // Validar se a chave é uma coluna válida
        if (!$this->isValidColumn($key)) {
            return;
        }

        if (strpos($value, ',')) {
            $value = explode(',', $value);
        }

        $this->result = $this->result->where(function (Builder $query) use ($key, $value) {
            if ($encryptedProperties = $this->request->get('encrypted')) {
                foreach ($encryptedProperties as $property) {
                    if ($property === $key) {
                        $query->whereEncrypted($key, 'like', '%' . $this->sanitizeValue($value) . '%');
                        return;
                    }
                }
            }

            if (is_array($value)) {
                $sanitizedValues = array_map([$this, 'sanitizeValue'], $value);
                $query->whereIn($key, $sanitizedValues);
                return;
            }

            if (is_numeric($value)) {
                $query->where($key, (float) $value);
            } else {
                $query->where($key, 'like', '%' . $this->sanitizeValue($value) . '%');
            }
        });
    }

    /**
     * Method to get Model Objects by passed Condition
     *
     * @param mixed $value
     * @return void
     */
    private function query(mixed $value): void
    {
        $columns = $this->repository->getFillable();
        $sanitizedValue = $this->sanitizeValue($value);

        foreach ($columns as $column) {
            if (!$this->isValidColumn($column)) {
                continue;
            }

            $type = Schema::getColumnType($this->repository->getTable(), $column);
            if (!in_array($type, ['integer', 'boolean', 'decimal'])) {
                $this->result = $this->result->orWhere($column, 'like', '%' . $sanitizedValue . '%');
            } else {
                if (is_numeric($value) || is_bool($value)) {
                    $this->result = $this->result->orWhere($column, $value);
                }
            }
        }
    }

    /**
     * Method to get Model Objects by passed Condition
     *
     * @param $key
     */
    private function whereNull($key)
    {
        $this->result = $this->result->whereNull($key);
    }

    /**
     * Method to get Model Objects by passed Condition
     *
     * @param $key
     */
    private function whereNotNull($key)
    {
        $this->result = $this->result->whereNotNull($key);
    }

    /**
     * Verify if relationship has children (at least one)
     *
     * @param $key
     */
    private function hasRelationChildren($key)
    {
        $this->result = $this->result->has($key);
    }

    /**
     * Method to Group Model Objects by passed column
     *
     * @param $column
     */
    private function groupBy($column): void
    {
        $this->result = $this->result->groupBy($column);
    }

    /**
     * Method to Group Model Objects by passed column
     *
     * @param string $value
     * @return void
     */
    private function whereInColumn(string $value): void
    {
        // Validar formato do input
        if (!preg_match('/^[a-zA-Z0-9_]+\[[^\]]+\]$/', $value)) {
            return;
        }

        $string = explode('[', $value);
        $column = $string[0];
        
        // Validar se a coluna é válida
        if (!$this->isValidColumn($column)) {
            return;
        }

        $value = substr($string[1], 0, -1);
        $values = explode(',', $value);
        
        // Sanitizar valores
        $sanitizedValues = array_map([$this, 'sanitizeValue'], $values);
        
        $this->result = $this->result->whereIn($column, $sanitizedValues);
    }

    /**
     * Check if column exists and is valid
     *
     * @param string $column
     * @return bool
     */
    private function isValidColumn(string $column): bool
    {
        return Schema::hasColumn($this->repository->getTable(), $column) &&
               (in_array($column, $this->repository->getFillable()) || 
                in_array($column, $this->repository->getGuarded()));
    }

    /**
     * Sanitize value to prevent SQL injection
     *
     * @param mixed $value
     * @return string
     */
    private function sanitizeValue(mixed $value): string
    {
        if (is_string($value)) {
            // Remove caracteres especiais que podem ser usados para SQL injection
            return preg_replace('/[^a-zA-Z0-9\s\-_.,]/', '', $value);
        }
        return (string) $value;
    }
}

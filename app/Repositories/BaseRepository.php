<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class BaseRepository
{
    protected Model $model;
    protected const DEFAULT_PER_PAGE = 20;
    protected const MAX_PER_PAGE = 100;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * 모든 레코드 조회
     */
    public function all(): Collection
    {
        return $this->model->all();
    }

    /**
     * ID로 레코드 조회
     */
    public function find(int $id)
    {
        return $this->model->find($id);
    }

    /**
     * 조건으로 레코드 조회
     */
    public function findBy(array $criteria)
    {
        return $this->buildQuery($criteria)->first();
    }

    /**
     * 조건으로 레코드 목록 조회
     */
    public function findAllBy(array $criteria): Collection
    {
        return $this->buildQuery($criteria)->get();
    }

    /**
     * 레코드 생성
     */
    public function create(array $data)
    {
        return $this->model->create($data);
    }

    /**
     * 레코드 수정
     */
    public function update(Model $model, array $data)
    {
        $model->update($data);
        return $model->fresh();
    }

    /**
     * 레코드 삭제
     */
    public function delete(Model $model)
    {
        return $model->delete();
    }

    /**
     * 페이지네이션된 결과 조회
     */
    public function paginate(array $criteria = [], int $perPage = null): LengthAwarePaginator
    {
        $perPage = min($perPage ?? self::DEFAULT_PER_PAGE, self::MAX_PER_PAGE);
        
        return $this->buildQuery($criteria)->paginate($perPage);
    }

    /**
     * 레코드 존재 여부 확인
     */
    public function exists(array $criteria): bool
    {
        return $this->buildQuery($criteria)->exists();
    }

    /**
     * 레코드 개수 조회
     */
    public function count(array $criteria = []): int
    {
        return $this->buildQuery($criteria)->count();
    }

    /**
     * 대량 삽입
     */
    public function insert(array $data): bool
    {
        return $this->model->insert($data);
    }

    /**
     * 대량 업데이트
     */
    public function bulkUpdate(array $ids, array $data): bool
    {
        return $this->model->whereIn('id', $ids)->update($data) > 0;
    }

    /**
     * 쿼리 빌더 생성
     */
    protected function buildQuery(array $criteria): Builder
    {
        $query = $this->model->newQuery();
        
        foreach ($criteria as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }
        
        return $query;
    }

    /**
     * 새로운 쿼리 빌더 인스턴스 반환
     */
    protected function newQuery(): Builder
    {
        return $this->model->newQuery();
    }

    /**
     * 모델 인스턴스 반환
     */
    protected function getModel(): Model
    {
        return $this->model;
    }
}

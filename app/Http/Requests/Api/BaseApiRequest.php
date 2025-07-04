<?php

namespace App\Http\Requests\Api;

use App\Http\Helpers\ApiResponseHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;

class BaseApiRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'required' => ':attribute는 필수 항목입니다.',
            'string' => ':attribute는 문자열이어야 합니다.',
            'integer' => ':attribute는 정수여야 합니다.',
            'numeric' => ':attribute는 숫자여야 합니다.',
            'boolean' => ':attribute는 참/거짓 값이어야 합니다.',
            'email' => ':attribute는 유효한 이메일 주소여야 합니다.',
            'min' => ':attribute는 최소 :min자 이상이어야 합니다.',
            'max' => ':attribute는 최대 :max자 이하여야 합니다.',
            'unique' => ':attribute는 이미 사용 중입니다.',
            'exists' => '선택한 :attribute가 유효하지 않습니다.',
            'confirmed' => ':attribute 확인이 일치하지 않습니다.',
            'in' => '선택한 :attribute가 유효하지 않습니다.',
            'array' => ':attribute는 배열이어야 합니다.',
            'file' => ':attribute는 파일이어야 합니다.',
            'image' => ':attribute는 이미지 파일이어야 합니다.',
            'mimes' => ':attribute는 :values 형식의 파일이어야 합니다.',
            'mimetypes' => ':attribute는 :values 형식의 파일이어야 합니다.',
            'max_file_size' => ':attribute는 최대 :max KB 이하여야 합니다.',
            'date' => ':attribute는 유효한 날짜여야 합니다.',
            'date_format' => ':attribute는 :format 형식이어야 합니다.',
            'before' => ':attribute는 :date 이전 날짜여야 합니다.',
            'after' => ':attribute는 :date 이후 날짜여야 합니다.',
            'regex' => ':attribute 형식이 올바르지 않습니다.',
            'alpha' => ':attribute는 문자만 포함할 수 있습니다.',
            'alpha_dash' => ':attribute는 문자, 숫자, 대시, 언더스코어만 포함할 수 있습니다.',
            'alpha_num' => ':attribute는 문자와 숫자만 포함할 수 있습니다.',
            'digits' => ':attribute는 :digits자리 숫자여야 합니다.',
            'digits_between' => ':attribute는 :min자리에서 :max자리 사이의 숫자여야 합니다.',
            'url' => ':attribute는 유효한 URL이어야 합니다.',
            'uuid' => ':attribute는 유효한 UUID여야 합니다.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => '이름',
            'email' => '이메일',
            'password' => '비밀번호',
            'password_confirmation' => '비밀번호 확인',
            'phone' => '전화번호',
            'title' => '제목',
            'content' => '내용',
            'description' => '설명',
            'category_id' => '카테고리',
            'department_id' => '부서',
            'status' => '상태',
            'priority' => '우선순위',
            'assigned_to' => '담당자',
            'grade' => '학년',
            'class_number' => '반',
            'subject' => '과목',
            'department' => '부서',
            'role' => '역할',
            'is_active' => '활성 상태',
            'file' => '파일',
            'image' => '이미지',
            'attachment' => '첨부파일',
            'per_page' => '페이지당 항목 수',
            'page' => '페이지 번호',
            'sort_by' => '정렬 기준',
            'sort_order' => '정렬 순서',
            'search' => '검색어',
            'start_date' => '시작 날짜',
            'end_date' => '종료 날짜',
            'relationship_type' => '관계 유형',
            'is_primary' => '주 보호자 여부',
            'comment' => '댓글',
            'is_internal' => '내부 댓글 여부',
            'is_public' => '공개 여부',
            'tags' => '태그',
            'metadata' => '메타데이터',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            ApiResponseHelper::validationError(
                $validator->errors()->toArray(),
                '입력값 검증에 실패했습니다.'
            )
        );
    }

    /**
     * Get validated data with only specified fields.
     */
    public function only(array $fields): array
    {
        return array_intersect_key($this->validated(), array_flip($fields));
    }

    /**
     * Get validated data except specified fields.
     */
    public function except(array $fields): array
    {
        return array_diff_key($this->validated(), array_flip($fields));
    }

    /**
     * Get sanitized input data.
     */
    public function sanitized(): array
    {
        $data = $this->validated();

        // 기본 데이터 정리
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = trim($value);
            }
        }

        return $data;
    }

    /**
     * Check if request has file uploads.
     */
    public function hasFiles(): bool
    {
        return $this->hasFile('file') || $this->hasFile('files') || $this->hasFile('attachment');
    }

    /**
     * Get file validation rules.
     */
    protected function getFileValidationRules(): array
    {
        return [
            'file' => 'file|max:10240', // 10MB
            'files.*' => 'file|max:10240',
            'attachment' => 'file|max:10240',
        ];
    }

    /**
     * Get image validation rules.
     */
    protected function getImageValidationRules(): array
    {
        return [
            'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:5120', // 5MB
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:5120',
        ];
    }

    /**
     * Get pagination validation rules.
     */
    protected function getPaginationValidationRules(): array
    {
        return [
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:1|max:100',
        ];
    }

    /**
     * Get search validation rules.
     */
    protected function getSearchValidationRules(): array
    {
        return [
            'search' => 'string|max:255',
            'sort_by' => 'string|max:50',
            'sort_order' => 'string|in:asc,desc',
        ];
    }

    /**
     * Get date range validation rules.
     */
    protected function getDateRangeValidationRules(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date|after_or_equal:start_date',
        ];
    }
}

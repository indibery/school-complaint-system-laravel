<?php

namespace App\Http\Requests\Api\Category;

use Illuminate\Foundation\Http\FormRequest;

class CategoryStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // 권한 체크는 컨트롤러에서 수행
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100|unique:categories,name',
            'description' => 'nullable|string|max:500',
            'parent_id' => 'nullable|integer|exists:categories,id',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'color' => 'nullable|string|regex:/^#[0-9a-fA-F]{6}$/',
            'icon' => 'nullable|string|max:50',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => '카테고리명을 입력해주세요.',
            'name.max' => '카테고리명은 100자를 초과할 수 없습니다.',
            'name.unique' => '이미 존재하는 카테고리명입니다.',
            'description.max' => '카테고리 설명은 500자를 초과할 수 없습니다.',
            'parent_id.exists' => '존재하지 않는 부모 카테고리입니다.',
            'sort_order.min' => '정렬 순서는 0 이상의 숫자여야 합니다.',
            'color.regex' => '색상은 #FFFFFF 형식으로 입력해주세요.',
            'icon.max' => '아이콘명은 50자를 초과할 수 없습니다.',
        ];
    }
}

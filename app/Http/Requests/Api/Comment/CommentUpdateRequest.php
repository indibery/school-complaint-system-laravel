<?php

namespace App\Http\Requests\Api\Comment;

use Illuminate\Foundation\Http\FormRequest;

class CommentUpdateRequest extends FormRequest
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
            'content' => 'required|string|min:1|max:2000',
            'is_private' => 'boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'content.required' => '댓글 내용을 입력해주세요.',
            'content.min' => '댓글 내용은 최소 1자 이상 입력해주세요.',
            'content.max' => '댓글 내용은 2000자를 초과할 수 없습니다.',
        ];
    }
}

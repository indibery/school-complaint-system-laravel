<?php

namespace App\Helpers;

class FileHelper
{
    /**
     * 파일 타입에 따른 아이콘 클래스 반환
     */
    public static function getFileIcon($mimeType)
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'bi-file-image';
        }
        
        if (str_contains($mimeType, 'pdf')) {
            return 'bi-file-pdf';
        }
        
        if (str_contains($mimeType, 'word') || str_contains($mimeType, 'document')) {
            return 'bi-file-word';
        }
        
        if (str_contains($mimeType, 'excel') || str_contains($mimeType, 'spreadsheet')) {
            return 'bi-file-excel';
        }
        
        if (str_contains($mimeType, 'powerpoint') || str_contains($mimeType, 'presentation')) {
            return 'bi-file-ppt';
        }
        
        if (str_contains($mimeType, 'zip') || str_contains($mimeType, 'rar') || str_contains($mimeType, 'compressed')) {
            return 'bi-file-zip';
        }
        
        if (str_starts_with($mimeType, 'text/')) {
            return 'bi-file-text';
        }
        
        return 'bi-file-earmark';
    }

    /**
     * 파일 크기를 읽기 쉬운 형식으로 변환
     */
    public static function formatFileSize($bytes)
    {
        if ($bytes == 0) {
            return '0 Bytes';
        }

        $k = 1024;
        $sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        $i = floor(log($bytes) / log($k));

        return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
    }

    /**
     * 파일 확장자 추출
     */
    public static function getFileExtension($filename)
    {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }

    /**
     * 허용된 파일 타입인지 확인
     */
    public static function isAllowedFileType($mimeType)
    {
        $allowedTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'text/plain',
            'application/zip',
            'application/x-rar-compressed'
        ];

        return in_array($mimeType, $allowedTypes);
    }

    /**
     * 최대 파일 크기 확인
     */
    public static function isWithinSizeLimit($fileSize, $maxSizeInMB = 10)
    {
        $maxSizeInBytes = $maxSizeInMB * 1024 * 1024;
        return $fileSize <= $maxSizeInBytes;
    }
}

<?php

namespace App\Services;

/**
 * @deprecated Digantikan oleh NineRouterService.
 * Class ini mewarisi NineRouterService untuk menjaga kompatibilitas mundur (backward compatibility)
 * agar seluruh kode yang masih memanggil GeminiAiService secara otomatis menggunakan otak 9Router.
 */
class GeminiAiService extends NineRouterService
{
}

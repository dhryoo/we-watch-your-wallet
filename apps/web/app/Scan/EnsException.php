<?php

namespace App\Scan;

/** ENS 해석 중 일시적/네트워크 실패. "주소 없음"(정상 null)과 구분한다. */
class EnsException extends \RuntimeException
{
}

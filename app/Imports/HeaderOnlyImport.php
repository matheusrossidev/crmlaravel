<?php

declare(strict_types=1);

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class HeaderOnlyImport implements ToCollection, WithHeadingRow
{
    private array $headers = [];

    public function collection(Collection $rows): void
    {
        if ($rows->isEmpty()) {
            return;
        }

        $first = $rows->first();
        if ($first) {
            $this->headers = array_filter(
                array_keys($first->toArray()),
                fn ($h) => $h !== null && $h !== ''
            );
        }
    }

    /** @return string[] */
    public function getHeaders(): array
    {
        return $this->headers;
    }
}

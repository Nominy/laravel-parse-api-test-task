<?php

namespace App\Models\Concerns;

trait HandlesApiData
{
    public static function createFromApiData(array $data): self
    {
        return self::create(static::mapApiData($data));
    }

    public static function prepareApiDataForInsert(array $data): array
    {
        $mappedData = static::mapApiData($data);
        $now = now();

        return array_merge($mappedData, [
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    abstract protected static function mapApiData(array $data): array;
}

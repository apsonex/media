<?php

namespace Apsonex\Media\Factory;

use Illuminate\Support\Collection;

class ParseImageVariations
{

    /**
     * dimension:100x100,filename
     * 100x100
     *
     * @param $variations
     * @return Collection
     */
    public static function parse($variations): Collection
    {
        $parsed = [];

        $enum = enum_exists(ImageSize::class) ? (new \ReflectionEnum(ImageSize::class)) : null;

        foreach ($variations as $variation) {
            if (str($variation)->startsWith('dimension:')) {
                $afterColon = str($variation)->after(':');
                $dimension = $afterColon->contains(',') ? $afterColon->before(',') : $afterColon;
                $dims = str($dimension)->explode('x');
                $name = $afterColon->contains(',') ? $afterColon->afterLast(',')->slug()->toString() : "{$dims[0]}x{$dims[1]}";

                $parsed[$name] = [
                    'name'   => $name,
                    'width'  => (int)$dims[0],
                    'height' => (int)$dims[1]
                ];
                continue;
            }

            $upper = strtoupper($variation);

            if ($enum && ($dimension = $enum->getConstant($upper))) {
                $dims = str($dimension)->explode('x');
                $prefix = strtolower($variation);
                $parsed[$prefix] = [
                    'name'   => $prefix,
                    'width'  => (int)$dims[0],
                    'height' => (int)$dims[1]
                ];
            }
        }

        return collect($parsed);
    }


}
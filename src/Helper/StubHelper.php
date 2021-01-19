<?php


namespace Nichozuo\LaravelCodegen\Helper;


use Illuminate\Support\Facades\File;

class StubHelper
{
    /**
     * @param $name
     * @return string
     */
    public static function getStub($name): string
    {
        $path = __DIR__ . '/../resources/laravel-codegen/stubs/' . $name;
        return File::get($path);
    }

    /**
     * @param array $array
     * @param $stubContent
     * @return mixed|string|string[]
     */
    public static function replace(array $array, $stubContent)
    {
        foreach ($array as $key => $value) {
            $stubContent = str_replace($key, $value, $stubContent);
        }
        return $stubContent;
    }

    /**
     * @param string $filePath
     * @param string $stubContent
     * @param bool $force
     */
    public static function save(string $filePath, string $stubContent, bool $force = false)
    {
        $exists = File::exists($filePath);
        if (!$exists || $force) {
            File::makeDirectory(File::dirname($filePath), 0755, true, true);
            File::put($filePath, $stubContent);
        }
    }
}
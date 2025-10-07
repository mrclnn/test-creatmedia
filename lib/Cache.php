<?php

namespace lib;

class Cache
{
    private string $cacheFile;
    const CACHE_DIR = __DIR__ . '/../storage/cache';

    /**
     *
     * @param string $task - для какой задачи конкретно создаем кэш, имя файла .json в /storage/cache
     */
    public function __construct(string $task)
    {

        if (!realpath(self::CACHE_DIR) || !is_dir(self::CACHE_DIR) || !is_writable(self::CACHE_DIR)) {
            // обычно структура папок уже должна существовать и если нет - создать их "молча"
            // может вызвать трудноуловимые ошибки. для простоты в тестовом задании создаем молча
            mkdir(self::CACHE_DIR, 0766, true);
        }

        $this->cacheFile = realpath(self::CACHE_DIR) . "/$task.json";

        // Если файла нет — создаем пустой JSON
        if (!file_exists($this->cacheFile)) {
            file_put_contents($this->cacheFile, json_encode([], JSON_PRETTY_PRINT));
        }
    }

    public function add(string $key, $value): void
    {
        $data = $this->readCache();

        if (!isset($data[$key]) || !is_array($data[$key])) {
            $data[$key] = [];
        }

        $data[$key][] = $value;

        $this->writeCache($data);
    }

    public function put(string $key, $value): void
    {
        $data = $this->readCache();
        $data[$key] = $value;
        $this->writeCache($data);
    }

    public function get(string $key)
    {
        $data = $this->readCache();
        return $data[$key] ?? null;
    }

    public function clear(): void
    {
        $this->writeCache([]);
    }

    private function readCache(): array
    {
        if (!file_exists($this->cacheFile)) {
            return [];
        }

        $content = file_get_contents($this->cacheFile);
        $data = json_decode($content, true);

        return is_array($data) ? $data : [];
    }

    private function writeCache(array $data): void
    {
        file_put_contents($this->cacheFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
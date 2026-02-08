<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;

class MenuAccessStorage
{
    private const STORAGE_PATH = 'layout/allowed-users.json';

    /**
     * Get all menu access groups
     * 
     * @return array
     */
    public function all(): array
    {
        if (!Storage::exists(self::STORAGE_PATH)) {
            return [];
        }

        $content = Storage::get(self::STORAGE_PATH);
        
        if (empty($content)) {
            return [];
        }

        $data = json_decode($content, true);

        return $data ?? [];
    }

    /**
     * Delete a menu access group by key
     * 
     * @param string $key
     * @return bool
     */
    public function delete(string $key): bool
    {
        $all = $this->all();

        if (!isset($all[$key])) {
            return false;
        }

        unset($all[$key]);

        return $this->save($all);
    }

    /**
     * Create or update a menu access group
     * 
     * @param string $key
     * @param array $users
     * @param string|null $label
     * @return bool
     */
    public function upsert(string $key, array $users, ?string $label = null): bool
    {
        $all = $this->all();

        $all[$key] = [
            'label' => $label,
            'users' => array_values(array_unique($users)),
        ];

        return $this->save($all);
    }

    /**
     * Parse user input string into array of users
     * 
     * @param string $input
     * @return array
     */
    public function parseInput(string $input): array
    {
        if (empty(trim($input))) {
            return [];
        }

        // Split by newlines, commas, or semicolons
        $users = preg_split('/[\r\n,;]+/', $input);
        
        // Trim and filter empty values
        $users = array_map('trim', $users);
        $users = array_filter($users, fn($user) => !empty($user));

        return array_values($users);
    }

    /**
     * Save data to storage file
     * 
     * @param array $data
     * @return bool
     */
    private function save(array $data): bool
    {
        // Sort by key for consistency
        ksort($data);

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return Storage::put(self::STORAGE_PATH, $json);
    }
}


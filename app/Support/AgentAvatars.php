<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Catálogo de avatares decorativos para agentes de IA.
 *
 * IMPORTANTE: esses avatares são APENAS para exibição na UI admin
 * (lista de agentes, sidebar do form de edit, etc). NUNCA são enviados
 * pro lead/cliente final via WhatsApp/Instagram/Web. Para o avatar do
 * widget web chat (que VAI pro visitante), use o campo `bot_avatar`.
 */
final class AgentAvatars
{
    /**
     * @return list<array{slug:string,file:string,name:string,gender:string}>
     */
    public static function all(): array
    {
        return [
            // Mulheres
            ['slug' => 'ana',       'file' => '/images/agents-avatar/ana.png',       'name' => 'Ana',       'gender' => 'f'],
            ['slug' => 'beatriz',   'file' => '/images/agents-avatar/beatriz.png',   'name' => 'Beatriz',   'gender' => 'f'],
            ['slug' => 'camila',    'file' => '/images/agents-avatar/camila.png',    'name' => 'Camila',    'gender' => 'f'],
            ['slug' => 'daniela',   'file' => '/images/agents-avatar/daniela.png',   'name' => 'Daniela',   'gender' => 'f'],
            ['slug' => 'fernanda',  'file' => '/images/agents-avatar/fernanda.png',  'name' => 'Fernanda',  'gender' => 'f'],
            ['slug' => 'juliana',   'file' => '/images/agents-avatar/juliana.png',   'name' => 'Juliana',   'gender' => 'f'],
            ['slug' => 'larissa',   'file' => '/images/agents-avatar/larissa.png',   'name' => 'Larissa',   'gender' => 'f'],
            // Homens
            ['slug' => 'bruno',     'file' => '/images/agents-avatar/bruno.png',     'name' => 'Bruno',     'gender' => 'm'],
            ['slug' => 'carlos',    'file' => '/images/agents-avatar/carlos.png',    'name' => 'Carlos',    'gender' => 'm'],
            ['slug' => 'diego',     'file' => '/images/agents-avatar/diego.png',     'name' => 'Diego',     'gender' => 'm'],
            ['slug' => 'eduardo',   'file' => '/images/agents-avatar/eduardo.png',   'name' => 'Eduardo',   'gender' => 'm'],
            ['slug' => 'felipe',    'file' => '/images/agents-avatar/felipe.png',    'name' => 'Felipe',    'gender' => 'm'],
            ['slug' => 'gabriel',   'file' => '/images/agents-avatar/gabriel.png',   'name' => 'Gabriel',   'gender' => 'm'],
        ];
    }

    public static function default(): string
    {
        return '/images/agents-avatar/ana.png';
    }

    public static function findByFile(?string $file): ?array
    {
        if (! $file) {
            return null;
        }
        foreach (self::all() as $avatar) {
            if ($avatar['file'] === $file) {
                return $avatar;
            }
        }
        return null;
    }
}

<?php
declare(strict_types=1);

class TagController
{
    /** GET /tags/index */
    public function index(?int $id, array $body): void
    {
        json_response(Tag::all());
    }

    /** POST /tags/store */
    public function store(?int $id, array $body): void
    {
        $data = $body ?: $_POST;
        $name = trim($data['name'] ?? '');
        if ($name === '') {
            json_response(['error' => 'Název štítku je povinný'], 422);
        }
        $color = $data['color'] ?? '#a78bfa';
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            $color = '#a78bfa';
        }
        $newId = Tag::create(['name' => $name, 'color' => $color]);
        json_response(['id' => $newId, 'message' => 'Štítek vytvořen'], 201);
    }

    /** POST /tags/update/{id} */
    public function update(?int $id, array $body): void
    {
        if (!$id) json_response(['error' => 'Chybí ID'], 400);
        $data = $body ?: $_POST;
        $name = trim($data['name'] ?? '');
        if ($name === '') {
            json_response(['error' => 'Název štítku je povinný'], 422);
        }
        $color = $data['color'] ?? '#a78bfa';
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            $color = '#a78bfa';
        }
        Tag::update($id, ['name' => $name, 'color' => $color]);
        json_response(['message' => 'Štítek uložen']);
    }

    /** POST /tags/delete/{id} */
    public function delete(?int $id, array $body): void
    {
        if (!$id) json_response(['error' => 'Chybí ID'], 400);
        Tag::delete($id);
        json_response(['message' => 'Štítek smazán']);
    }

    /** POST /tags/reorder */
    public function reorder(?int $id, array $body): void
    {
        $ids = $body['ids'] ?? [];
        if (!is_array($ids)) json_response(['error' => 'Neplatná data'], 400);
        Tag::reorder($ids);
        json_response(['message' => 'Pořadí uloženo']);
    }
}

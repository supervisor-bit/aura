<?php
declare(strict_types=1);

class CodeListController
{
    /** GET /codelists/index — all items (optionally filtered by ?type=) */
    public function index(?int $id, array $body): void
    {
        $type = $_GET['type'] ?? null;
        if ($type) {
            $allowed = ['service', 'ratio', 'bowl', 'material'];
            if (!in_array($type, $allowed, true)) {
                json_response(['error' => 'Neplatný typ'], 400);
            }
            json_response(CodeList::byType($type));
        }
        json_response(CodeList::all());
    }

    /** POST /codelists/store */
    public function store(?int $id, array $body): void
    {
        $allowed = ['service', 'ratio', 'bowl', 'material'];
        if (empty($body['type']) || !in_array($body['type'], $allowed, true)) {
            json_response(['error' => 'Neplatný typ'], 400);
        }
        if (empty($body['name'])) {
            json_response(['error' => 'Název je povinný'], 400);
        }
        $newId = CodeList::create($body);
        json_response(['id' => $newId, 'ok' => true]);
    }

    /** POST /codelists/update/{id} */
    public function update(?int $id, array $body): void
    {
        if (!$id) {
            json_response(['error' => 'Chybí ID'], 400);
        }
        if (empty($body['name'])) {
            json_response(['error' => 'Název je povinný'], 400);
        }
        CodeList::update($id, $body);
        json_response(['ok' => true]);
    }

    /** POST /codelists/reorder — přeřadit položky */
    public function reorder(?int $id, array $body): void
    {
        if (empty($body['ids']) || !is_array($body['ids'])) {
            json_response(['error' => 'Chybí seznam ID'], 400);
        }
        CodeList::reorder($body['ids']);
        json_response(['ok' => true]);
    }

    /** POST /codelists/delete/{id} */
    public function delete(?int $id, array $body): void
    {
        if (!$id) {
            json_response(['error' => 'Chybí ID'], 400);
        }
        CodeList::delete($id);
        json_response(['ok' => true]);
    }
}

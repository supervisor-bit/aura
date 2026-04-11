<?php
declare(strict_types=1);

class NoteController
{
    /** GET /notes/index/{clientId} */
    public function index(?int $id, array $body): void
    {
        if (!$id) json_response(['error' => 'Chybí client_id'], 400);
        json_response(ClientNote::forClient($id));
    }

    /** POST /notes/store */
    public function store(?int $id, array $body): void
    {
        $clientId = (int)($body['client_id'] ?? 0);
        $content  = trim($body['content'] ?? '');

        if (!$clientId) json_response(['error' => 'Chybí client_id'], 400);
        if ($content === '') json_response(['error' => 'Poznámka nesmí být prázdná'], 400);

        $newId = ClientNote::create($clientId, $content);
        $note  = ClientNote::find($newId);
        json_response($note);
    }

    /** POST /notes/update/{id} */
    public function update(?int $id, array $body): void
    {
        if (!$id) json_response(['error' => 'Chybí ID'], 400);
        $content = trim($body['content'] ?? '');
        if ($content === '') json_response(['error' => 'Poznámka nesmí být prázdná'], 400);

        ClientNote::update($id, $content);
        $note = ClientNote::find($id);
        json_response($note);
    }

    /** POST /notes/delete/{id} */
    public function delete(?int $id, array $body): void
    {
        if (!$id) json_response(['error' => 'Chybí ID'], 400);
        ClientNote::delete($id);
        json_response(['message' => 'Smazáno']);
    }
}

<?php

namespace AppTest\models;

use Minz\Database;

#[Database\Table(name: 'friends')]
class Friend
{
    use Database\Recordable;

    #[Database\Column]
    public int $id;

    #[Database\Column]
    public string $name;

    #[Database\Column]
    public ?string $address = null;

    #[Database\Column]
    public ?\DateTimeImmutable $created_at = null;

    #[Database\Column]
    public ?\DateTimeImmutable $updated_at = null;

    #[Database\Column]
    public bool $is_kind = true;

    /** @var array<string, bool> */
    #[Database\Column]
    public array $options = [];
}

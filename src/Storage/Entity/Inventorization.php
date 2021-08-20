<?php

declare(strict_types=1);

namespace App\Storage\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Tenant\Entity\TenantEntity;

/**
 * @ORM\Entity()
 */
class Inventorization extends TenantEntity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="inventorization_id")
     *
     * @psalm-readonly
     */
    public InventorizationId $id;

    /**
     * @ORM\OneToOne(targetEntity=InventorizationClose::class, mappedBy="inventorization", cascade={"persist"})
     */
    private ?InventorizationClose $close = null;

    public function __construct()
    {
        $this->id = InventorizationId::generate();
    }

    public function toId(): InventorizationId
    {
        return $this->id;
    }

    public function close(): void
    {
        if (null !== $this->close) {
            return;
        }

        $this->close = new InventorizationClose($this);
    }

    public function isClosed(): bool
    {
        return null !== $this->close;
    }
}

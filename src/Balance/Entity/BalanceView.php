<?php

declare(strict_types=1);

namespace App\Balance\Entity;

use App\Tenant\Entity\TenantEntity;
use Doctrine\ORM\Mapping as ORM;
use Money\Money;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity(readOnly=true)
 * @ORM\Table(name="balance_view")
 *
 * @psalm-suppress MissingConstructor
 */
class BalanceView extends TenantEntity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="uuid")
     */
    public UuidInterface $id;

    /**
     * @ORM\Embedded(class=Money::class, columnPrefix=false)
     */
    public Money $money;
}

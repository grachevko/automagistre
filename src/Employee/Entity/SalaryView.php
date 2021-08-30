<?php

declare(strict_types=1);

namespace App\Employee\Entity;

use App\CreatedBy\Entity\Blamable;
use App\Customer\Entity\OperandId;
use App\Tenant\Entity\TenantEntity;
use Doctrine\ORM\Mapping as ORM;
use Money\Money;

/**
 * @ORM\Entity(readOnly=true)
 * @ORM\Table(name="salary_view")
 *
 * @psalm-suppress MissingConstructor
 */
class SalaryView extends TenantEntity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="salary_id")
     */
    public SalaryId $id;

    /**
     * @ORM\Column(type="employee_id")
     */
    public EmployeeId $employeeId;

    /**
     * @ORM\Column(type="operand_id")
     */
    public OperandId $personId;

    /**
     * @ORM\Column(type="integer")
     */
    public int $payday;

    /**
     * @ORM\Column(type="money")
     */
    public Money $amount;

    /**
     * @ORM\Embedded(class=Blamable::class)
     */
    public Blamable $created;

    /**
     * @ORM\Embedded(class=Blamable::class)
     */
    public ?Blamable $ended = null;
}

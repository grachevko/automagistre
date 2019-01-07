<?php

declare(strict_types=1);

namespace App\Entity;

use App\Doctrine\ORM\Mapping\Traits\Identity;
use App\Doctrine\ORM\Mapping\Traits\WalletTrait;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use LogicException;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity
 *
 * @UniqueEntity(fields={"person", "firedAt"}, message="Данный человек уже является сотрудником", ignoreNull=false)
 */
class Employee implements WalletOwner
{
    use Identity;
    use WalletTrait;

    /**
     * @var Person|null
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Person")
     * @ORM\JoinColumn(nullable=false)
     */
    private $person;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     */
    private $ratio;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime")
     */
    private $hiredAt;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $firedAt;

    public function __construct()
    {
        $this->hiredAt = new DateTime();
    }

    public function __toString(): string
    {
        return $this->person->getFullName();
    }

    public function setPerson(Person $person): void
    {
        if ($this->person instanceof Person) {
            throw new LogicException('Person for Employee can\'t be replaced');
        }

        $this->person = $person;
        $this->setWallet($person->getWallet());
    }

    public function getPerson(): ?Person
    {
        return $this->person;
    }

    public function setRatio(int $ratio): void
    {
        $this->ratio = $ratio;
    }

    public function getRatio(): ?int
    {
        return $this->ratio;
    }

    public function getHiredAt(): DateTime
    {
        return $this->hiredAt;
    }

    public function getFiredAt(): ?DateTime
    {
        return $this->firedAt;
    }

    public function getFullName(): string
    {
        return $this->person->getFullName();
    }

    public function isFired(): bool
    {
        return null !== $this->firedAt;
    }

    public function fire(): void
    {
        $this->firedAt = new DateTime();
    }
}

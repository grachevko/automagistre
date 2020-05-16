<?php

declare(strict_types=1);

namespace App\Car\Ports\EasyAdmin;

use App\Car\Entity\Car;
use App\Car\Entity\CarId;
use App\Car\Entity\CarPossession;
use App\Car\Entity\Note;
use App\Car\Form\DTO\CarDto;
use App\Car\Form\DTO\CarPossessionDto;
use App\Car\Repository\CarPossessionRepository;
use App\Controller\EasyAdmin\AbstractController;
use App\Customer\Domain\Operand;
use App\Customer\Domain\OperandId;
use App\Customer\Domain\Organization;
use App\Customer\Domain\Person;
use App\EasyAdmin\Form\AutocompleteType;
use App\Order\Entity\Order;
use App\Shared\Enum\Transition;
use App\Vehicle\Domain\Embeddable\Engine;
use App\Vehicle\Domain\Embeddable\Equipment;
use App\Vehicle\Domain\Model;
use App\Vehicle\Domain\VehicleId;
use function array_map;
use function array_merge;
use function assert;
use Closure;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use function explode;
use function mb_strtolower;
use function sprintf;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Konstantin Grachev <me@grachevko.ru>
 */
final class CarController extends AbstractController
{
    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            CarPossessionRepository::class,
        ]);
    }

    public function possessionAction(): Response
    {
        /** @var CarId|null $carId */
        $carId = $this->getIdentifier(CarId::class);
        /** @var OperandId|null $possessorId */
        $possessorId = $this->getIdentifier(OperandId::class);

        $dto = new CarPossessionDto($carId, $possessorId);

        $form = $this->createFormBuilder($dto)
            ->add('possessorId', AutocompleteType::class, [
                'label' => 'Владелец',
                'class' => Operand::class,
                'disabled' => null !== $dto->possessorId,
            ])
            ->add('carId', AutocompleteType::class, [
                'label' => 'Автомобиль',
                'class' => Car::class,
                'disabled' => null !== $dto->carId,
            ])
            ->getForm()
            ->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $em->persist(new CarPossession($dto->possessorId, $dto->carId, Transition::promote()));
            $em->flush();

            return $this->redirectToReferrer();
        }

        return $this->render('easy_admin/simple.html.twig', [
            'content_title' => 'Связать автомобиль и владельца',
            'form' => $form->createView(),
            'button' => 'Связать',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityFormOptions($entity, $view): array
    {
        $request = $this->request;
        $options = parent::getEntityFormOptions($entity, $view);

        $options['validation_groups'] = 'equipment' === $request->query->getAlnum('validate')
            ? ['Default', 'CarEquipment', 'CarEngine']
            : ['Default'];

        return $options;
    }

    /**
     * {@inheritdoc}
     */
    protected function createNewEntity(): CarDto
    {
        return new CarDto(CarId::generate());
    }

    protected function persistEntity($entity): Car
    {
        $dto = $entity;
        assert($dto instanceof CarDto);

        $entity = new Car(
            CarId::generate(),
        );
        $entity->equipment = $dto->equipment;
        $entity->setGosnomer($dto->gosnomer);
        $entity->identifier = $dto->identifier;
        $entity->year = $dto->year;
        $entity->caseType = $dto->caseType;
        $entity->description = $dto->description;

        if (null !== $dto->model) {
            $entity->vehicleId = $dto->model->toId();
        }

        parent::persistEntity($entity);

        return $entity;
    }

    protected function createEditDto(Closure $callable): ?object
    {
        $arr = $callable();

        $vehicleId = $arr['vehicleId'];
        $vehicle = $vehicleId instanceof VehicleId
            ? $this->registry->findBy(Model::class, ['uuid' => $vehicleId])
            : null;

        $equipment = new Equipment(
            new Engine($arr['equipment.engine.name'], $arr['equipment.engine.type'], $arr['equipment.engine.capacity']),
            $arr['equipment.transmission'],
            $arr['equipment.wheelDrive'],
        );

        return new CarDto(
            $arr['uuid'],
            $equipment,
            $vehicle,
            $arr['identifier'],
            $arr['year'],
            $arr['caseType'],
            $arr['description'],
            $arr['gosnomer'],
        );
    }

    protected function updateEntity($entity): Car
    {
        $dto = $entity;
        assert($dto instanceof CarDto);

        $entity = $this->registry->findBy(Car::class, ['uuid' => $dto->carId]);

        $entity->equipment = $dto->equipment;
        $entity->setGosnomer($dto->gosnomer);
        $entity->identifier = $dto->identifier;
        $entity->year = $dto->year;
        $entity->caseType = $dto->caseType;
        $entity->description = $dto->description;

        if (null !== $dto->model) {
            $entity->vehicleId = $dto->model->toId();
        }

        parent::updateEntity($entity);

        return $entity;
    }

    /**
     * {@inheritdoc}
     */
    protected function renderTemplate($actionName, $templatePath, array $parameters = []): Response
    {
        if ('show' === $actionName) {
            /** @var Car $car */
            $car = $parameters['entity'];

            /** @var CarPossessionRepository $possessions */
            $possessions = $this->container->get(CarPossessionRepository::class);

            $parameters['orders'] = $this->registry->repository(Order::class)
                ->findBy(['car.id' => $car->getId()], ['closedAt' => 'DESC'], 20);
            $parameters['notes'] = $this->registry->repository(Note::class)
                ->findBy(['car' => $car], ['createdAt' => 'DESC']);
            $parameters['possessors'] = $possessions->possessorsByCar($car->toId());
        }

        return parent::renderTemplate($actionName, $templatePath, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    protected function createSearchQueryBuilder(
        $entityClass,
        $searchQuery,
        array $searchableFields,
        $sortField = null,
        $sortDirection = null,
        $dqlFilter = null
    ): QueryBuilder {
        $qb = $this->registry->repository(Car::class)->createQueryBuilder('car');

        if ('' === $searchQuery) {
            return $qb;
        }

        // TODO Восстановить поиск по производителю и модели

        $qb
//            ->leftJoin(Model::class, 'model', Join::WITH, 'model.uuid = car.vehicleId')
//            ->leftJoin(Manufacturer::class, 'manufacturer', Join::WITH, 'manufacturer.uuid = model.manufacturerId')
            ->leftJoin(CarPossession::class, 'possession', Join::WITH, 'possession.carId = car.uuid')
            ->leftJoin(Operand::class, 'owner', Join::WITH, 'possession.possessorId = owner.uuid')
            ->leftJoin(Person::class, 'person', Join::WITH, 'person.id = owner.id AND owner INSTANCE OF '.Person::class)
            ->leftJoin(Organization::class, 'organization', Join::WITH, 'organization.id = owner.id AND owner INSTANCE OF '.Organization::class);

        foreach (explode(' ', $searchQuery) as $key => $searchString) {
            $key = ':search_'.$key;

            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->like('LOWER(CAST(car.year AS string))', $key),
                $qb->expr()->like('LOWER(car.gosnomer)', $key),
                $qb->expr()->like('LOWER(car.identifier)', $key),
                $qb->expr()->like('LOWER(car.description)', $key),
//                $qb->expr()->like('LOWER(model.name)', $key),
//                $qb->expr()->like('LOWER(model.localizedName)', $key),
//                $qb->expr()->like('LOWER(manufacturer.name)', $key),
//                $qb->expr()->like('LOWER(manufacturer.localizedName)', $key),
                $qb->expr()->like('LOWER(person.firstname)', $key),
                $qb->expr()->like('LOWER(person.lastname)', $key),
                $qb->expr()->like('LOWER(person.telephone)', $key),
                $qb->expr()->like('LOWER(person.email)', $key),
                $qb->expr()->like('LOWER(organization.name)', $key)
            ));

            $qb->setParameter($key, '%'.mb_strtolower($searchString).'%');
        }

        return $qb;
    }

    /**
     * {@inheritdoc}
     */
    protected function autocompleteAction(): JsonResponse
    {
        $query = $this->request->query;
        $isUuid = $query->has('use_uuid');

        $qb = $this->createSearchQueryBuilder($query->get('entity'), $query->get('query', ''), []);

        // TODO Поиск по собственнику
//        $ownerId = $query->get('owner_id');
//        if (null !== $ownerId) {
//            $qb->andWhere('car.owner = :owner')
//                ->setParameter('owner', $em->getReference(Operand::class, $ownerId));
//        }

        $paginator = $this->get('easyadmin.paginator')->createOrmPaginator($qb, $query->get('page', 1));

        $data = array_map(function (Car $car) use ($isUuid): array {
            $text = '';

            if (null !== $car->vehicleId) {
                $text .= $this->display($car->vehicleId, 'long');
            }

            $gosnomer = $car->getGosnomer();
            if (null !== $gosnomer) {
                $text .= sprintf(' (%s)', $gosnomer);
            }

            // TODO Как выводить нескольких владельцев?
//            $person = $car->owner;
//            if (null === $ownerId && $person instanceof Person) {
//                $text .= ' - '.$person->getFullName();
//
//                $telephone = $person->getTelephone();
//                if (null !== $telephone) {
//                    $text .= sprintf(' (%s)', $this->formatTelephone($telephone));
//                }
//            }

            return [
                'id' => $isUuid ? $car->toId()->toUuid() : $car->getId(),
                'text' => $text,
            ];
        }, (array) $paginator->getCurrentPageResults());

        return $this->json(['results' => $data]);
    }
}

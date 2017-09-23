<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\User;
use App\Request\EntityTransformer;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AdminController as EasyAdminController;
use EasyCorp\Bundle\EasyAdminBundle\Event\EasyAdminEvents;
use Money\MoneyFormatter;
use Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface;

/**
 * @method User getUser()
 *
 * @author Konstantin Grachev <me@grachevko.ru>
 */
abstract class AdminController extends EasyAdminController
{
    /**
     * @var MoneyFormatter
     */
    protected $moneyFormatter;

    /**
     * @var EntityTransformer
     */
    private $entityTransformer;

    /**
     * @var ArgumentResolverInterface
     */
    private $argumentResolver;

    /**
     * @required
     */
    public function setEntityTransformer(EntityTransformer $entityTransformer): void
    {
        $this->entityTransformer = $entityTransformer;
    }

    /**
     * @required
     */
    public function setMoneyFormatter(MoneyFormatter $moneyFormatter): void
    {
        $this->moneyFormatter = $moneyFormatter;
    }

    /**
     * @required
     */
    public function setArgumentResolver(ArgumentResolverInterface $argumentResolver): void
    {
        $this->argumentResolver = $argumentResolver;
    }

    /**
     * {@inheritdoc}
     */
    protected function newAction()
    {
        $this->dispatch(EasyAdminEvents::PRE_NEW);

        $entity = $this->executeDynamicMethod('createNew<EntityName>Entity');

        $easyadmin = $this->request->attributes->get('easyadmin');
        $easyadmin['item'] = $entity;
        $this->request->attributes->set('easyadmin', $easyadmin);

        $fields = $this->entity['new']['fields'];

        $newForm = $this->executeDynamicMethod('create<EntityName>NewForm', [$entity, $fields]);

        $newForm->handleRequest($this->request);
        if ($newForm->isSubmitted() && $newForm->isValid()) {
            $this->dispatch(EasyAdminEvents::PRE_PERSIST, ['entity' => $entity]);

            $this->executeDynamicMethod('prePersist<EntityName>Entity', [$entity]);

            $this->persistEntity($entity);

            $this->dispatch(EasyAdminEvents::POST_PERSIST, ['entity' => $entity]);

            $refererUrl = $this->request->query->get('referer', '');

            return !empty($refererUrl)
                ? $this->redirect(urldecode($refererUrl))
                : $this->redirect($this->generateUrl('easyadmin', [
                    'action' => 'list', 'entity' => $this->entity['name'],
                ]));
        }

        $this->dispatch(EasyAdminEvents::POST_NEW, [
            'entity_fields' => $fields,
            'form' => $newForm,
            'entity' => $entity,
        ]);

        return $this->render($this->entity['templates']['new'], [
            'form' => $newForm->createView(),
            'entity_fields' => $fields,
            'entity' => $entity,
        ]);
    }

    protected function executeDynamicMethod($methodNamePattern, array $arguments = [])
    {
        $methodName = str_replace('<EntityName>', $this->entity['name'], $methodNamePattern);

        if (!is_callable([$this, $methodName])) {
            $methodName = str_replace('<EntityName>', '', $methodNamePattern);
        }

        try {
            $resolvedArgs = $this->argumentResolver->getArguments($this->request, [$this, $methodName]);
            if ($resolvedArgs) {
                $arguments = array_merge($resolvedArgs, $arguments);
            }
        } catch (\RuntimeException $e) {
        }

        return parent::executeDynamicMethod($methodName, $arguments);
    }

    protected function persistEntity($entity): void
    {
        $this->em->persist($entity);
        $this->em->flush();
    }

    protected function getEntity(string $class)
    {
        return $this->entityTransformer->reverseTransform($class);
    }
}

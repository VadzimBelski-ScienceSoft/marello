<?php

namespace Marello\Bundle\OrderBundle\EventListener\Doctrine;

use Doctrine\ORM\Event\LifecycleEventArgs;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Marello\Bundle\OrderBundle\Entity\Order;
use Marello\Bundle\OrderBundle\Entity\OrderItem;
use Marello\Bundle\InventoryBundle\Event\InventoryUpdateEvent;
use Marello\Bundle\InventoryBundle\Model\InventoryUpdateContext;

class OrderInventoryAllocationListener
{
    /** @var EventDispatcherInterface $eventDispatcher */
    protected $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();

        if (!$entity instanceof Order) {
            return;
        }

        $entity->getItems()->map(function (OrderItem $item) use ($entity, $args) {
            $this->handleInventoryUpdate($item, $item->getQuantity(), $entity);
        });
    }

    /**
     * handle the inventory update for items which have been received
     * @param OrderItem $item
     * @param $inventoryUpdateQty
     * @param Order $order
     */
    protected function handleInventoryUpdate($item, $inventoryUpdateQty, $order)
    {
        $inventoryItems = $item->getProduct()->getInventoryItems();
        $inventoryItemData = [];
        foreach ($inventoryItems as $inventoryItem) {
            $inventoryItemData[] = [
                'item'          => $inventoryItem,
                'qty'           => null,
                'allocatedQty'  => $inventoryUpdateQty
            ];
        }

        $data = [
            'stock'             => null,
            'allocatedStock'    => $inventoryUpdateQty,
            'trigger'           => 'order_workflow.pending',
            'items'             => $inventoryItemData,
            'relatedEntity'     => $order
        ];

        $context = InventoryUpdateContext::createUpdateContext($data);
        $this->eventDispatcher->dispatch(
            InventoryUpdateEvent::NAME,
            new InventoryUpdateEvent($context)
        );
    }
}

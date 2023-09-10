<?php

namespace App\Projectors;

use App\StorableEvents\WooCommerce\CustomerDeleted;
use App\StorableEvents\WooCommerce\SubscriptionCreated;
use App\StorableEvents\WooCommerce\SubscriptionDeleted;
use App\StorableEvents\WooCommerce\SubscriptionImported;
use App\StorableEvents\WooCommerce\SubscriptionUpdated;
use App\Models\Subscription;
use Spatie\EventSourcing\EventHandlers\Projectors\Projector;
use Spatie\EventSourcing\EventHandlers\Projectors\ProjectsEvents;

final class SubscriptionProjector extends Projector
{
    use ProjectsEvents;

    public function onStartingEventReplay()
    {
        Subscription::truncate();
    }

    public function onSubscriptionImported(SubscriptionImported $event)
    {
        $this->addOrGetSubscription($event->subscription);
    }

    public function onSubscriptionCreated(SubscriptionCreated $event)
    {
        $this->addOrGetSubscription($event->subscription);
    }

    public function onSubscriptionUpdated(SubscriptionUpdated $event)
    {
        $subscription = $this->addOrGetSubscription($event->subscription);

        $subscription->status = $event->subscription['status'];

        $subscription->save();
    }

    public function onSubscriptionDeleted(SubscriptionDeleted $event)
    {
        Subscription::whereWooId($event->subscription['id'])->delete();
    }

    public function onCustomerDeleted(CustomerDeleted $event)
    {
        Subscription::whereCustomerId($event->customerId)
            ->delete();
    }

    /**
     * @return Subscription
     */
    private function addOrGetSubscription($subscription)
    {
        $subscriptionModel = Subscription::whereWooId($subscription['id'])->first();

        if (is_null($subscriptionModel)) {
            $wooId = $subscription['id'];
            $customerId = $subscription['customer_id'];
            $status = $subscription['status'];

            return Subscription::create([
                'woo_id' => $wooId,
                'customer_id' => $customerId,
                'status' => $status,
            ]);
        } else {
            return $subscriptionModel;
        }
    }
}

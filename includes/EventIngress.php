<?php

namespace MediaWiki\Extension\Gadgets;

use MediaWiki\DomainEvent\EventIngressBase;
use MediaWiki\Page\Event\PageDeletedEvent;
use MediaWiki\Page\Event\PageRevisionUpdatedEvent;
use MediaWiki\Title\TitleValue;

/**
 * Event subscriber acting as an ingress for relevant events emitted
 * by MediaWiki core.
 */
class EventIngress extends EventIngressBase {
	private GadgetRepo $gadgetRepo;

	public function __construct(
		GadgetRepo $gadgetRepo
	) {
		$this->gadgetRepo = $gadgetRepo;
	}

	/**
	 * Handle PageRevisionUpdatedEvent
	 */
	public function handlePageRevisionUpdatedEvent(
		PageRevisionUpdatedEvent $event
	): void {
		$title = TitleValue::newFromPage( $event->getPage() );
		$this->gadgetRepo->handlePageUpdate( $title );
	}

	/**
	 * Handle PageDeletedEvent
	 */
	public function handlePageDeletedEvent(
		PageDeletedEvent $event
	): void {
		$title = TitleValue::newFromPage( $event->getPage() );
		$this->gadgetRepo->handlePageUpdate( $title );
	}

}

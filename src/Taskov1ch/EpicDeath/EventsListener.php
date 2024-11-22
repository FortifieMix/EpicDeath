<?php

namespace Taskov1ch\EpicDeath;

use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\server\CommandEvent;
use pocketmine\player\Player;
use Taskov1ch\EpicDeath\events\EpicDeathEvent;

class EventsListener implements Listener
{
	public function __construct(private Main $main) {}

	public function onDamage(EntityDamageEvent $event): void
	{
		$victim = $event->getEntity();

		if ($this->main->inProcess($victim)) {
			return;
		}

		if (!$event instanceof EntityDamageByEntityEvent) {
			return;
		}

		if ($this->main->inProcess($event->getDamager())) {
			return;
		}

		$health = $victim->getHealth();

		if ($health > $event->getFinalDamage()) {
			return;
		}

		$event->cancel();
		$customEvent = new EpicDeathEvent($victim);
		$customEvent->call();

		if ($customEvent->isCancelled()) {
			$event->uncancel();
			return;
		}

		$this->main->startProcess($victim);
	}

	public function onInteract(PlayerInteractEvent $event): void
	{
		if ($this->main->inProcess($event->getPlayer())) {
			$event->cancel();
		}
	}

	public function onCommand(CommandEvent $event): void
	{
		$sender = $event->getSender();

		if ($sender instanceof Player and $this->main->inProcess($sender)) {
			$event->cancel();
		}
	}

	public function onRespawn(PlayerRespawnEvent $event): void
	{
		$player = $event->getPlayer();

		if ($this->main->inProcess($player)) {
			$this->main->unsetProcess($player);
		}
	}
}